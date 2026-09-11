<?php

$userId = (int) ($_SESSION['user_id'] ?? 0);
$reviews = [];
$reviewsError = '';

try {
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.booking_id,
            r.service_rating,
            r.assistant_rating,
            r.comment,
            r.created_at,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            u.name AS assistant_name,
            GROUP_CONCAT(
                DISTINCT s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS services
        FROM reviews r
        INNER JOIN bookings b
            ON r.booking_id = b.id
           AND b.user_id = r.user_id
        LEFT JOIN users u
            ON r.service_assistant_id = u.user_id
        LEFT JOIN booking_services bs
            ON b.id = bs.booking_id
        LEFT JOIN services s
            ON bs.service_id = s.id
        WHERE r.user_id = ?
        GROUP BY
            r.id,
            r.booking_id,
            r.service_rating,
            r.assistant_rating,
            r.comment,
            r.created_at,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            u.name
        ORDER BY r.created_at DESC
    ");

    $stmt->execute([$userId]);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviewsError = 'Unable to load your reviews.';
}

function myReviewStars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

?>

<div class="panel-header">
    <div>
        <span class="section-label">FEEDBACK</span>
        <h2>My Reviews</h2>
    </div>
</div>

<?php if ($reviewsError !== ''): ?>
    <div class="review-error-message">
        <?= htmlspecialchars($reviewsError) ?>
    </div>
<?php endif; ?>

<?php if (empty($reviews) && $reviewsError === ''): ?>
    <div class="empty-message">
        <h3>No Reviews Yet</h3>
        <p>You haven't submitted any reviews yet.</p>
    </div>
<?php else: ?>
    <div class="my-reviews-list">
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-card-header">
                    <div>
                        <span class="booking-number">BOOKING #<?= (int) $review['booking_id'] ?></span>
                        <h3><?= htmlspecialchars($review['vehicle_model'] ?: 'Unknown Vehicle') ?></h3>
                        <p><?= htmlspecialchars($review['license_plate'] ?: 'No License Plate') ?></p>
                    </div>

                    <span class="review-date">
                        <?= !empty($review['created_at'])
                            ? htmlspecialchars(date('d M Y', strtotime($review['created_at'])))
                            : 'N/A' ?>
                    </span>
                </div>

                <div class="review-service-name">
                    <span>Completed Service(s)</span>
                    <strong><?= htmlspecialchars($review['services'] ?: 'No services found') ?></strong>
                </div>

                <div class="review-ratings-grid">
                    <div class="review-rating-display">
                        <span>Overall Service</span>
                        <strong class="review-stars">
                            <?= htmlspecialchars(myReviewStars((int) $review['service_rating'])) ?>
                            <small><?= (int) $review['service_rating'] ?>/5</small>
                        </strong>
                    </div>

                    <?php if ($review['assistant_rating'] !== null): ?>
                        <div class="review-rating-display">
                            <span>Service Assistant</span>
                            <strong><?= htmlspecialchars($review['assistant_name'] ?: 'Service Assistant') ?></strong>
                            <div class="review-stars">
                                <?= htmlspecialchars(myReviewStars((int) $review['assistant_rating'])) ?>
                                <small><?= (int) $review['assistant_rating'] ?>/5</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="review-rating-display">
                            <span>Service Assistant</span>
                            <strong>Not Assigned</strong>
                            <small>No assistant rating was required.</small>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($review['comment'])): ?>
                    <div class="review-comment-display">
                        <span>Feedback</span>
                        <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
