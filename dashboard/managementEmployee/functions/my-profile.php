<?php

$userId = $_SESSION['user_id'] ?? 0;

$user = null;
$message = '';
$messageType = '';

/* =====================================================
   GET USER
===================================================== */

if ($userId > 0) {

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        $user = null;

    }
}


/* =====================================================
   HANDLE PROFILE UPDATE
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_profile'])
) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {

        $message = "Name and email are required.";
        $messageType = "error";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $name,
                $email,
                $phone,
                $userId
            ]);

            $message = "Profile updated successfully.";
            $messageType = "success";

            $stmt = $pdo->prepare("
                SELECT *
                FROM users
                WHERE user_id = ?
                LIMIT 1
            ");

            $stmt->execute([$userId]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            $message = "Unable to update your profile.";
            $messageType = "error";

        }

    }

}


/* =====================================================
   HANDLE PASSWORD UPDATE
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['change_password'])
) {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';


    if (
        empty($currentPassword)
        || empty($newPassword)
        || empty($confirmPassword)
    ) {

        $message = "Please fill in all password fields.";
        $messageType = "error";

    } elseif (!password_verify(
        $currentPassword,
        $user['password']
    )) {

        $message = "Your current password is incorrect.";
        $messageType = "error";

    } elseif ($newPassword !== $confirmPassword) {

        $message = "New passwords do not match.";
        $messageType = "error";

    } elseif (strlen($newPassword) < 6) {

        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } else {

        try {

            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $hashedPassword,
                $userId
            ]);

            $message = "Password changed successfully.";
            $messageType = "success";

        } catch (PDOException $e) {

            $message = "Unable to change password.";
            $messageType = "error";

        }

    }

}


/* =====================================================
   VIEW MODE
===================================================== */

$view = $_GET['view'] ?? 'details';

?>


<div class="dashboard-panel profile-panel">


    <!-- =============================================
         HEADER
    ============================================== -->

    <div class="panel-header">

        <div>

            <span class="section-label">
                ACCOUNT
            </span>

            <h2>
                My Profile
            </h2>

        </div>


        <?php if ($user && $view === 'details'): ?>

            <div class="profile-actions">

                <a
                    href="?page=profile&view=edit"
                    class="profile-edit-btn"
                >
                    Edit Profile
                </a>


                <a
                    href="?page=profile&view=password"
                    class="profile-password-btn"
                >
                    Change Password
                </a>

            </div>

        <?php endif; ?>


    </div>


    <!-- =============================================
         MESSAGE
    ============================================== -->

    <?php if (!empty($message)): ?>

        <div class="profile-message <?= $messageType ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if (!$user): ?>


        <!-- =========================================
             PROFILE NOT FOUND
        ========================================== -->

        <div class="empty-message">

            <h3>
                Profile Not Found
            </h3>

            <p>
                Your profile information could not be found.
            </p>

        </div>


    <?php elseif ($view === 'edit'): ?>


        <!-- =========================================
             EDIT PROFILE
        ========================================== -->

        <div class="profile-form-container">

            <div class="profile-form-heading">

                <h3>
                    Edit Profile Details
                </h3>

                <p>
                    Update your personal account information.
                </p>

            </div>


            <form method="POST">


                <div class="profile-form-grid">


                    <div class="profile-form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                        >

                    </div>


                </div>


                <div class="profile-form-actions">

                    <a
                        href="?page=profile"
                        class="profile-cancel-btn"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        name="update_profile"
                        class="profile-save-btn"
                    >
                        Save Changes
                    </button>

                </div>


            </form>

        </div>


    <?php elseif ($view === 'password'): ?>


        <!-- =========================================
             CHANGE PASSWORD
        ========================================== -->

        <div class="profile-form-container password-container">


            <div class="profile-form-heading">

                <h3>
                    Change Password
                </h3>

                <p>
                    Keep your account secure with a strong password.
                </p>

            </div>


            <form method="POST">


                <div class="profile-password-form">


                    <div class="profile-form-group">

                        <label>
                            Current Password
                        </label>

                        <input
                            type="password"
                            name="current_password"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            required
                        >

                    </div>


                </div>


                <div class="profile-form-actions">

                    <a
                        href="?page=profile"
                        class="profile-cancel-btn"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        name="change_password"
                        class="profile-save-btn"
                    >
                        Update Password
                    </button>

                </div>


            </form>

        </div>


    <?php else: ?>


        <!-- =========================================
             PROFILE DETAILS
        ========================================== -->

        <div class="profile-details">


            <div class="profile-detail-card">

                <span class="profile-detail-label">
                    Full Name
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['name'] ?? 'Not Available'
                    ) ?>
                </strong>

            </div>


            <div class="profile-detail-card">

                <span class="profile-detail-label">
                    Email Address
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['email'] ?? 'Not Available'
                    ) ?>
                </strong>

            </div>


            <div class="profile-detail-card">

                <span class="profile-detail-label">
                    Phone Number
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['phone'] ?? 'Not Available'
                    ) ?>
                </strong>

            </div>


        </div>


    <?php endif; ?>


</div>