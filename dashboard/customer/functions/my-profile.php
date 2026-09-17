<?php

$userId = $_SESSION['user_id'] ?? 0;

$user = null;
$message = '';
$messageType = '';

function formatCustomerPhone(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (preg_match('/^0[0-9]{9}$/', $digits)) {
        return substr($digits,0,3).' '.substr($digits,3,3).' '.substr($digits,6,4);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $phoneDigits = preg_replace('/\D/', '', $phone);
    $formattedPhone = formatCustomerPhone($phoneDigits);
    $errors = [];

    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
        $errors[] = 'Full name can contain letters and spaces only.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (!preg_match('/\.(com|lk)$/i', $email)) {
        $errors[] = 'Email address must end with .com or .lk.';
    }

    if ($phoneDigits === '') {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^0[0-9]{9}$/', $phoneDigits)) {
        $errors[] = 'Phone number must contain exactly 10 digits and start with 0.';
    }

    if ($errors) {
        $message = $errors[0];
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, email, phone FROM users WHERE user_id <> ? AND (LOWER(email)=LOWER(?) OR REPLACE(phone, ' ', '')=?) LIMIT 1");
            $stmt->execute([$userId, $email, $phoneDigits]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if (strcasecmp(trim((string)$existing['email']), $email) === 0) {
                    throw new RuntimeException('This email address is already registered.');
                }
                if (preg_replace('/\D/', '', (string)$existing['phone']) === $phoneDigits) {
                    throw new RuntimeException('This phone number is already registered.');
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $formattedPhone, $userId]);
            $message = 'Profile updated successfully.';
            $messageType = 'success';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = $e->getCode() === '23000' ? 'This email address or phone number is already registered.' : 'Unable to update your profile.';
            $messageType = 'error';
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $messageType = 'error';
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
                            id="customer-name"
                            name="name"
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                            maxlength="100"
                            pattern="[A-Za-z ]+"
                            title="Full name can contain letters and spaces only."
                            oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '')"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="customer-email"
                            name="email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            placeholder="customer@veyro.lk"
                            maxlength="150"
                            pattern="^[^@\s]+@[^@\s]+\.(com|lk)$"
                            title="Email address must end with .com or .lk"
                            required
                        >

                    </div>


                    <div class="profile-form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="customer-phone"
                            name="phone"
                            value="<?= htmlspecialchars(formatCustomerPhone((string) ($user['phone'] ?? ''))) ?>"
                            placeholder="071 234 5689"
                            maxlength="12"
                            inputmode="numeric"
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
                        !empty($user['phone']) ? formatCustomerPhone((string) $user['phone']) : 'Not Available'
                    ) ?>
                </strong>

            </div>


        </div>


    <?php endif; ?>


</div>

<script>
const customerPhoneInput=document.getElementById('customer-phone');
if(customerPhoneInput){
 const formatPhone=()=>{let n=customerPhoneInput.value.replace(/\D/g,'').slice(0,10); customerPhoneInput.value=n.length<=3?n:n.length<=6?n.slice(0,3)+' '+n.slice(3):n.slice(0,3)+' '+n.slice(3,6)+' '+n.slice(6);};
 const validatePhone=()=>{let n=customerPhoneInput.value.replace(/\D/g,''); customerPhoneInput.setCustomValidity(n.length===10&&n[0]==='0'?'':'Phone number must contain exactly 10 digits and start with 0.'); return customerPhoneInput.checkValidity();};
 customerPhoneInput.addEventListener('input',()=>{customerPhoneInput.setCustomValidity('');formatPhone();}); customerPhoneInput.addEventListener('blur',validatePhone); formatPhone();
}
const customerEmailInput=document.getElementById('customer-email');
if(customerEmailInput){
 customerEmailInput.addEventListener('keydown',function(e){let done=/\.(com|lk)$/i.test(this.value); let atEnd=this.selectionStart===this.value.length&&this.selectionEnd===this.value.length; let allowed=['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','Tab','Escape']; if(done&&atEnd&&!allowed.includes(e.key)&&!e.ctrlKey&&!e.metaKey)e.preventDefault();});
 customerEmailInput.addEventListener('input',function(){this.setCustomValidity(''); let m=this.value.match(/^([^@\s]+@[^@\s]*?\.(?:com|lk))/i); if(m&&this.value.length>m[1].length)this.value=m[1];});
 customerEmailInput.addEventListener('blur',function(){let ok=/^[^\s@]+@[^\s@]+\.(com|lk)$/i.test(this.value.trim()); this.setCustomValidity(ok?'':'Please enter a valid email address ending with .com or .lk.');});
}
</script>
