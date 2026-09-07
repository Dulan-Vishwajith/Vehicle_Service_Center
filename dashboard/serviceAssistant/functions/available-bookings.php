<?php

$bookings = [];

try {
    $stmt = $pdo->query("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.vehicle_year,
            b.vehicle_type,
            b.booking_date,
            b.total_price,
            b.total_duration_minutes,
            b.status,
            u.name AS customer_name,
            u.phone AS customer_phone,
            GROUP_CONCAT(DISTINCT s.service_name ORDER BY s.service_name SEPARATOR ', ') AS services
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.user_id
        LEFT JOIN booking_services bs ON b.id = bs.booking_id
        LEFT JOIN services s ON bs.service_id = s.id
        WHERE b.assigned_assistant_id IS NULL
          AND b.status IN ('pending', 'booked')
        GROUP BY
            b.id, b.vehicle_model, b.license_plate, b.vehicle_year, b.vehicle_type,
            b.booking_date, b.total_price, b.total_duration_minutes, b.status,
            u.name, u.phone
        ORDER BY b.booking_date ASC, b.id ASC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
}
?>

<div class="panel-header">
    <div>
        <span class="section-label">BOOKINGS</span>
        <h2>Available Bookings</h2>
    </div>
</div>

<?php if (empty($bookings)): ?>
    <div class="empty-message">
        <h3>No Available Bookings</h3>
        <p>There are currently no pending bookings available for confirmation.</p>
    </div>
<?php else: ?>
    <div class="my-bookings-list">
        <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <div class="booking-card-header">
                    <div>
                        <h3>Booking #<?= (int) $booking['id'] ?></h3>
                        <p><?= htmlspecialchars($booking['customer_name']) ?></p>
                    </div>
                    <span class="status"><?= htmlspecialchars(ucfirst($booking['status'])) ?></span>
                </div>

                <p><strong>Vehicle:</strong> <?= htmlspecialchars($booking['vehicle_model']) ?> - <?= htmlspecialchars($booking['license_plate']) ?></p>
                <p><strong>Services:</strong> <?= htmlspecialchars($booking['services'] ?: 'Not available') ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($booking['booking_date']) ?></p>
                <p><strong>Total:</strong> Rs. <?= number_format((float) $booking['total_price'], 2) ?></p>

                <div class="booking-status-actions">
                    <a href="?page=details&id=<?= (int) $booking['id'] ?>">View Details</a>
                    <form method="POST" action="./serviceAssistant/functions/confirm-and-assign.php" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                        <button type="submit" onclick="return confirm('Confirm this booking and assign it to yourself?');">Confirm &amp; Assign to Me</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
