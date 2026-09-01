<?php

$userId = $_SESSION['user_id'] ?? 0;

$user = null;

if ($userId > 0) {

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $user = $stmt->fetch();

    } catch (PDOException $e) {

        $user = null;

    }
}

?>


<div class="panel-header">

    <div>

        <span class="section-label">
            ACCOUNT
        </span>

        <h2>
            My Profile
        </h2>

    </div>

</div>


<?php if ($user): ?>


    <div class="profile-details">


        <div class="profile-item">

            <span>Full Name</span>

            <strong>
                <?= htmlspecialchars($user['name'] ?? 'Not Available') ?>
            </strong>

        </div>


        <div class="profile-item">

            <span>Email Address</span>

            <strong>
                <?= htmlspecialchars($user['email'] ?? 'Not Available') ?>
            </strong>

        </div>


        <div class="profile-item">

            <span>Phone Number</span>

            <strong>
                <?= htmlspecialchars($user['phone'] ?? 'Not Available') ?>
            </strong>

        </div>


    </div>


<?php else: ?>


    <div class="empty-message">

        <h3>
            Profile Not Found
        </h3>

        <p>
            Your profile information could not be found.
        </p>

    </div>


<?php endif; ?>