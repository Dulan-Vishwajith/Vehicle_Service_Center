<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| MANAGEMENT ROLE
|--------------------------------------------------------------------------
*/

$managementId =
    (int) ($_SESSION['user_id'] ?? 0);


if (
    $managementId <= 0
    || (int) ($_SESSION['role_id'] ?? 0) !== 3
) {

    echo '<div class="booking-error-message">
        You are not authorized to manage payments.
    </div>';

    return;
}


/*
|--------------------------------------------------------------------------
| MARK NOTIFICATION AS READ
|--------------------------------------------------------------------------
| The notification form also submits to this page.
| Handle it before the payment actions.
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_notification_read'])
) {

    $notificationId =
        (int) ($_POST['notification_id'] ?? 0);

    if ($notificationId > 0) {

        markNotificationAsRead(
            $pdo,
            $notificationId,
            $managementId
        );

    }

    header('Location: ?page=payments');
    exit;
}


$paymentMessage = '';
$paymentMessageType = '';


/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $paymentId =
        (int) ($_POST['payment_id'] ?? 0);

    $action =
        $_POST['payment_action'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | CONFIRM REMAINING PAYMENT
        |--------------------------------------------------------------------------
        | The customer has paid the remaining balance physically
        | at the service center.
        */

        if ($action === 'confirm_remaining') {

            $bookingId =
                (int) ($_POST['booking_id'] ?? 0);

            if ($bookingId <= 0) {

                $paymentMessage =
                    'Invalid booking.';

                $paymentMessageType =
                    'error';

            } else {

                try {

                    $pdo->beginTransaction();


                    /*
                    |--------------------------------------------------------------------------
                    | Get and lock the booking
                    |--------------------------------------------------------------------------
                    */

                    $bookingStmt = $pdo->prepare("
                        SELECT
                            id,
                            user_id,
                            total_price,
                            payment_status,
                            status
                        FROM bookings
                        WHERE id = ?
                        FOR UPDATE
                    ");

                    $bookingStmt->execute([
                        $bookingId
                    ]);

                    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);


                    if (!$booking) {

                        throw new Exception(
                            'Booking not found.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Make sure the service is ready for handover
                    |--------------------------------------------------------------------------
                    */

                    if ($booking['status'] !== 'ready_to_handover') {

                        throw new Exception(
                            'This booking is not ready for handover.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Calculate confirmed initial payments
                    |--------------------------------------------------------------------------
                    */

                    $paidStmt = $pdo->prepare("
                        SELECT
                            COALESCE(SUM(amount), 0)
                        FROM payments
                        WHERE booking_id = ?
                        AND verification_status = 'confirmed'
                        AND payment_type = 'deposit'
                    ");

                    $paidStmt->execute([
                        $bookingId
                    ]);

                    $paidAmount =
                        (float) $paidStmt->fetchColumn();


                    /*
                    |--------------------------------------------------------------------------
                    | Calculate remaining balance
                    |--------------------------------------------------------------------------
                    */

                    $totalAmount =
                        (float) $booking['total_price'];

                    $remainingAmount =
                        max(
                            0,
                            $totalAmount - $paidAmount
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Mark payment as fully paid
                    |--------------------------------------------------------------------------
                    */

                    $updateBooking = $pdo->prepare("
                        UPDATE bookings
                        SET
                            payment_status = 'paid',
                            status = 'completed'
                        WHERE id = ?
                        AND status = 'ready_to_handover'
                    ");

                    $updateBooking->execute([
                        $bookingId
                    ]);


                    if ($updateBooking->rowCount() !== 1) {

                        throw new Exception(
                            'Booking could not be completed.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CUSTOMER NOTIFICATION
                    |--------------------------------------------------------------------------
                    */

                    createNotification(
                        $pdo,
                        (int) $booking['user_id'],
                        $bookingId,
                        'payment',
                        'Remaining Payment Confirmed',
                        'Your remaining payment of Rs. '
                            . number_format($remainingAmount, 2)
                            . ' for Booking #'
                            . $bookingId
                            . ' has been confirmed. Your booking is now completed.'
                    );


                    $pdo->commit();


                    $paymentMessage =
                        'Remaining payment confirmed and booking completed successfully.';

                    $paymentMessageType =
                        'success';


                    /*
                    |--------------------------------------------------------------------------
                    | Redirect
                    |--------------------------------------------------------------------------
                    */

                    header(
                        'Location: ?page=payments&payment_success=remaining'
                    );

                    exit;


                } catch (Throwable $e) {

                    if ($pdo->inTransaction()) {

                        $pdo->rollBack();
                    }


                    $paymentMessage =
                        $e->getMessage();

                    $paymentMessageType =
                        'error';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Do not process the initial-payment action block when the
        | confirm_remaining action has already been handled above.
        |--------------------------------------------------------------------------
        */

        if ($action !== 'confirm_remaining' && (
            $paymentId <= 0
            || !in_array(
                $action,
                ['confirm', 'reject'],
                true
            )
        )) {

        $paymentMessage =
            'Invalid payment action.';

        $paymentMessageType =
            'error';

    } else {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Get payment and lock it
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    booking_id,
                    user_id,
                    amount,
                    payment_method,
                    verification_status
                FROM payments
                WHERE id = ?
                FOR UPDATE
            ");

            $stmt->execute([
                $paymentId
            ]);

            $payment =
                $stmt->fetch();


            if (!$payment) {

                throw new Exception(
                    'Payment not found.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate confirmation
            |--------------------------------------------------------------------------
            */

            if (
                $payment['verification_status']
                !== 'pending'
            ) {

                throw new Exception(
                    'This payment has already been processed.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CONFIRM
            |--------------------------------------------------------------------------
            */

            if ($action === 'confirm') {

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        verification_status = 'confirmed',
                        verified_by = ?,
                        verified_at = NOW()
                    WHERE id = ?
                      AND verification_status = 'pending'
                ");

                $stmt->execute([

                    $managementId,

                    $paymentId

                ]);


                if ($stmt->rowCount() !== 1) {

                    throw new Exception(
                        'Payment could not be confirmed.'
                    );
                }


                /*
                | Check total confirmed payments.
                */

                $sumStmt = $pdo->prepare("
                    SELECT
                        COALESCE(
                            SUM(amount),
                            0
                        )
                    FROM payments
                    WHERE booking_id = ?
                      AND verification_status = 'confirmed'
                      AND payment_type = 'deposit'
                ");

                $sumStmt->execute([
                    $payment['booking_id']
                ]);

                $paidAmount =
                    (float) $sumStmt->fetchColumn();


                /*
                | Get booking total.
                */

                $bookingStmt = $pdo->prepare("
                    SELECT
                        total_price
                    FROM bookings
                    WHERE id = ?
                    LIMIT 1
                ");

                $bookingStmt->execute([
                    $payment['booking_id']
                ]);

                $totalPrice =
                    (float) $bookingStmt->fetchColumn();


                $newPaymentStatus =
                    $paidAmount >= $totalPrice
                        ? 'paid'
                        : 'partial';


                /*
                |--------------------------------------------------------------------------
                | Update booking payment status.
                |--------------------------------------------------------------------------
                | Confirmed payment may make the booking partial or fully paid.
                */

                $updateBooking = $pdo->prepare("
                    UPDATE bookings
                    SET payment_status = ?
                    WHERE id = ?
                ");

                $updateBooking->execute([
                    $newPaymentStatus,
                    $payment['booking_id']
                ]);


                /*
                |--------------------------------------------------------------------------
                | CUSTOMER NOTIFICATION
                |--------------------------------------------------------------------------
                | Notify the customer that their payment was successfully verified.
                */

                createNotification(
                    $pdo,
                    (int) $payment['user_id'],
                    (int) $payment['booking_id'],
                    'payment',
                    'Payment Confirmed',
                    'Your payment of Rs. '
                        . number_format((float) $payment['amount'], 2)
                        . ' for Booking #'
                        . (int) $payment['booking_id']
                        . ' has been confirmed.'
                );


                $paymentMessage =
                    'Payment confirmed successfully.';

                $paymentMessageType =
                    'success';
            }


            /*
            |--------------------------------------------------------------------------
            | REJECT
            |--------------------------------------------------------------------------
            */

            if ($action === 'reject') {

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        verification_status = 'rejected',
                        verified_by = ?,
                        verified_at = NOW()
                    WHERE id = ?
                      AND verification_status = 'pending'
                ");

                $stmt->execute([

                    $managementId,

                    $paymentId

                ]);


                if ($stmt->rowCount() !== 1) {

                    throw new Exception(
                        'Payment could not be rejected.'
                    );
                }


                /*
                | Keep booking unpaid.
                */

                $updateBooking = $pdo->prepare("
                    UPDATE bookings
                    SET payment_status = 'unpaid'
                    WHERE id = ?
                ");

                $updateBooking->execute([
                    $payment['booking_id']
                ]);


                /*
                |--------------------------------------------------------------------------
                | CUSTOMER NOTIFICATION
                |--------------------------------------------------------------------------
                | Notify the customer that the payment was rejected.
                */

                createNotification(
                    $pdo,
                    (int) $payment['user_id'],
                    (int) $payment['booking_id'],
                    'payment',
                    'Payment Rejected',
                    'Your payment of Rs. '
                        . number_format((float) $payment['amount'], 2)
                        . ' for Booking #'
                        . (int) $payment['booking_id']
                        . ' was rejected. Please submit a new payment or replacement slip.'
                );


                $paymentMessage =
                    'Payment rejected. Customer can upload a replacement slip.';

                $paymentMessageType =
                    'success';
            }


            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | AFTER ACCEPTING PAYMENT
            |--------------------------------------------------------------------------
            | Once a pending payment is accepted, send management directly to
            | Confirm & Assign Bookings so the booking can be confirmed and an
            | assistant can be assigned. Rejected payments stay on this page.
            */
            if ($action === 'confirm') {
                header('Location: ?page=bookings');
                exit;
            }


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $paymentMessage =
                $e->getMessage();

            $paymentMessageType =
                'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$filter =
    $_GET['filter'] ?? 'pending';


$allowedFilters = [

    'pending',

    'confirmed',

    'rejected',

    'all'

];


if (
    !in_array(
        $filter,
        $allowedFilters,
        true
    )
) {

    $filter = 'pending';
}







/*
|--------------------------------------------------------------------------
| READY TO HANDOVER BOOKINGS
|--------------------------------------------------------------------------
| These bookings have completed the vehicle service.
| Management must collect the remaining balance physically
| at the service center before completing the booking.
*/

$readyToHandoverBookings = [];

try {

    $readyStmt = $pdo->prepare("
        SELECT
            b.id,
            b.user_id,
            b.vehicle_model,
            b.license_plate,
            b.total_price,
            u.name AS customer_name,

            COALESCE((
                SELECT SUM(p.amount)
                FROM payments p
                WHERE p.booking_id = b.id
                  AND p.verification_status = 'confirmed'
                  AND p.payment_type = 'deposit'
            ), 0) AS paid_amount

        FROM bookings b

        INNER JOIN users u
            ON b.user_id = u.user_id

        WHERE b.status = 'ready_to_handover'

        ORDER BY b.id DESC
    ");

    $readyStmt->execute();

    $readyToHandoverBookings = $readyStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $readyToHandoverBookings = [];

}






/*
|--------------------------------------------------------------------------
| LOAD PAYMENTS
|--------------------------------------------------------------------------
*/

$payments = [];


try {

    $where = '';

    if ($filter !== 'all') {

        $where =
            'WHERE p.verification_status = :status';

    }


    $sql = "
        SELECT

            p.id,
            p.booking_id,
            p.amount,
            p.payment_method,
            p.reference_number,
            p.bank_name,
            p.slip_path,
            p.verification_status,
            p.created_at,

            u.name AS customer_name,

            b.vehicle_model,
            b.license_plate

        FROM payments p

        INNER JOIN users u
            ON p.user_id = u.user_id

        INNER JOIN bookings b
            ON p.booking_id = b.id

        $where

        ORDER BY
            p.created_at DESC
    ";


    $stmt =
        $pdo->prepare($sql);


    if ($filter !== 'all') {

        $stmt->execute([
            'status' => $filter
        ]);

    } else {

        $stmt->execute();

    }


    $payments =
        $stmt->fetchAll();


} catch (PDOException $e) {

    $paymentMessage =
        'Unable to load payments.';

    $paymentMessageType =
        'error';
}

?>



<div class="panel-header">

    <div>

        <span class="section-label">
            PAYMENTS
        </span>

        <h2>
            Manage Payments
        </h2>

    </div>

</div>


<?php if ($paymentMessage): ?>

    <div class="booking-<?= 
        $paymentMessageType === 'success'
            ? 'success'
            : 'error'
    ?>-message">

        <?= htmlspecialchars(
            $paymentMessage
        ) ?>

    </div>

<?php endif; ?>




<?php if (!empty($readyToHandoverBookings)): ?>

    <section class="ready-handover-section">

        <div class="ready-handover-header">
            <div>
                <span class="section-label">VEHICLE HANDOVER</span>

                <h2>Ready for Handover</h2>

                <p>
                    These vehicles have completed their service.
                    Collect the remaining balance physically before completing the booking.
                </p>
            </div>

            <span class="ready-handover-count">
                <?= count($readyToHandoverBookings) ?>
                <?= count($readyToHandoverBookings) === 1 ? 'Booking' : 'Bookings' ?>
            </span>
        </div>


        <div class="ready-handover-grid">

            <?php foreach ($readyToHandoverBookings as $readyBooking): ?>

                <?php
                    $totalAmount = (float) ($readyBooking['total_price'] ?? 0);
                    $paidAmount = (float) ($readyBooking['paid_amount'] ?? 0);

                    $remainingAmount = max(
                        0,
                        $totalAmount - $paidAmount
                    );
                ?>

                <div class="ready-handover-card">

                    <div class="ready-handover-card-header">

                        <div>
                            <h3>
                                Booking #<?= (int) $readyBooking['id'] ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $readyBooking['customer_name'] ?? ''
                                ) ?>
                            </p>
                        </div>

                        <span class="ready-handover-status">
                            Ready to Handover
                        </span>

                    </div>


                    <div class="ready-handover-details">

                        <div class="handover-detail-row">
                            <strong>Vehicle:</strong>

                            <span>
                                <?= htmlspecialchars(
                                    $readyBooking['vehicle_model'] ?? ''
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $readyBooking['license_plate'] ?? ''
                                ) ?>
                            </span>
                        </div>


                        <div class="handover-detail-row">
                            <strong>Total Service Cost:</strong>

                            <span>
                                Rs.
                                <?= number_format(
                                    $totalAmount,
                                    2
                                ) ?>
                            </span>
                        </div>


                        <div class="handover-detail-row">
                            <strong>Confirmed Payment:</strong>

                            <span>
                                Rs.
                                <?= number_format(
                                    $paidAmount,
                                    2
                                ) ?>
                            </span>
                        </div>


                        <div class="handover-detail-row remaining-balance">

                            <strong>Remaining Balance:</strong>

                            <span>
                                Rs.
                                <?= number_format(
                                    $remainingAmount,
                                    2
                                ) ?>
                            </span>

                        </div>

                    </div>


                    <div class="ready-handover-action">

                        <form
                            method="POST"
                            onsubmit="return confirm('Are you sure the remaining payment has been received and this booking is ready to be completed?');"
                        >
                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= (int) $readyBooking['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="payment_action"
                                value="confirm_remaining"
                            >

                            <button
                                type="submit"
                                class="ready-payment-button"
                            >
                                Confirm Remaining Payment
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

<?php endif; ?>




<div class="payment-filter-links">

    <a href="?page=payments&filter=pending">
        Pending Verification
    </a>

    <a href="?page=payments&filter=confirmed">
        Confirmed
    </a>

    <a href="?page=payments&filter=rejected">
        Rejected
    </a>

    <a href="?page=payments&filter=all">
        All Payments
    </a>

</div>



<div class="booking-flow-guide">

    <div class="booking-flow-item">
        <span>1</span>

        <div>
            <strong>Payment Accepted</strong>

            <small>
                Initial deposit/payment verified
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>2</span>

        <div>
            <strong>Confirm Booking</strong>

            <small>
                Management approves the appointment
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>3</span>

        <div>
            <strong>Assign Assistant</strong>

            <small>
                Select the responsible assistant
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>4</span>

        <div>
            <strong>Service</strong>

            <small>
                Assistant completes the vehicle service
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>5</span>

        <div>
            <strong>Ready to Handover</strong>

            <small>
                Vehicle service is completed
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>6</span>

        <div>
            <strong>Remaining Payment</strong>

            <small>
                Management confirms final payment
            </small>
        </div>
    </div>


    <div class="booking-flow-arrow">→</div>


    <div class="booking-flow-item">
        <span>7</span>

        <div>
            <strong>Completed</strong>

            <small>
                Booking is fully completed
            </small>
        </div>
    </div>

</div>



<?php if (empty($payments)): ?>

    <div class="empty-message">

        <h3>
            No Payments Found
        </h3>

    </div>

<?php else: ?>

    <div class="ops-table payments-table">

        <div class="ops-table-heading">

            <span>
                Payment
            </span>

            <span>
                Booking
            </span>

            <span>
                Customer
            </span>

            <span>
                Method
            </span>

            <span>
                Amount
            </span>

            <span>
                Status
            </span>

            <span>
                Actions
            </span>

        </div>


        <?php foreach ($payments as $payment): ?>

            <div class="ops-table-row">

                <span>
                    #<?= (int) $payment['id'] ?>
                </span>


                <span>
                    #<?= (int) $payment['booking_id'] ?>
                </span>


                <span>

                    <?= htmlspecialchars(
                        $payment['customer_name']
                    ) ?>

                    <small>

                        <?= htmlspecialchars(
                            $payment['vehicle_model']
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $payment['license_plate']
                        ) ?>

                    </small>

                </span>


                <span>

                    <?= htmlspecialchars(
                        ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $payment['payment_method']
                            )
                        )
                    ) ?>

                </span>


                <span>

                    Rs.
                    <?= number_format(
                        (float) $payment['amount'],
                        2
                    ) ?>

                </span>


                <span>

                    <?= htmlspecialchars(
                        ucfirst(
                            $payment['verification_status']
                        )
                    ) ?>

                </span>


                <span>

                    <?php
                    // Generate the public URL for the payment slip if it exists.
                    $slipPath = trim($payment['slip_path'] ?? '');

                    // Check if the slip path is not empty and the file exists.
                    if ($slipPath !== '') {

                        // Convert stored payment-slip path to the correct public URL.
                        $slipFileName = basename(str_replace('\\', '/', $slipPath));

                        //
                        $slipUrl =
                            '/Vehicle_Service_Center/booking/uploads/payment-slips/'
                            . rawurlencode($slipFileName);
                    ?>
                        <a
                            href="<?= htmlspecialchars($slipUrl) ?>"
                            target="_blank"
                            rel="noopener"
                            class="ops-view-link"
                        >
                            View Slip
                        </a>
                    <?php } ?>


                    <?php if (
                        $payment['verification_status']
                        === 'pending'
                    ): ?>

                        <form
                            method="POST"
                            style="display:inline;"
                        >

                            <input
                                type="hidden"
                                name="payment_id"
                                value="<?= (int) $payment['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="payment_action"
                                value="confirm"
                            >

                            <button type="submit">
                                Confirm
                            </button>

                        </form>


                        <form
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Reject this payment?');"
                        >

                            <input
                                type="hidden"
                                name="payment_id"
                                value="<?= (int) $payment['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="payment_action"
                                value="reject"
                            >

                            <button type="submit">
                                Reject
                            </button>

                        </form>

                    <?php endif; ?>

                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>