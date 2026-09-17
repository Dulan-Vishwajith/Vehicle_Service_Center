<?php

$userId = $_SESSION['user_id'] ?? 0;

$user = null;
$message = '';
$messageType = '';

/* =====================================================
   PHONE FORMAT HELPER
===================================================== */

function formatAssistantProfilePhone(string $phone): string
{
    $digits = preg_replace('/\\D/', '', $phone);

    if (preg_match('/^0[0-9]{9}$/', $digits)) {
        return substr($digits, 0, 3)
            . ' '
            . substr($digits, 3, 3)
            . ' '
            . substr($digits, 6, 4);
    }

    return trim($phone);
}

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

    $phoneDigits = preg_replace('/\D/', '', $phone);
    $formattedPhone = formatAssistantProfilePhone($phoneDigits);

    $errors = [];

    /* Full Name: letters and spaces only */
    if ($name === '') {
        $errors[] = "Full name is required.";
    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
        $errors[] = "Full name can contain letters and spaces only.";
    }

    /* Email: valid and must end exactly with .com or .lk */
    if ($email === '') {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (!preg_match('/\.(com|lk)$/i', $email)) {
        $errors[] = "Email address must end with .com or .lk.";
    }

    /* Phone: exactly 10 digits and starts with 0 */
    if ($phoneDigits === '') {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^0[0-9]{9}$/', $phoneDigits)) {
        $errors[] =
            "Phone number must contain exactly 10 digits and start with 0.";
    }

    if (!empty($errors)) {

        $message = $errors[0];
        $messageType = "error";

    } else {

        try {

            /* Prevent duplicate email or phone, excluding this assistant. */
            $stmt = $pdo->prepare("
                SELECT user_id, email, phone
                FROM users
                WHERE user_id <> ?
                AND (
                    LOWER(email) = LOWER(?)
                    OR REPLACE(phone, ' ', '') = ?
                )
                LIMIT 1
            ");

            $stmt->execute([
                $userId,
                $email,
                $phoneDigits
            ]);

            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {

                if (
                    isset($existingUser['email'])
                    && strcasecmp(
                        trim((string) $existingUser['email']),
                        $email
                    ) === 0
                ) {
                    throw new RuntimeException(
                        "This email address is already registered."
                    );
                }

                $existingPhoneDigits = preg_replace(
                    '/\D/',
                    '',
                    (string) ($existingUser['phone'] ?? '')
                );

                if ($existingPhoneDigits === $phoneDigits) {
                    throw new RuntimeException(
                        "This phone number is already registered."
                    );
                }
            }

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
                $formattedPhone,
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

            if ($e->getCode() === '23000') {
                $message =
                    "This email address or phone number is already registered.";
            } else {
                $message = "Unable to update your profile.";
            }

            $messageType = "error";

        } catch (RuntimeException $e) {

            $message = $e->getMessage();
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
                            id="assistant-profile-name"
                            name="name"
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                            maxlength="100"
                            pattern="[A-Za-z ]+"
                            title="Full name can contain letters and spaces only."
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="assistant-profile-email"
                            name="email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            placeholder="assistant@veyro.lk"
                            maxlength="150"
                            pattern="^[^@\s]+@[^@\s]+\.(com|lk)$"
                            title="Email address must end with .com or .lk"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="assistant-profile-phone"
                            name="phone"
                            value="<?= htmlspecialchars(formatAssistantProfilePhone((string) ($user['phone'] ?? ''))) ?>"
                            placeholder="071 234 5689"
                            maxlength="12"
                            inputmode="numeric"
                            autocomplete="tel"
                            title="Enter a 10-digit phone number. Example: 0712345689"
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
                        !empty($user['phone'])
                            ? formatAssistantProfilePhone((string) $user['phone'])
                            : 'Not Available'
                    ) ?>
                </strong>

            </div>


        </div>


    <?php endif; ?>


</div>

<script>
const assistantProfileName = document.getElementById('assistant-profile-name');
const assistantProfileEmail = document.getElementById('assistant-profile-email');
const assistantProfilePhone = document.getElementById('assistant-profile-phone');

/* Full Name: letters and spaces only */
if (assistantProfileName) {
    assistantProfileName.addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z ]/g, '');
    });
}

/* Phone: digits only and auto-format 0712345689 -> 071 234 5689 */
function formatAssistantProfilePhoneInput() {
    if (!assistantProfilePhone) return;

    let numbers = assistantProfilePhone.value.replace(/\D/g, '').slice(0, 10);

    if (numbers.length <= 3) {
        assistantProfilePhone.value = numbers;
    } else if (numbers.length <= 6) {
        assistantProfilePhone.value =
            numbers.slice(0, 3) + ' ' + numbers.slice(3);
    } else {
        assistantProfilePhone.value =
            numbers.slice(0, 3) + ' '
            + numbers.slice(3, 6) + ' '
            + numbers.slice(6, 10);
    }
}

function validateAssistantProfilePhone() {
    if (!assistantProfilePhone) return true;

    const numbers = assistantProfilePhone.value.replace(/\D/g, '');

    if (numbers.length !== 10 || numbers.charAt(0) !== '0') {
        assistantProfilePhone.setCustomValidity(
            'Phone number must contain exactly 10 digits and start with 0.'
        );
        return false;
    }

    assistantProfilePhone.setCustomValidity('');
    return true;
}

if (assistantProfilePhone) {
    assistantProfilePhone.addEventListener('input', function () {
        this.setCustomValidity('');
        formatAssistantProfilePhoneInput();
    });

    assistantProfilePhone.addEventListener(
        'blur',
        validateAssistantProfilePhone
    );

    formatAssistantProfilePhoneInput();
}

/* Email: must end with .com or .lk; block extra typing after the ending. */
if (assistantProfileEmail) {
    assistantProfileEmail.addEventListener('keydown', function (event) {
        const email = this.value.toLowerCase();
        const finished = email.endsWith('.com') || email.endsWith('.lk');
        const cursorAtEnd =
            this.selectionStart === this.value.length
            && this.selectionEnd === this.value.length;

        if (finished && cursorAtEnd) {
            const allowed = [
                'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight',
                'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab', 'Escape'
            ];

            if (
                !allowed.includes(event.key)
                && !event.ctrlKey
                && !event.metaKey
            ) {
                event.preventDefault();
            }
        }
    });

    /* Also remove pasted/autofilled text after a completed .com or .lk. */
    assistantProfileEmail.addEventListener('input', function () {
        this.setCustomValidity('');

        const match = this.value.match(
            /^([^@\s]+@[^@\s]*?\.(?:com|lk))/i
        );

        if (match && this.value.length > match[1].length) {
            this.value = match[1];
        }
    });
}

function validateAssistantProfileEmail() {
    if (!assistantProfileEmail) return true;

    const email = assistantProfileEmail.value.trim();
    const pattern = /^[^\s@]+@[^\s@]+\.(com|lk)$/i;

    if (!pattern.test(email)) {
        assistantProfileEmail.setCustomValidity(
            'Please enter a valid email address ending with .com or .lk.'
        );
        return false;
    }

    assistantProfileEmail.setCustomValidity('');
    return true;
}

if (assistantProfileEmail) {
    assistantProfileEmail.addEventListener(
        'blur',
        validateAssistantProfileEmail
    );
}

/* Validate the Edit Profile form before submitting. */
const assistantProfileForm =
    assistantProfileEmail ? assistantProfileEmail.closest('form') : null;

if (assistantProfileForm) {
    assistantProfileForm.addEventListener('submit', function (event) {
        if (!validateAssistantProfilePhone()) {
            event.preventDefault();
            assistantProfilePhone.reportValidity();
            return;
        }

        if (!validateAssistantProfileEmail()) {
            event.preventDefault();
            assistantProfileEmail.reportValidity();
        }
    });
}
</script>

