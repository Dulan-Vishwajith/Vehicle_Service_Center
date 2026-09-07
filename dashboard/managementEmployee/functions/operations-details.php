<?php

/*
|--------------------------------------------------------------------------
| OPERATIONS DETAILS
|--------------------------------------------------------------------------
| Management users primarily monitor operations here.
| No status-change actions are given, per instructions.
*/

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$booking = null;
$services = [];
$detailsError = '';

if ($bookingId <= 0) {

    $detailsError = "No booking selected.";

} else {

    try {

        $stmt = $pdo->prepare("
            SELECT
                b.*,
                u.name AS customer_name,
                u.email AS customer_email,
                u.phone AS customer_phone,
                a.name AS assistant_name,
                ts.slot_name,
                ts.start_time,
                ts.end_time
            FROM bookings b
            INNER JOIN users u
                ON u.user_id = b.user_id
            LEFT JOIN users a
                ON a.user_id = b.assigned_assistant_id
            LEFT JOIN time_slots ts
                ON ts.id = b.time_slot_id
            WHERE b.id = ?
            LIMIT 1
        ");

        $stmt->execute([$bookingId]);

        $booking = $stmt->fetch();


        if (!$booking) {

            $detailsError = "This booking could not be found.";

        } else {

            $stmt = $pdo->prepare("
                SELECT
                    bs.service_price,
                    bs.service_duration_minutes,
                    s.service_name
                FROM booking_services bs
                INNER JOIN services s
                    ON s.id = bs.service_id
                WHERE bs.booking_id = ?
            ");

            $stmt->execute([$bookingId]);

            $services = $stmt->fetchAll();
        }

    } catch (PDOException $e) {

        $detailsError = "Unable to load booking details.";
    }
}


function detailsStatusClass($status)
{
    switch (strtolower((string) $status)) {
        case 'pending':
            return 'status-pending';
        case 'booked':
        case 'confirmed':
            return 'status-confirmed';
        case 'service':
            return 'status-progress';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-pending';
    }
}

?>


<div class="panel-header">
    <h2>Operation Details</h2>
    <a href="?page=operations">&larr; Back to Operations</a>
</div>


<?php if ($detailsError): ?>

    <div class="ops-error-message">
        <?= htmlspecialchars($detailsError) ?>
    </div>

<?php else: ?>

    <div class="ops-details">

        <div class="ops-details-top">

            <div>
                <span class="booking-number">BOOKING #<?= (int) $booking['id'] ?></span>
                <h3><?= htmlspecialchars($booking['vehicle_model']) ?> &middot; <?= htmlspecialchars($booking['license_plate']) ?></h3>
            </div>

            <span class="status <?= detailsStatusClass($booking['status']) ?>">
                <?= htmlspecialchars(ucfirst($booking['status'])) ?>
            </span>

        </div>


        <!-- Customer -->

        <div class="ops-details-block">

            <span class="booking-section-title">Customer</span>

            <div class="ops-details-grid">

                <div class="booking-detail-item">
                    <span>Name</span>
                    <strong><?= htmlspecialchars($booking['customer_name']) ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Email</span>
                    <strong><?= htmlspecialchars($booking['customer_email']) ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Phone</span>
                    <strong><?= htmlspecialchars($booking['customer_phone']) ?></strong>
                </div>

            </div>

        </div>


        <!-- Vehicle -->

        <div class="ops-details-block">

            <span class="booking-section-title">Vehicle</span>

            <div class="ops-details-grid">

                <div class="booking-detail-item">
                    <span>Make / Model</span>
                    <strong><?= htmlspecialchars($booking['vehicle_model']) ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Registration</span>
                    <strong><?= htmlspecialchars($booking['license_plate']) ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Year</span>
                    <strong><?= htmlspecialchars($booking['vehicle_year'] ?? '—') ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Type</span>
                    <strong><?= htmlspecialchars($booking['vehicle_type'] ?? '—') ?></strong>
                </div>

            </div>

        </div>


        <!-- Booking -->

        <div class="ops-details-block">

            <span class="booking-section-title">Booking</span>

            <div class="ops-details-grid">

                <div class="booking-detail-item">
                    <span>Date</span>
                    <strong><?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Time Slot</span>
                    <strong>
                        <?= htmlspecialchars($booking['slot_name'] ?? '—') ?>
                        <?php if ($booking['start_time']): ?>
                            (<?= htmlspecialchars(date('h:i A', strtotime($booking['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($booking['end_time']))) ?>)
                        <?php endif; ?>
                    </strong>
                </div>

                <div class="booking-detail-item">
                    <span>Assigned Assistant</span>
                    <strong><?= htmlspecialchars($booking['assistant_name'] ?? 'Unassigned') ?></strong>
                </div>

                <div class="booking-detail-item">
                    <span>Created</span>
                    <strong><?= htmlspecialchars(date('d M Y, h:i A', strtotime($booking['created_at']))) ?></strong>
                </div>

            </div>

        </div>


        <!-- Services -->

        <div class="ops-details-block">

            <span class="booking-section-title">Services</span>

            <?php if (empty($services)): ?>

                <p>No services recorded for this booking.</p>

            <?php else: ?>

                <div class="ops-services-list">

                    <?php foreach ($services as $service): ?>

                        <div class="ops-service-line">
                            <span><?= htmlspecialchars($service['service_name']) ?></span>
                            <span>Rs. <?= number_format((float) $service['service_price'], 2) ?></span>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- Notes -->

        <?php if (!empty($booking['notes'])): ?>

            <div class="ops-details-block">
                <span class="booking-section-title">Notes</span>
                <p><?= nl2br(htmlspecialchars($booking['notes'])) ?></p>
            </div>

        <?php endif; ?>


        <!-- Payment -->

        <div class="booking-payment-section">

            <div>
                <span>Deposit</span>
                <strong>Rs. <?= number_format((float) $booking['deposit_amount'], 2) ?></strong>
            </div>

            <div>
                <span>Payment Status</span>
                <strong><?= htmlspecialchars(ucfirst($booking['payment_status'])) ?></strong>
            </div>

            <div>
                <span>Total</span>
                <strong>Rs. <?= number_format((float) $booking['total_price'], 2) ?></strong>
            </div>

        </div>

    </div>

<?php endif; ?>
