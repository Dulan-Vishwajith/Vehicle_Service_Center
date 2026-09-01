<?php

$userId = $_SESSION['user_id'] ?? 0;

/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
|
| Change this query depending on
| your actual reviews table structure.
|
*/

$reviews = [];

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM reviews
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$userId]);

    $reviews = $stmt->fetchAll();

} catch (PDOException $e) {

    $reviews = [];

}

?>


<div class="panel-header">

    <div>
        <span class="section-label">FEEDBACK</span>
        <h2>My Reviews</h2>
    </div>

</div>


<?php if (empty($reviews)): ?>


    <div class="empty-message">

        <h3>No Reviews Yet</h3>

        <p>
            You haven't submitted any reviews yet.
        </p>

    </div>


<?php else: ?>


    <?php foreach ($reviews as $review): ?>

        <div class="review-row">

            <div>

                <strong>
                    ⭐ <?= htmlspecialchars($review['rating']) ?>/5
                </strong>

                <p>
                    <?= htmlspecialchars($review['comment']) ?>
                </p>

            </div>

        </div>

    <?php endforeach; ?>


<?php endif; ?>