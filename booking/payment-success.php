<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once '../config/database.php';


if (!isset($_SESSION['user_id'])) {

    header('Location: ../login/login-form.php');
    exit;
}


$success =
    $_SESSION['payment_success'] ?? null;


unset(
    $_SESSION['payment_success']
);


if (!$success) {

    header(
        'Location: ../dashboard/dashboard.php?page=bookings'
    );

    exit;
}


$bookingId =
    (int) $success['booking_id'];

$reference =
    $success['reference'] ?? '';

$paymentMethod =
    $success['payment_method'] ?? '';

$amount =
    (float) ($success['amount'] ?? 0);

$isPending =
    !empty(
        $success['pending_verification']
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

    <title>Booking Confirmation - VEYRO</title>

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

            <div class="booking-success-message">

                <?php if ($isPending): ?>

                    <h1>
                        Payment Submitted
                    </h1>

                    <p>
                        Your bank deposit slip has been
                        submitted successfully.
                    </p>

                    <p>
                        Your payment is now awaiting
                        verification by our management team.
                    </p>

                <?php else: ?>

                    <h1>
                        Booking Confirmed!
                    </h1>

                    <p>
                        Your booking and deposit payment
                        were successfully recorded.
                    </p>

                <?php endif; ?>

            </div>


            <div class="booking-payment-section">

                <div>

                    <span>
                        Booking ID
                    </span>

                    <strong>
                        #<?= $bookingId ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Payment Method
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $paymentMethod
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Amount
                    </span>

                    <strong>
                        Rs.
                        <?= number_format(
                            $amount,
                            2
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Reference
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $reference
                        ) ?>
                    </strong>

                </div>

            </div>


            <?php if ($isPending): ?>

                <p>

                    Your booking will become available
                    to the service team after the payment
                    has been verified.

                </p>

            <?php endif; ?>


            <a
                href="../dashboard/dashboard.php?page=bookings"
                class="btn btn-primary"
            >
                View My Bookings
            </a>

        </div>

    </div>

</main>


<?php include '../includes/footer.php'; ?>

</body>
</html>