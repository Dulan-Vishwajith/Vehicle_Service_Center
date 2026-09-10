<?php

$appointments = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.status,
            u.name AS customer_name,
            ts.start_time,
            ts.end_time,
            GROUP_CONCAT(
                DISTINCT s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS services
        FROM bookings b
        INNER JOIN users u
            ON b.user_id = u.user_id
        LEFT JOIN time_slots ts
            ON b.time_slot_id = ts.id
        LEFT JOIN booking_services bs
            ON b.id = bs.booking_id
        LEFT JOIN services s
            ON bs.service_id = s.id
        WHERE b.assigned_assistant_id = ?
          AND b.booking_date = CURDATE()
          AND b.status IN (
              'confirmed',
              'service',
              'vehicle_arrived',
              'service_ongoing',
              'service_done',
              'vehicle_handover'
          )
        GROUP BY
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.status,
            u.name,
            ts.start_time,
            ts.end_time
        ORDER BY ts.start_time ASC
    ");

    $stmt->execute([$assistantId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $appointments = [];
}
?>

<div class="panel-header">
    <div>
        <span class="section-label">TODAY</span>
        <h2>Today's Schedule</h2>
    </div>
</div>

<?php if (empty($appointments)): ?>

    <div class="empty-message">
        <h3>No Appointments Today</h3>
        <p>You don't have any active appointments scheduled for today.</p>
    </div>

<?php else: ?>

    <div class="my-bookings-list">

        <?php foreach ($appointments as $appointment): ?>

            <?php
            $bookingId = (int) $appointment['id'];
            $status = $appointment['status'];

            /*
             * Button configuration
             *
             * IMPORTANT:
             * Do not skip any booking status.
             */
            $nextStatus = null;
            $buttonText = null;

            switch ($status) {

                case 'confirmed':
                    $nextStatus = 'service';
                    $buttonText = 'Start Service';
                    break;

                case 'service':
                    $nextStatus = 'vehicle_arrived';
                    $buttonText = 'Vehicle Arrived';
                    break;

                case 'vehicle_arrived':
                    $nextStatus = 'service_ongoing';
                    $buttonText = 'Start Service Work';
                    break;

                case 'service_ongoing':
                    $nextStatus = 'service_done';
                    $buttonText = 'Service Done';
                    break;

                case 'service_done':
                    $nextStatus = 'vehicle_handover';
                    $buttonText = 'Hand Over Vehicle';
                    break;

                case 'vehicle_handover':
                    $nextStatus = 'completed';
                    $buttonText = 'Complete Appointment';
                    break;

                default:
                    $nextStatus = null;
                    $buttonText = null;
                    break;
            }

            /*
             * Friendly status names
             */
            $statusLabels = [
                'confirmed'        => 'Confirmed',
                'service'          => 'Service',
                'vehicle_arrived'  => 'Vehicle Arrived',
                'service_ongoing'  => 'Service Ongoing',
                'service_done'     => 'Service Done',
                'vehicle_handover' => 'Vehicle Handover',
                'completed'        => 'Completed'
            ];

            $displayStatus = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
            ?>

            <div class="booking-card">

                <div class="booking-card-header">

                    <div>
                        <h3>
                            Booking #<?= $bookingId ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $appointment['customer_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    </div>

                    <span class="status">
                        <?= htmlspecialchars(
                            $displayStatus,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </div>

                <p>
                    <strong>Vehicle:</strong>
                    <?= htmlspecialchars(
                        $appointment['vehicle_model'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $appointment['license_plate'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    <strong>Service:</strong>
                    <?= htmlspecialchars(
                        $appointment['services'] ?: 'Not available',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?= htmlspecialchars(
                        $appointment['start_time'] ?: '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $appointment['end_time'] ?: '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <div class="booking-status-actions">

                    <!-- View booking details -->
                    <a href="?page=details&id=<?= $bookingId ?>">
                        View Details
                    </a>


                    <?php if ($nextStatus !== null): ?>

                        <!-- Next status action -->
                        <form
                            method="POST"
                            action="./serviceAssistant/functions/update-booking-status.php"
                            style="display:inline;"
                            onsubmit="return confirmBookingAction(this);"
                        >

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= $bookingId ?>"
                            >

                            <input
                                type="hidden"
                                name="status"
                                value="<?= htmlspecialchars(
                                    $nextStatus,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="confirmation_message"
                                value="Are you sure you want to change Booking #<?= $bookingId ?> from <?= htmlspecialchars($displayStatus, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($statusLabels[$nextStatus] ?? ucfirst(str_replace('_', ' ', $nextStatus)), ENT_QUOTES, 'UTF-8') ?>?"
                            >

                            <button type="submit">
                                <?= htmlspecialchars(
                                    $buttonText,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </button>

                        </form>

                    <?php elseif ($status === 'completed'): ?>

                        <span class="completed-label">
                            ✓ Completed
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<script>
function confirmBookingAction(form) {

    const messageInput = form.querySelector(
        'input[name="confirmation_message"]'
    );

    if (!messageInput) {
        return true;
    }

    return window.confirm(messageInput.value);
}
</script>