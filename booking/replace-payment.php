<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';


$userId =
    (int) ($_SESSION['user_id'] ?? 0);


if ($userId <= 0) {

    header('Location: ../login/login-form.php');
    exit;
}


$bookingId =
    (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);


if ($bookingId <= 0) {

    header(
        'Location: ../dashboard/dashboard.php?page=bookings'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET BOOKING
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        deposit_amount,
        total_price,
        payment_status
    FROM bookings
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $bookingId,
    $userId
]);

$booking =
    $stmt->fetch();


if (!$booking) {

    http_response_code(403);

    exit(
        'You are not allowed to access this booking.'
    );
}


/*
|--------------------------------------------------------------------------
| Only rejected payment can be replaced
|--------------------------------------------------------------------------
*/

$paymentStmt = $pdo->prepare("
    SELECT
        id,
        amount,
        verification_status
    FROM payments
    WHERE booking_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    $bookingId
]);

$payment =
    $paymentStmt->fetch();


if (
    !$payment
    || $payment['verification_status']
        !== 'rejected'
) {

    header(
        'Location: ../dashboard/dashboard.php?page=bookings'
    );

    exit;
}


$error = '';


/*
|--------------------------------------------------------------------------
| SUBMIT NEW SLIP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bankName =
        trim(
            $_POST['bank_name'] ?? ''
        );

    $reference =
        trim(
            $_POST['reference_number'] ?? ''
        );


    if ($bankName === '') {

        $error =
            'Please enter the bank name.';

    } elseif ($reference === '') {

        $error =
            'Please enter the payment reference number.';

    } elseif (
        !isset($_FILES['payment_slip'])
        || $_FILES['payment_slip']['error']
            !== UPLOAD_ERR_OK
    ) {

        $error =
            'Please upload a payment slip.';

    } else {

        $file =
            $_FILES['payment_slip'];


        if ($file['size'] > 5 * 1024 * 1024) {

            $error =
                'File size cannot exceed 5 MB.';

        } else {

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mime =
                $finfo->file(
                    $file['tmp_name']
                );


            $allowed = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'application/pdf' => 'pdf'

            ];


            if (
                !isset($allowed[$mime])
            ) {

                $error =
                    'Only JPG, JPEG, PNG and PDF files are allowed.';

            } else {

                $directory =
                    __DIR__
                    . '/uploads/payment-slips/';


                if (
                    !is_dir($directory)
                    && !mkdir(
                        $directory,
                        0755,
                        true
                    )
                ) {

                    $error =
                        'Unable to create upload directory.';

                } else {

                    $filename =
                        'payment_'
                        . date('Ymd_His')
                        . '_'
                        . bin2hex(
                            random_bytes(6)
                        )
                        . '.'
                        . $allowed[$mime];


                    $destination =
                        $directory
                        . $filename;


                    if (
                        !move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )
                    ) {

                        $error =
                            'Unable to upload payment slip.';

                    } else {

                        try {

                            $pdo->beginTransaction();


                            /*
                            | Create replacement payment.
                            */

                            $insert = $pdo->prepare("
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


                            $insert->execute([

                                $bookingId,

                                $userId,

                                $booking['deposit_amount'],

                                $reference,

                                $bankName,

                                'uploads/payment-slips/'
                                . $filename

                            ]);


                            /*
                            | Booking remains unpaid until
                            | management confirms.
                            */

                            $update = $pdo->prepare("
                                UPDATE bookings
                                SET payment_status = 'unpaid'
                                WHERE id = ?
                                AND user_id = ?
                            ");

                            $update->execute([

                                $bookingId,

                                $userId

                            ]);


                            $pdo->commit();


                            header(
                                'Location: ../dashboard/dashboard.php?page=bookings'
                            );

                            exit;


                        } catch (Throwable $e) {

                            if (
                                $pdo->inTransaction()
                            ) {

                                $pdo->rollBack();

                            }


                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );

                            }


                            $error =
                                'Unable to submit replacement payment.';
                        }
                    }
                }
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Replace Payment - VEYRO</title>

    <link rel="stylesheet" href="../includes/css/header.css">
    <link rel="stylesheet" href="../includes/css/global.css">
    <link rel="stylesheet" href="../includes/css/footer.css">
    <link rel="stylesheet" href="css/booking.css">

</head>

<body>

<?php include '../includes/header.php'; ?>


<main class="booking-page">

    <div class="container">

        <div class="booking-form-card">

            <span class="booking-label">
                PAYMENT
            </span>

            <h1>
                Upload New Payment Slip
            </h1>

            <p>
                Your previous payment was rejected.
                Please submit a new valid payment slip.
            </p>


            <?php if ($error): ?>

                <div class="booking-message error">

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <p>

                <strong>
                    Deposit Required:
                </strong>

                Rs.
                <?= number_format(
                    (float) $booking['deposit_amount'],
                    2
                ) ?>

            </p>


            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="booking_id"
                    value="<?= $bookingId ?>"
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


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Submit New Slip
                </button>

            </form>

        </div>

    </div>

</main>


<?php include '../includes/footer.php'; ?>

</body>
</html>