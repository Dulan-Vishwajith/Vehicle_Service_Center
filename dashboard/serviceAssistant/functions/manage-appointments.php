<?php

$appointments = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            b.id, b.vehicle_model, b.license_plate, b.booking_date,
            b.total_price, b.status,
            u.name AS customer_name,
            ts.slot_name, ts.start_time, ts.end_time,
            GROUP_CONCAT(DISTINCT s.service_name ORDER BY s.service_name SEPARATOR ', ') AS services
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.user_id
        LEFT JOIN time_slots ts ON b.time_slot_id = ts.id
        LEFT JOIN booking_services bs ON b.id = bs.booking_id
        LEFT JOIN services s ON bs.service_id = s.id
        WHERE b.assigned_assistant_id = ?
        GROUP BY
            b.id, b.vehicle_model, b.license_plate, b.booking_date, b.total_price, b.status,
            u.name, ts.slot_name, ts.start_time, ts.end_time
        ORDER BY b.booking_date ASC, ts.start_time ASC
    ");
    $stmt->execute([$assistantId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
}
?>

<div class="panel-header">
    <div>
        <span class="section-label">APPOINTMENTS</span>
        <h2>My Appointments</h2>
    </div>
</div>

<?php if (empty($appointments)): ?>
    <div class="empty-message">
        <h3>No Appointments Found</h3>
        <p>You have not confirmed any bookings yet.</p>
    </div>
<?php else: ?>
    <div class="my-bookings-list">
        <?php foreach ($appointments as $appointment): ?>
            <div class="booking-card">
                <div class="booking-card-header">
                    <div>
                        <h3>Booking #<?= (int) $appointment['id'] ?></h3>
                        <p><?= htmlspecialchars($appointment['customer_name']) ?></p>
                    </div>
                    <span class="status"><?= htmlspecialchars(ucfirst($appointment['status'])) ?></span>
                </div>
                <p><strong>Vehicle:</strong> <?= htmlspecialchars($appointment['vehicle_model']) ?> - <?= htmlspecialchars($appointment['license_plate']) ?></p>
                <p><strong>Service:</strong> <?= htmlspecialchars($appointment['services'] ?: 'Not available') ?></p>
                <p><strong>Date & Time:</strong> <?= htmlspecialchars($appointment['booking_date']) ?> | <?= htmlspecialchars($appointment['start_time'] ?: '') ?> - <?= htmlspecialchars($appointment['end_time'] ?: '') ?></p>
                <div class="booking-status-actions">
                    <a href="?page=details&id=<?= (int) $appointment['id'] ?>">View Details</a>
                    <?php if ($appointment['status'] === 'confirmed'): ?>
                        <form method="POST" action="./serviceAssistant/functions/update-booking-status.php" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= (int) $appointment['id'] ?>">
                            <input type="hidden" name="status" value="service">
                            <button type="submit">Start Service</button>
                        </form>
                    <?php elseif ($appointment['status'] === 'service'): ?>
                        <form method="POST" action="./serviceAssistant/functions/update-booking-status.php" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= (int) $appointment['id'] ?>">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit">Complete Service</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
