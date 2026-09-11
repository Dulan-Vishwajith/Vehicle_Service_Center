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


    if (
        $paymentId <= 0
        || !in_array(
            $action,
            ['confirm', 'reject'],
            true
        )
    ) {

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
                | Activate payment status.
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


                $paymentMessage =
                    'Payment rejected. Customer can upload a replacement slip.';

                $paymentMessageType =
                    'success';
            }


            $pdo->commit();


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
    <div class="booking-flow-item is-done">
        <span>1</span>
        <div>
            <strong>Payment Accepted</strong>
            <small>Deposit/payment verified</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item is-active">
        <span>2</span>
        <div>
            <strong>Confirm Booking</strong>
            <small>Management approves the appointment</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item">
        <span>3</span>
        <div>
            <strong>Assign Assistant</strong>
            <small>Select the responsible assistant</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item">
        <span>4</span>
        <div>
            <strong>Confirmed</strong>
            <small>Assistant can begin the service flow</small>
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