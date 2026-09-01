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
                ============================================== -->

                <div class="booking-payment-section">


                    <!-- TOTAL PRICE -->

                    <div>

                        <span>
                            Total Price
                        </span>

                        <strong>

                            Rs.
                            <?= number_format(
                                (float) (
                                    $booking['total_price'] ?? 0
                                ),
                                2
                            ) ?>

                        </strong>

                    </div>


                    <!-- DEPOSIT AMOUNT -->

                    <div>

                        <span>
                            Deposit Amount
                        </span>

                        <strong>

                            Rs.
                            <?= number_format(
                                (float) (
                                    $booking['deposit_amount'] ?? 0
                                ),
                                2
                            ) ?>

                        </strong>

                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>