<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../login/login-form.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PENDING BOOKING
|--------------------------------------------------------------------------
*/

$pending = $_SESSION['pending_booking'] ?? null;


if (!$pending) {

    $_SESSION['booking_message'] =
        'Your booking session has expired. Please start again.';

    $_SESSION['booking_message_type'] = 'error';

    header('Location: booking.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFY SESSION OWNERSHIP
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

if ((int) $pending['user_id'] !== $userId) {

    unset($_SESSION['pending_booking']);

    header('Location: booking.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| EXPIRATION
|--------------------------------------------------------------------------
|
| Pending booking sessions expire after 30 minutes.
|
*/

if (
    isset($pending['created_at'])
    && time() - (int) $pending['created_at'] > 1800
) {

    unset($_SESSION['pending_booking']);

    $_SESSION['booking_message'] =
        'Your booking session expired. Please start again.';

    $_SESSION['booking_message_type'] = 'error';

    header('Location: booking.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['payment_csrf'])) {

    $_SESSION['payment_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['payment_csrf'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function paymentError($message)
{
    $_SESSION['payment_error'] = $message;

    header('Location: payment.php');
    exit;
}


function generatePaymentReference()
{
    return 'VEYRO-' .
        date('YmdHis') .
        '-' .
        strtoupper(
            bin2hex(random_bytes(4))
        );
}


/*
|--------------------------------------------------------------------------
| PAYMENT PROCESSING
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token'])
        || !hash_equals(
            $_SESSION['payment_csrf'],
            $_POST['csrf_token']
        )
    ) {

        paymentError(
            'Invalid payment request. Please try again.'
        );
    }


    $paymentMethod =
        $_POST['payment_method'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | MOCK CARD
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === 'mock_card') {

        $cardholderName =
            trim(
                $_POST['cardholder_name'] ?? ''
            );

        $cardNumber =
            preg_replace(
                '/\D/',
                '',
                $_POST['card_number'] ?? ''
            );

        $expiry =
            trim(
                $_POST['expiry'] ?? ''
            );

        $cvv =
            trim(
                $_POST['cvv'] ?? ''
            );


        /*
        | Cardholder
        */

        if (
            strlen($cardholderName) < 2
            || !preg_match(
                '/^[A-Za-z ]+$/',
                $cardholderName
            )
        ) {

            paymentError(
                'Please enter a valid cardholder name.'
            );
        }


        /*
        | Card number
        */

        if (
            strlen($cardNumber) !== 16
            || !ctype_digit($cardNumber)
        ) {

            paymentError(
                'Card number must contain exactly 16 digits.'
            );
        }


        /*
        | Luhn validation
        */

        $sum = 0;
        $length = strlen($cardNumber);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {

            $digit = (int) $cardNumber[$i];

            if ($i % 2 === $parity) {

                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        if ($sum % 10 !== 0) {

            paymentError(
                'Invalid card number.'
            );
        }


        /*
        | Expiry
        */

        if (
            !preg_match(
                '/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
                $expiry,
                $matches
            )
        ) {

            paymentError(
                'Expiry date must be in MM/YY format.'
            );
        }


        $expiryMonth =
            (int) $matches[1];

        $expiryYear =
            2000 + (int) $matches[2];


        $currentMonth =
            (int) date('m');

        $currentYear =
            (int) date('Y');


        if (
            $expiryYear < $currentYear
            || (
                $expiryYear === $currentYear
                && $expiryMonth < $currentMonth
            )
        ) {

            paymentError(
                'The card has expired.'
            );
        }


        /*
        | CVV
        */

        if (
            !preg_match(
                '/^[0-9]{3}$/',
                $cvv
            )
        ) {

            paymentError(
                'CVV must contain exactly 3 digits.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE BOOKING + PAYMENT
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            /*
            | Lock selected time slot.
            |
            | This reduces the chance of two customers taking
            | the last available slot simultaneously.
            */

            $slotStmt = $pdo->prepare("
                SELECT
                    id,
                    max_bookings
                FROM time_slots
                WHERE id = ?
                  AND status = 1
                FOR UPDATE
            ");

            $slotStmt->execute([
                $pending['time_slot_id']
            ]);

            $lockedSlot = $slotStmt->fetch();


            if (!$lockedSlot) {

                throw new Exception(
                    'The selected time slot is no longer available.'
                );
            }


            /*
            | Re-check capacity after payment page was opened.
            */

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE booking_date = ?
                  AND time_slot_id = ?
                  AND status IN (
                      'pending',
                      'booked',
                      'confirmed'
                  )
            ");

            $countStmt->execute([
                $pending['booking_date'],
                $pending['time_slot_id']
            ]);

            $bookingCount =
                (int) $countStmt->fetchColumn();


            if (
                $bookingCount
                >= (int) $lockedSlot['max_bookings']
            ) {

                throw new Exception(
                    'Sorry, this time slot has just become fully booked.'
                );
            }


            /*
            | Create booking.
            */

            $bookingStmt = $pdo->prepare("
                INSERT INTO bookings
                (
                    user_id,
                    vehicle_model,
                    license_plate,
                    vehicle_year,
                    vehicle_type,
                    booking_date,
                    time_slot_id,
                    notes,
                    total_price,
                    total_duration_minutes,
                    deposit_amount,
                    status,
                    payment_status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    'pending',
                    'partial'
                )
            ");

            $bookingStmt->execute([

                $userId,

                $pending['vehicle_model'],

                $pending['license_plate'],

                $pending['vehicle_year'],

                $pending['vehicle_type'],

                $pending['booking_date'],

                $pending['time_slot_id'],

                $pending['notes'],

                $pending['total_price'],

                $pending['total_duration_minutes'],

                $pending['deposit_amount']

            ]);


            $bookingId =
                (int) $pdo->lastInsertId();


            /*
            | Booking services.
            */

            $serviceStmt = $pdo->prepare("
                INSERT INTO booking_services
                (
                    booking_id,
                    service_id,
                    service_price,
                    service_duration_minutes
                )
                VALUES
                (?, ?, ?, ?)
            ");


            foreach (
                $pending['selected_services']
                as $service
            ) {

                $serviceStmt->execute([

                    $bookingId,

                    $service['id'],

                    $service['price'],

                    $service['duration_minutes']

                ]);
            }


            /*
            | Payment record.
            */

            $reference =
                generatePaymentReference();


            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                (
                    booking_id,
                    user_id,
                    payment_type,
                    payment_method,
                    amount,
                    reference_number,
                    card_last_four,
                    verification_status
                )
                VALUES
                (
                    ?,
                    ?,
                    'deposit',
                    'mock_card',
                    ?,
                    ?,
                    ?,
                    'confirmed'
                )
            ");


            $paymentStmt->execute([

                $bookingId,

                $userId,

                $pending['deposit_amount'],

                $reference,

                substr(
                    $cardNumber,
                    -4
                )

            ]);


            $pdo->commit();


            /*
            | Prevent reuse.
            */

            unset(
                $_SESSION['pending_booking']
            );

            unset(
                $_SESSION['payment_csrf']
            );


            /*
            | Store success information.
            */

            $_SESSION['payment_success'] = [

                'booking_id' =>
                    $bookingId,

                'reference' =>
                    $reference,

                'payment_method' =>
                    'Mock Card',

                'amount' =>
                    $pending['deposit_amount']

            ];


            header(
                'Location: payment-success.php'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            paymentError(
                $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BANK DEPOSIT
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === 'bank_deposit') {

        $bankName =
            trim(
                $_POST['bank_name'] ?? ''
            );

        $referenceNumber =
            trim(
                $_POST['reference_number'] ?? ''
            );


        if ($bankName === '') {

            paymentError(
                'Please enter the bank name.'
            );
        }


        if (
            $referenceNumber === ''
            || strlen($referenceNumber) > 100
        ) {

            paymentError(
                'Please enter a valid payment reference number.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILE
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_FILES['payment_slip'])
            || $_FILES['payment_slip']['error']
                !== UPLOAD_ERR_OK
        ) {

            paymentError(
                'Please upload your payment slip.'
            );
        }


        $file =
            $_FILES['payment_slip'];


        /*
        | 5 MB maximum
        */

        if ($file['size'] > 5 * 1024 * 1024) {

            paymentError(
                'Payment slip must not exceed 5 MB.'
            );
        }


        /*
        | MIME detection
        */

        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $mime =
            $finfo->file(
                $file['tmp_name']
            );


        $allowedMimes = [

            'image/jpeg' =>
                'jpg',

            'image/png' =>
                'png',

            'application/pdf' =>
                'pdf'

        ];


        if (
            !isset(
                $allowedMimes[$mime]
            )
        ) {

            paymentError(
                'Only JPG, JPEG, PNG and PDF files are allowed.'
            );
        }


        $extension =
            $allowedMimes[$mime];


        /*
        |--------------------------------------------------------------------------
        | CREATE UPLOAD DIRECTORY
        |--------------------------------------------------------------------------
        */

        $uploadDirectory =
            __DIR__
            . '/uploads/payment-slips/';


        if (
            !is_dir($uploadDirectory)
            && !mkdir(
                $uploadDirectory,
                0755,
                true
            )
        ) {

            paymentError(
                'Unable to create payment slip directory.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | UNIQUE FILENAME
        |--------------------------------------------------------------------------
        */

        $filename =
            'payment_'
            . date('Ymd_His')
            . '_'
            . bin2hex(
                random_bytes(6)
            )
            . '.'
            . $extension;


        $destination =
            $uploadDirectory
            . $filename;


        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            paymentError(
                'Unable to save payment slip.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATABASE
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            /*
            | Lock slot.
            */

            $slotStmt = $pdo->prepare("
                SELECT
                    id,
                    max_bookings
                FROM time_slots
                WHERE id = ?
                  AND status = 1
                FOR UPDATE
            ");

            $slotStmt->execute([
                $pending['time_slot_id']
            ]);

            $lockedSlot =
                $slotStmt->fetch();


            if (!$lockedSlot) {

                throw new Exception(
                    'The selected time slot is no longer available.'
                );
            }


            /*
            | Re-check capacity.
            */

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE booking_date = ?
                  AND time_slot_id = ?
                  AND status IN (
                      'pending',
                      'booked',
                      'confirmed'
                  )
            ");

            $countStmt->execute([

                $pending['booking_date'],

                $pending['time_slot_id']

            ]);


            $bookingCount =
                (int) $countStmt->fetchColumn();


            if (
                $bookingCount
                >= (int) $lockedSlot['max_bookings']
            ) {

                throw new Exception(
                    'Sorry, this time slot has just become fully booked.'
                );
            }


            /*
            | Bank payments remain pending.
            */

            $bookingStmt = $pdo->prepare("
                INSERT INTO bookings
                (
                    user_id,
                    vehicle_model,
                    license_plate,
                    vehicle_year,
                    vehicle_type,
                    booking_date,
                    time_slot_id,
                    notes,
                    total_price,
                    total_duration_minutes,
                    deposit_amount,
                    status,
                    payment_status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    'pending',
                    'unpaid'
                )
            ");

            $bookingStmt->execute([

                $userId,

                $pending['vehicle_model'],

                $pending['license_plate'],

                $pending['vehicle_year'],

                $pending['vehicle_type'],

                $pending['booking_date'],

                $pending['time_slot_id'],

                $pending['notes'],

                $pending['total_price'],

                $pending['total_duration_minutes'],

                $pending['deposit_amount']

            ]);


            $bookingId =
                (int) $pdo->lastInsertId();


            /*
            | Booking services.
            */

            $serviceStmt = $pdo->prepare("
                INSERT INTO booking_services
                (
                    booking_id,
                    service_id,
                    service_price,
                    service_duration_minutes
                )
                VALUES
                (?, ?, ?, ?)
            ");


            foreach (
                $pending['selected_services']
                as $service
            ) {

                $serviceStmt->execute([

                    $bookingId,

                    $service['id'],

                    $service['price'],

                    $service['duration_minutes']

                ]);
            }


            /*
            | Payment record.
            */

            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                (
                    booking_id,
                    user_id,
                    payment_type,
                    payment_method,
                    amount,
                    reference_number,
                    bank_name,
                    slip_path,
                    verification_status
                )
                VALUES
                (
                    ?,
                    ?,
                    'deposit',
                    'bank_deposit',
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending'
                )
            ");


            $paymentStmt->execute([

                $bookingId,

                $userId,

                $pending['deposit_amount'],

                $referenceNumber,

                $bankName,

                'uploads/payment-slips/' . $filename

            ]);


            $pdo->commit();


            unset(
                $_SESSION['pending_booking']
            );

            unset(
                $_SESSION['payment_csrf']
            );


            $_SESSION['payment_success'] = [

                'booking_id' =>
                    $bookingId,

                'reference' =>
                    $referenceNumber,

                'payment_method' =>
                    'Bank Deposit',

                'amount' =>
                    $pending['deposit_amount'],

                'pending_verification' =>
                    true

            ];


            header(
                'Location: payment-success.php'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
            | Delete uploaded file if DB operation fails.
            */

            if (file_exists($destination)) {
                unlink($destination);
            }

            paymentError(
                $e->getMessage()
            );
        }
    }


    paymentError(
        'Please select a valid payment method.'
    );
}


/*
|--------------------------------------------------------------------------
| SUCCESS/ERROR MESSAGE
|--------------------------------------------------------------------------
*/

$paymentError =
    $_SESSION['payment_error'] ?? '';

unset(
    $_SESSION['payment_error']
);


/*
|--------------------------------------------------------------------------
| BANK INFORMATION
|--------------------------------------------------------------------------
|
| Replace these values with your group's actual demo bank details.
|
*/

$bankDetails = [

    'bank_name' =>
        'VEYRO Commercial Bank',

    'account_name' =>
        'VEYRO Vehicle Service Centre',

    'account_number' =>
        '1234567890',

    'branch' =>
        'Colombo Main Branch'

];


$totalPrice =
    (float) $pending['total_price'];

$deposit =
    (float) $pending['deposit_amount'];

$remaining =
    max(
        0,
        $totalPrice - $deposit
    );

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Payment - VEYRO</title>

    <link rel="stylesheet" href="../includes/css/header.css">
    <link rel="stylesheet" href="../includes/css/global.css">
    <link rel="stylesheet" href="../includes/css/footer.css">
    <link rel="stylesheet" href="css/booking.css">

</head>

<body>

<?php include '../includes/header.php'; ?>


<main class="booking-page">

    <div class="container">

        <div class="booking-layout">

            <section class="booking-form-card">

                <span class="booking-label">
                    SECURE PAYMENT
                </span>

                <h1>
                    Complete Your Payment
                </h1>

                <p>
                    Pay the required booking deposit to confirm
                    your service request.
                </p>


                <?php if ($paymentError): ?>

                    <div class="booking-message error">

                        <?= htmlspecialchars(
                            $paymentError
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- BOOKING SUMMARY -->

                <div class="booking-payment-summary">

                    <h2>
                        Booking Summary
                    </h2>

                    <p>
                        <strong>Vehicle:</strong>
                        <?= htmlspecialchars(
                            $pending['vehicle_model']
                        ) ?>
                        -
                        <?= htmlspecialchars(
                            $pending['license_plate']
                        ) ?>
                    </p>

                    <p>
                        <strong>Date:</strong>
                        <?= htmlspecialchars(
                            date(
                                'd M Y',
                                strtotime(
                                    $pending['booking_date']
                                )
                            )
                        ) ?>
                    </p>

                    <p>
                        <strong>Time:</strong>
                        <?= htmlspecialchars(
                            $pending['time_slot_name']
                        ) ?>
                    </p>

                    <p>
                        <strong>Services:</strong>
                        <?php

                        $serviceNames = [];

                        foreach (
                            $pending['selected_services']
                            as $service
                        ) {

                            $serviceNames[] =
                                $service['service_name'];
                        }

                        echo htmlspecialchars(
                            implode(
                                ', ',
                                $serviceNames
                            )
                        );

                        ?>

                    </p>

                </div>


                <!-- PAYMENT TOTALS -->

                <div class="booking-payment-section">

                    <div>

                        <span>
                            Service Total
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                $totalPrice,
                                2
                            ) ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Required Deposit
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                $deposit,
                                2
                            ) ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Pay Now
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                $deposit,
                                2
                            ) ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Remaining Balance
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                $remaining,
                                2
                            ) ?>
                        </strong>

                    </div>

                </div>


                <!-- PAYMENT METHODS -->

                <div class="payment-methods">

                    <button
                        type="button"
                        class="payment-method-button active"
                        data-payment-method="card"
                    >
                        💳 Mock Card Payment
                    </button>

                    <button
                        type="button"
                        class="payment-method-button"
                        data-payment-method="bank"
                    >
                        🏦 Bank Deposit
                    </button>

                </div>


                <!-- CARD -->

                <div
                    class="payment-method-panel"
                    id="cardPaymentPanel"
                >

                    <h2>
                        Mock Card Payment
                    </h2>

                    <p>
                        This is an academic mock payment.
                        Do not enter a real card.
                    </p>

                    <form
                        method="POST"
                        action="payment.php"
                        id="cardPaymentForm"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $csrfToken
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="payment_method"
                            value="mock_card"
                        >


                        <label>
                            Cardholder Name
                        </label>

                        <input
                            type="text"
                            name="cardholder_name"
                            maxlength="100"
                            autocomplete="off"
                            required
                        >


                        <label>
                            Card Number
                        </label>

                        <input
                            type="text"
                            name="card_number"
                            maxlength="19"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="1234 5678 9012 3452"
                            required
                        >


                        <div class="payment-form-row">

                            <div>

                                <label>
                                    Expiry
                                </label>

                                <input
                                    type="text"
                                    name="expiry"
                                    maxlength="5"
                                    placeholder="MM/YY"
                                    autocomplete="off"
                                    required
                                >

                            </div>

                            <div>

                                <label>
                                    CVV
                                </label>

                                <input
                                    type="password"
                                    name="cvv"
                                    maxlength="3"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                >

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Pay Rs.
                            <?= number_format(
                                $deposit,
                                2
                            ) ?>
                        </button>

                    </form>

                </div>


                <!-- BANK -->

                <div
                    class="payment-method-panel"
                    id="bankPaymentPanel"
                    style="display:none;"
                >

                    <h2>
                        Bank Deposit / Transfer
                    </h2>


                    <div class="bank-details">

                        <p>
                            <strong>Bank:</strong>
                            <?= htmlspecialchars(
                                $bankDetails['bank_name']
                            ) ?>
                        </p>

                        <p>
                            <strong>Account Name:</strong>
                            <?= htmlspecialchars(
                                $bankDetails['account_name']
                            ) ?>
                        </p>

                        <p>
                            <strong>Account Number:</strong>
                            <?= htmlspecialchars(
                                $bankDetails['account_number']
                            ) ?>
                        </p>

                        <p>
                            <strong>Branch:</strong>
                            <?= htmlspecialchars(
                                $bankDetails['branch']
                            ) ?>
                        </p>

                        <p>
                            <strong>Amount:</strong>
                            Rs.
                            <?= number_format(
                                $deposit,
                                2
                            ) ?>
                        </p>

                    </div>


                    <form
                        method="POST"
                        action="payment.php"
                        enctype="multipart/form-data"
                        id="bankPaymentForm"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $csrfToken
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="payment_method"
                            value="bank_deposit"
                        >


                        <label>
                            Bank Name
                        </label>

                        <input
                            type="text"
                            name="bank_name"
                            maxlength="100"
                            required
                        >


                        <label>
                            Payment Reference Number
                        </label>

                        <input
                            type="text"
                            name="reference_number"
                            maxlength="100"
                            required
                        >


                        <label>
                            Payment Slip
                        </label>

                        <input
                            type="file"
                            name="payment_slip"
                            accept=".jpg,.jpeg,.png,.pdf"
                            required
                        >

                        <small>
                            JPG, JPEG, PNG or PDF.
                            Maximum 5 MB.
                        </small>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Submit Deposit Slip
                        </button>

                    </form>

                </div>

            </section>

        </div>

    </div>

</main>


<?php include '../includes/footer.php'; ?>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const buttons =
            document.querySelectorAll(
                '.payment-method-button'
            );

        const cardPanel =
            document.getElementById(
                'cardPaymentPanel'
            );

        const bankPanel =
            document.getElementById(
                'bankPaymentPanel'
            );


        buttons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        buttons.forEach(
                            function (item) {

                                item.classList.remove(
                                    'active'
                                );

                            }
                        );


                        button.classList.add(
                            'active'
                        );


                        const method =
                            button.dataset.paymentMethod;


                        if (method === 'card') {

                            cardPanel.style.display =
                                'block';

                            bankPanel.style.display =
                                'none';

                        } else {

                            cardPanel.style.display =
                                'none';

                            bankPanel.style.display =
                                'block';
                        }

                    }
                );

            }
        );


        /*
        | Card number formatting
        */

        const cardNumber =
            document.querySelector(
                'input[name="card_number"]'
            );


        if (cardNumber) {

            cardNumber.addEventListener(
                'input',
                function () {

                    let value =
                        this.value.replace(
                            /\D/g,
                            ''
                        ).substring(
                            0,
                            16
                        );


                    this.value =
                        value.replace(
                            /(.{4})/g,
                            '$1 '
                        ).trim();

                }
            );

        }


        /*
        | Expiry formatting
        */

        const expiry =
            document.querySelector(
                'input[name="expiry"]'
            );


        if (expiry) {

            expiry.addEventListener(
                'input',
                function () {

                    let value =
                        this.value.replace(
                            /\D/g,
                            ''
                        ).substring(
                            0,
                            4
                        );


                    if (value.length > 2) {

                        value =
                            value.substring(0, 2)
                            + '/'
                            + value.substring(2);

                    }

                    this.value = value;

                }
            );

        }


        /*
        | Double-submit protection
        */

        document
            .querySelectorAll('form')
            .forEach(
                function (form) {

                    form.addEventListener(
                        'submit',
                        function () {

                            const button =
                                form.querySelector(
                                    'button[type="submit"]'
                                );

                            if (button) {

                                button.disabled =
                                    true;

                                button.textContent =
                                    'Processing...';

                            }

                        }
                    );

                }
            );

    }
);

</script>

</body>
</html>