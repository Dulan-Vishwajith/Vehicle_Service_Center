<?php

$userId = $_SESSION['user_id'] ?? 0;

$cancelSuccess = '';
$cancelError = '';
$bookings = [];


/* =====================================================
   CANCEL BOOKING
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_booking_id'])
    && $userId > 0
) {

    $bookingId = (int) $_POST['cancel_booking_id'];

    try {

        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'cancelled'
            WHERE id = ?
            AND user_id = ?
            AND LOWER(status) IN ('booked', 'pending')
        ");

        $stmt->execute([
            $bookingId,
            $userId
        ]);


        if ($stmt->rowCount() > 0) {

            $cancelSuccess = "Booking cancelled successfully.";

        } else {

            $cancelError = "This booking cannot be cancelled because it has already been confirmed.";

        }

    } catch (PDOException $e) {

        $cancelError = "Unable to cancel the booking.";

    }

}


/* =====================================================
   GET CUSTOMER BOOKINGS
===================================================== */

if ($userId > 0) {

    try {

        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.vehicle_model,
                b.license_plate,
                b.vehicle_year,
                b.vehicle_type,
                b.booking_date,
                b.notes,
                b.total_price,
                b.total_duration_minutes,
                b.deposit_amount,
                b.status,
                b.payment_status,
                r.id AS review_id,

                ts.slot_name,
                ts.start_time,
                ts.end_time,

                GROUP_CONCAT(
                    DISTINCT s.service_name
                    ORDER BY s.service_name
                    SEPARATOR ', '
                ) AS services

            FROM bookings b

            LEFT JOIN time_slots ts
                ON b.time_slot_id = ts.id

            LEFT JOIN booking_services bs
                ON b.id = bs.booking_id

            LEFT JOIN services s
                ON bs.service_id = s.id

            LEFT JOIN reviews r
                ON b.id = r.booking_id

            WHERE b.user_id = ?

            GROUP BY
                b.id,
                b.vehicle_model,
                b.license_plate,
                b.vehicle_year,
                b.vehicle_type,
                b.booking_date,
                b.notes,
                b.total_price,
                b.total_duration_minutes,
                b.deposit_amount,
                b.status,
                b.payment_status,
                r.id,
                ts.slot_name,
                ts.start_time,
                ts.end_time

            ORDER BY b.booking_date DESC
        ");

        $stmt->execute([$userId]);

        $bookings = $stmt->fetchAll();

    } catch (PDOException $e) {

        $bookings = [];

        $cancelError = "Unable to load your bookings.";

    }

}

?>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="panel-header">

    <div>

        <span class="section-label">
            BOOKINGS
        </span>

        <h2>
            My Bookings
        </h2>

    </div>

</div>


<!-- =====================================================
     SUCCESS MESSAGE
===================================================== -->

<?php if (!empty($cancelSuccess)): ?>

    <div class="booking-success-message">

        <?= htmlspecialchars($cancelSuccess) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ERROR MESSAGE
===================================================== -->

<?php if (!empty($cancelError)): ?>

    <div class="booking-error-message">

        <?= htmlspecialchars($cancelError) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     NO BOOKINGS
===================================================== -->

<?php if (empty($bookings)): ?>

    <div class="empty-message">

        <h3>
            No Bookings Found
        </h3>

        <p>
            You don't have any bookings yet.
        </p>

    </div>


<!-- =====================================================
     BOOKINGS LIST
===================================================== -->

<?php else: ?>

    <div class="my-bookings-list">


        <?php foreach ($bookings as $booking): ?>


            <?php

            /* =============================================
               STATUS SETTINGS
            ============================================= */

            $status = strtolower(
                trim($booking['status'] ?? '')
            );

            $statusClass = str_replace(
                [' ', '_'],
                '-',
                $status
            );

            $canCancel = in_array(
                $status,
                ['booked', 'pending'],
                true
            );

            ?>


            <!-- =============================================
                 BOOKING CARD
            ============================================== -->

            <div class="booking-detail-card">


                <!-- BOOKING HEADER -->

                <div class="booking-detail-header">


                    <!-- VEHICLE INFORMATION -->

                    <div>

                        <span class="booking-number">

                            BOOKING #<?= htmlspecialchars(
                                $booking['id']
                            ) ?>

                        </span>


                        <h3>

                            <?= htmlspecialchars(
                                $booking['vehicle_model']
                                ?? 'Unknown Vehicle'
                            ) ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                $booking['license_plate']
                                ?? 'No License Plate'
                            ) ?>

                        </p>

                    </div>


                    <!-- STATUS AND CANCEL BUTTON -->

                    <div class="booking-status-actions">


                        <span class="status status-<?= htmlspecialchars(
                            $statusClass
                        ) ?>">

                            <?= htmlspecialchars(
                                ucwords(
                                    str_replace(
                                        ['_', '-'],
                                        ' ',
                                        $status
                                    )
                                )
                            ) ?>

                        </span>


                        <!-- CANCEL ONLY BEFORE CONFIRMATION -->

                        <?php if ($canCancel): ?>

                            <form
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                            >

                                <input
                                    type="hidden"
                                    name="cancel_booking_id"
                                    value="<?= (int) $booking['id'] ?>"
                                >


                                <button
                                    type="submit"
                                    class="cancel-booking-btn"
                                >

                                    Cancel Booking

                                </button>

                            </form>

                        <?php endif; ?>


                    </div>


                </div>


                <!-- =============================================
                     BOOKING DETAILS
                ============================================== -->

                <div class="booking-detail-grid">


                    <!-- BOOKING DATE -->

                    <div class="booking-detail-item">

                        <span>
                            📅 Booking Date
                        </span>

                        <strong>

                            <?php if (!empty($booking['booking_date'])): ?>

                                <?= date(
                                    'd M Y',
                                    strtotime($booking['booking_date'])
                                ) ?>

                            <?php else: ?>

                                N/A

                            <?php endif; ?>

                        </strong>

                    </div>


                    <!-- TIME SLOT -->

                    <div class="booking-detail-item">

                        <span>
                            🕐 Time Slot
                        </span>

                        <strong>

                            <?php if (!empty($booking['slot_name'])): ?>

                                <?= htmlspecialchars(
                                    $booking['slot_name']
                                ) ?>


                                <?php if (
                                    !empty($booking['start_time'])
                                    && !empty($booking['end_time'])
                                ): ?>

                                    <br>

                                    <small>

                                        <?= date(
                                            'g:i A',
                                            strtotime(
                                                $booking['start_time']
                                            )
                                        ) ?>

                                        -

                                        <?= date(
                                            'g:i A',
                                            strtotime(
                                                $booking['end_time']
                                            )
                                        ) ?>

                                    </small>

                                <?php endif; ?>


                            <?php else: ?>

                                Not Assigned

                            <?php endif; ?>

                        </strong>

                    </div>


                    <!-- VEHICLE TYPE -->

                    <div class="booking-detail-item">

                        <span>
                            🚗 Vehicle Type
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $booking['vehicle_type'] ?? 'N/A'
                            ) ?>

                        </strong>

                    </div>


                    <!-- VEHICLE YEAR -->

                    <div class="booking-detail-item">

                        <span>
                            📆 Vehicle Year
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $booking['vehicle_year'] ?? 'N/A'
                            ) ?>

                        </strong>

                    </div>


                    <!-- TOTAL DURATION -->

                    <div class="booking-detail-item">

                        <span>
                            ⏱ Duration
                        </span>

                        <strong>

                            <?= !empty(
                                $booking['total_duration_minutes']
                            )

                                ? htmlspecialchars(
                                    $booking[
                                        'total_duration_minutes'
                                    ]
                                ) . ' Minutes'

                                : 'N/A'
                            ?>

                        </strong>

                    </div>


                    <!-- PAYMENT STATUS -->

                    <div class="booking-detail-item">

                        <span>
                            💳 Payment Status
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $booking['payment_status']
                                    ?? 'N/A'
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>


                <!-- =============================================
                     SELECTED SERVICES
                ============================================== -->

                <div class="booking-services-section">

                    <span class="booking-section-title">

                        Selected Services

                    </span>


                    <p>

                        <?php if (!empty($booking['services'])): ?>

                            <?= htmlspecialchars(
                                $booking['services']
                            ) ?>

                        <?php else: ?>

                            No services found.

                        <?php endif; ?>

                    </p>

                </div>



                <!-- =====================================================
                    REPLACED PARTS
                ===================================================== -->

                <?php

                $replacedParts = [];

                $replacedPartsTotal = 0;

                try {

                    $partsStmt = $pdo->prepare("
                        SELECT
                            part_name,
                            part_number,
                            quantity,
                            unit_price,
                            total_price,
                            notes,
                            created_at

                        FROM replaced_parts

                        WHERE booking_id = ?

                        ORDER BY created_at DESC
                    ");

                    $partsStmt->execute([
                        $booking['id']
                    ]);

                    $replacedParts =
                        $partsStmt->fetchAll(PDO::FETCH_ASSOC);


                    foreach ($replacedParts as $part) {

                        $replacedPartsTotal +=
                            (float) $part['total_price'];

                    }

                } catch (PDOException $e) {

                    $replacedParts = [];

                    $replacedPartsTotal = 0;

                }

                ?>


                <div class="booking-services-section">

                    <span class="booking-section-title">

                        🔧 Replaced Parts

                    </span>


                    <?php if (empty($replacedParts)): ?>

                        <p>

                            No parts have been replaced
                            during this service.

                        </p>

                    <?php else: ?>

                        <div class="replaced-parts-list">

                            <?php foreach ($replacedParts as $part): ?>

                                <div class="replaced-part-item">

                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $part['part_name']
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty($part['part_number'])
                                        ): ?>

                                            <p>

                                                Part No:
                                                <?= htmlspecialchars(
                                                    $part['part_number']
                                                ) ?>

                                            </p>

                                        <?php endif; ?>


                                        <p>

                                            Quantity:
                                            <?= (int) $part['quantity'] ?>

                                        </p>


                                        <?php if (
                                            !empty($part['notes'])
                                        ): ?>

                                            <p>

                                                <?= htmlspecialchars(
                                                    $part['notes']
                                                ) ?>

                                            </p>

                                        <?php endif; ?>

                                    </div>


                                    <strong>

                                        Rs.
                                        <?= number_format(
                                            (float) $part['total_price'],
                                            2
                                        ) ?>

                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="booking-payment-section">

                            <div>

                                <span>
                                    Replaced Parts Total
                                </span>

                                <strong>

                                    Rs.
                                    <?= number_format(
                                        $replacedPartsTotal,
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- =============================================
                     NOTES
                ============================================== -->

                <?php if (!empty($booking['notes'])): ?>

                    <div class="booking-notes-section">

                        <span class="booking-section-title">

                            Notes

                        </span>

                        <p>

                            <?= htmlspecialchars(
                                $booking['notes']
                            ) ?>

                        </p>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                    PAYMENT INFORMATION
                ============================================= -->

                    <div class="booking-payment-section">

                        <div>

                            <span>
                            Service Total
                            </span>

                            <strong>

                                Rs.
                                <?= number_format(
                                    (float) $booking['total_price'],
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
                                    (float) $booking['deposit_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | Get confirmed deposit amount
                        |--------------------------------------------------------------------------
                        */

                        $depositPaid = 0.00;

                        try {

                            $paymentStmt = $pdo->prepare("
                                SELECT COALESCE(
                                    SUM(amount),
                                    0
                                )
                                FROM payments
                                WHERE booking_id = ?
                                AND payment_type = 'deposit'
                                AND verification_status = 'confirmed'
                            ");

                            $paymentStmt->execute([
                                $booking['id']
                            ]);

                            $depositPaid =
                                (float) $paymentStmt->fetchColumn();

                        } catch (PDOException $e) {

                            $depositPaid = 0.00;
                        }


                        $remainingBalance =
                            max(
                                0,
                                (float) $booking['total_price']
                                - $depositPaid
                            );

                        ?>


                        <div>

                            <span>
                                Deposit Paid
                            </span>

                            <strong>

                                Rs.
                                <?= number_format(
                                    $depositPaid,
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
                                    $remainingBalance,
                                    2
                                ) ?>

                            </strong>

                        </div>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Payment display state
        |--------------------------------------------------------------------------
        */

        $paymentStatus =
            strtolower(
                $booking['payment_status'] ?? 'unpaid'
            );


        $verificationStatus = null;

        try {

            $verificationStmt = $pdo->prepare("
                SELECT verification_status
                FROM payments
                WHERE booking_id = ?
                ORDER BY id DESC
                LIMIT 1
            ");

            $verificationStmt->execute([
                $booking['id']
            ]);

            $verificationStatus =
                $verificationStmt->fetchColumn();

        } catch (PDOException $e) {

            $verificationStatus = null;
        }

        ?>


        <div>

            <span>
                Payment Status
            </span>

            <strong>

                <?php if (
                    $verificationStatus === 'pending'
                ): ?>

                    <span class="payment-status-badge payment-status-pending">
                        Awaiting Verification
                    </span>

                <?php elseif (
                    $verificationStatus === 'rejected'
                ): ?>

                    <span class="payment-status-badge payment-status-rejected">
                        Payment Rejected
                    </span>

                <?php elseif (
                    $paymentStatus === 'paid'
                ): ?>

                    <span class="payment-status-badge payment-status-paid">
                        Paid
                    </span>

                <?php elseif (
                    $paymentStatus === 'partial'
                ): ?>

                    <span class="payment-status-badge payment-status-partial">
                        Deposit Paid
                    </span>

                <?php else: ?>

                    <span class="payment-status-badge payment-status-unpaid">
                        Unpaid
                    </span>

                <?php endif; ?>

            </strong>

        </div>


        <?php if (
            $verificationStatus === 'rejected'
        ): ?>

            <div>

                <a
                    href="booking/replace-payment.php?booking_id=<?= (int) $booking['id'] ?>"
                    class="btn btn-primary"
                >
                    Upload New Slip
                </a>

            </div>

        <?php endif; ?>

    </div>


                <!-- =============================================
                     REVIEW ACTION
                ============================================== -->

                <?php if ($status === 'completed'): ?>

                    <div class="booking-review-section">

                        <?php if (empty($booking['review_id'])): ?>

                            <div>
                                <span class="booking-section-title">Your Experience</span>
                                <p>Your service is complete. Tell us how we did.</p>
                            </div>

                            <a
                                class="leave-review-btn"
                                href="?page=review-booking&id=<?= (int) $booking['id'] ?>"
                            >
                                ⭐ Leave a Review
                            </a>

                        <?php else: ?>

                            <div>
                                <span class="booking-section-title">Your Experience</span>
                                <p class="review-submitted-text">✓ Review Submitted</p>
                            </div>

                            <a class="view-review-btn" href="?page=reviews">
                                View Your Review
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>