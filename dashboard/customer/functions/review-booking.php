<?php

$userId = (int) ($_SESSION['user_id'] ?? 0);
$roleId = (int) ($_SESSION['role_id'] ?? 0);
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$reviewError = '';
$reviewSuccess = '';
$booking = null;
$existingReview = null;

if ($userId <= 0 || $roleId !== 1) {
    $reviewError = 'You must be logged in as a customer to submit a review.';
} elseif ($bookingId <= 0) {
    $reviewError = 'Invalid booking selected.';
}

/* =====================================================
   LOAD COMPLETED BOOKING OWNED BY CUSTOMER
===================================================== */
if ($reviewError === '') {
    try {
        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.user_id,
                b.assigned_assistant_id,
                b.vehicle_model,
                b.license_plate,
                b.booking_date,
                b.status,
                u.name AS assistant_name,
                GROUP_CONCAT(
                    DISTINCT s.service_name
                    ORDER BY s.service_name
                    SEPARATOR ', '
                ) AS services
            FROM bookings b
            LEFT JOIN users u
                ON b.assigned_assistant_id = u.user_id
            LEFT JOIN booking_services bs
                ON b.id = bs.booking_id
            LEFT JOIN services s
                ON bs.service_id = s.id
            WHERE b.id = ?
              AND b.user_id = ?
              AND LOWER(b.status) = 'completed'
            GROUP BY
                b.id,
                b.user_id,
                b.assigned_assistant_id,
                b.vehicle_model,
                b.license_plate,
                b.booking_date,
                b.status,
                u.name
            LIMIT 1
        ");
        $stmt->execute([$bookingId, $userId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $reviewError = 'This booking cannot be reviewed. Only your completed bookings are eligible.';
        }
    } catch (PDOException $e) {
        $reviewError = 'Unable to load the booking information.';
    }
}

/* =====================================================
   CHECK EXISTING REVIEW
===================================================== */
if ($booking && $reviewError === '') {
    try {
        $stmt = $pdo->prepare("
            SELECT id, service_rating, assistant_rating, comment, created_at
            FROM reviews
            WHERE booking_id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingId]);
        $existingReview = $stmt->fetch();
    } catch (PDOException $e) {
        $reviewError = 'Unable to check the review status.';
    }
}

/* =====================================================
   CSRF TOKEN
===================================================== */
if (empty($_SESSION['review_csrf_token'])) {
    $_SESSION['review_csrf_token'] = bin2hex(random_bytes(32));
}

/* =====================================================
   SUBMIT REVIEW
===================================================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $booking
    && !$existingReview
    && $reviewError === ''
) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $serviceRating = filter_input(INPUT_POST, 'service_rating', FILTER_VALIDATE_INT);
    $assistantRatingRaw = $_POST['assistant_rating'] ?? null;
    $assistantRating = null;
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!hash_equals($_SESSION['review_csrf_token'], $csrfToken)) {
        $reviewError = 'Invalid request. Please refresh the page and try again.';
    } elseif ($serviceRating === false || $serviceRating < 1 || $serviceRating > 5) {
        $reviewError = 'Please select a service rating from 1 to 5 stars.';
    } elseif (mb_strlen($comment) > 1000) {
        $reviewError = 'Your feedback must be 1000 characters or less.';
    } else {
        if (!empty($booking['assigned_assistant_id'])) {
            $assistantRating = filter_var($assistantRatingRaw, FILTER_VALIDATE_INT);

            if ($assistantRating === false || $assistantRating < 1 || $assistantRating > 5) {
                $reviewError = 'Please select a Service Assistant rating from 1 to 5 stars.';
            }
        }
    }

    if ($reviewError === '') {
        try {
            /* Re-check eligibility and ownership at submit time. */
            $verifyStmt = $pdo->prepare("
                SELECT id, assigned_assistant_id
                FROM bookings
                WHERE id = ?
                  AND user_id = ?
                  AND LOWER(status) = 'completed'
                LIMIT 1
            ");
            $verifyStmt->execute([$bookingId, $userId]);
            $verifiedBooking = $verifyStmt->fetch();

            if (!$verifiedBooking) {
                $reviewError = 'This booking is no longer eligible for a review.';
            } else {
                $duplicateStmt = $pdo->prepare("
                    SELECT id
                    FROM reviews
                    WHERE booking_id = ?
                    LIMIT 1
                ");
                $duplicateStmt->execute([$bookingId]);

                if ($duplicateStmt->fetch()) {
                    $reviewError = 'You have already submitted a review for this service.';
                } else {
                    $assistantId = $verifiedBooking['assigned_assistant_id'] !== null
                        ? (int) $verifiedBooking['assigned_assistant_id']
                        : null;

                    if ($assistantId === null) {
                        $assistantRating = null;
                    }

                    $insertStmt = $pdo->prepare("
                        INSERT INTO reviews (
                            booking_id,
                            user_id,
                            service_assistant_id,
                            service_rating,
                            assistant_rating,
                            comment
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $insertStmt->execute([
                        $bookingId,
                        $userId,
                        $assistantId,
                        $serviceRating,
                        $assistantRating,
                        $comment !== '' ? $comment : null
                    ]);

                    $reviewSuccess = 'Thank you! Your review has been submitted successfully.';
                    $_SESSION['review_csrf_token'] = bin2hex(random_bytes(32));

                    $existingReview = [
                        'id' => (int) $pdo->lastInsertId(),
                        'service_rating' => $serviceRating,
                        'assistant_rating' => $assistantRating,
                        'comment' => $comment,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (PDOException $e) {
            /* Unique booking_id also protects against duplicate submissions. */
            if ($e->getCode() === '23000') {
                $reviewError = 'You have already submitted a review for this service.';
            } else {
                $reviewError = 'Unable to submit your review. Please try again.';
            }
        }
    }
}

function reviewStars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

?>

<div class="panel-header">
    <div>
        <span class="section-label">FEEDBACK</span>
        <h2>Review Completed Service</h2>
    </div>

    <a href="?page=bookings">← My Bookings</a>
</div>

<?php if ($reviewSuccess !== ''): ?>
    <div class="review-success-message">
        <?= htmlspecialchars($reviewSuccess) ?>
    </div>
<?php endif; ?>

<?php if ($reviewError !== ''): ?>
    <div class="review-error-message">
        <?= htmlspecialchars($reviewError) ?>
    </div>
<?php endif; ?>

<?php if ($booking): ?>

    <div class="review-booking-summary">
        <div>
            <span class="booking-number">BOOKING #<?= (int) $booking['id'] ?></span>
            <h3><?= htmlspecialchars($booking['vehicle_model'] ?? 'Unknown Vehicle') ?></h3>
            <p><?= htmlspecialchars($booking['license_plate'] ?? 'No License Plate') ?></p>
        </div>

        <div class="review-summary-details">
            <div>
                <span>Completed Service(s)</span>
                <strong><?= htmlspecialchars($booking['services'] ?: 'No services found') ?></strong>
            </div>

            <div>
                <span>Service Date</span>
                <strong>
                    <?= !empty($booking['booking_date'])
                        ? htmlspecialchars(date('d M Y', strtotime($booking['booking_date'])))
                        : 'N/A' ?>
                </strong>
            </div>

            <div>
                <span>Service Assistant</span>
                <strong><?= htmlspecialchars($booking['assistant_name'] ?: 'Not Assigned') ?></strong>
            </div>
        </div>
    </div>

    <?php if ($existingReview): ?>
        <div class="review-card review-existing-card">
            <div class="review-card-header">
                <div>
                    <span class="booking-number">YOUR REVIEW</span>
                    <h3>Review Submitted</h3>
                </div>
                <span class="review-submitted-badge">✓ Submitted</span>
            </div>

            <div class="review-rating-display">
                <span>Overall Service</span>
                <strong class="review-stars">
                    <?= htmlspecialchars(reviewStars((int) $existingReview['service_rating'])) ?>
                    <small><?= (int) $existingReview['service_rating'] ?>/5</small>
                </strong>
            </div>

            <?php if ($existingReview['assistant_rating'] !== null): ?>
                <div class="review-rating-display">
                    <span>Service Assistant</span>
                    <strong class="review-stars">
                        <?= htmlspecialchars(reviewStars((int) $existingReview['assistant_rating'])) ?>
                        <small><?= (int) $existingReview['assistant_rating'] ?>/5</small>
                    </strong>
                </div>
            <?php endif; ?>

            <?php if (!empty($existingReview['comment'])): ?>
                <div class="review-comment-display">
                    <span>Feedback</span>
                    <p><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
                </div>
            <?php endif; ?>

            <a class="review-view-button" href="?page=reviews">View My Reviews</a>
        </div>

    <?php elseif ($reviewError === ''): ?>

        <form method="POST" class="review-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['review_csrf_token']) ?>"
            >

            <div class="review-rating-section">
                <label class="review-field-title">Rate Our Service <span>*</span></label>
                <p>How would you rate the overall vehicle service?</p>

                <div class="star-rating" aria-label="Service rating">
                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                        <input
                            type="radio"
                            id="service-star-<?= $rating ?>"
                            name="service_rating"
                            value="<?= $rating ?>"
                            required
                        >
                        <label for="service-star-<?= $rating ?>" title="<?= $rating ?> star<?= $rating > 1 ? 's' : '' ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if (!empty($booking['assigned_assistant_id'])): ?>
                <div class="review-rating-section">
                    <label class="review-field-title">Rate Your Service Assistant <span>*</span></label>
                    <p>How would you rate <?= htmlspecialchars($booking['assistant_name'] ?: 'your Service Assistant') ?>?</p>

                    <div class="star-rating" aria-label="Service Assistant rating">
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <input
                                type="radio"
                                id="assistant-star-<?= $rating ?>"
                                name="assistant_rating"
                                value="<?= $rating ?>"
                                required
                            >
                            <label for="assistant-star-<?= $rating ?>" title="<?= $rating ?> star<?= $rating > 1 ? 's' : '' ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="review-textarea-group">
                <label for="review-comment" class="review-field-title">Share Your Experience</label>
                <textarea
                    id="review-comment"
                    class="review-textarea"
                    name="comment"
                    maxlength="1000"
                    rows="6"
                    placeholder="Tell us about your experience with the service and our Service Assistant..."
                ></textarea>
                <small>Optional · Maximum 1000 characters</small>
            </div>

            <div class="review-form-actions">
                <a href="?page=bookings" class="review-cancel-button">Cancel</a>
                <button type="submit" class="review-submit-button">Submit Review</button>
            </div>
        </form>

    <?php endif; ?>

<?php endif; ?>
