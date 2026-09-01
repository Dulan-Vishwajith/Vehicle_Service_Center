<?php

$userId = $_SESSION['user_id'] ?? 0;

$bookings = [];

if ($userId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            vehicle_model,
            license_plate,
            booking_date,
            status,
            total_price
        FROM bookings
        WHERE user_id = ?
        ORDER BY booking_date DESC
    ");

    $stmt->execute([$userId]);

    $bookings = $stmt->fetchAll();
}

?>

<div class="panel-header">

    <div>
        <span class="section-label">BOOKINGS</span>
        <h2>My Bookings</h2>
    </div>

</div>


<?php if (empty($bookings)): ?>

    <p class="empty-message">
        You don't have any bookings yet.
    </p>

<?php else: ?>


    <?php foreach ($bookings as $booking): ?>

        <div class="booking-row">

            <div>

                <strong>
                    <?= htmlspecialchars($booking['vehicle_model']) ?>
                </strong>

                <small>
                    Booking #<?= $booking['id'] ?>
                    ·
                    <?= htmlspecialchars($booking['license_plate']) ?>
                </small>

            </div>


            <div>

                <strong>
                    <?= date(
                        'd M Y',
                        strtotime($booking['booking_date'])
                    ) ?>
                </strong>

                <small>
                    Booking Date
                </small>

            </div>


            <div>

                <span class="status status-<?= strtolower(
                    str_replace(' ', '-', $booking['status'])
                ) ?>">

                    <?= htmlspecialchars($booking['status']) ?>

                </span>

            </div>


            <div>

                <strong>
                    Rs. <?= number_format(
                        $booking['total_price'],
                        2
                    ) ?>
                </strong>

                <small>Total</small>

            </div>

        </div>

    <?php endforeach; ?>


<?php endif; ?>