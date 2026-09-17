<?php

$message = '';
$type = '';

function formatAssistantPhone(string $phone): string
{
    $digits = preg_replace('/\\D+/', '', $phone);

    if (preg_match('/^0[0-9]{9}$/', $digits)) {
        return substr($digits, 0, 3) . ' ' .
               substr($digits, 3, 3) . ' ' .
               substr($digits, 6, 4);
    }

    return trim($phone);
}

/*
|--------------------------------------------------------------------------
| HANDLE FORM ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assistant_action'])) {

    $action = $_POST['assistant_action'];
    $id = (int) ($_POST['assistant_id'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | CREATE / UPDATE ASSISTANT
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $password = $_POST['password'] ?? '';

        $errors = [];

        if ($name === '') {
            $errors[] = 'Full name is required.';
        } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
            $errors[] = 'Full name can contain letters and spaces only.';
        }

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (!preg_match('/\.(com|lk)$/i', $email)) {
            $errors[] = 'Email address must end with .com or .lk.';
        }

        if ($phoneDigits === '') {
            $errors[] = 'Phone number is required.';
        } elseif (!preg_match('/^0[0-9]{9}$/', $phoneDigits)) {
            $errors[] = 'Phone number must contain exactly 10 digits and start with 0.';
        }

        if ($errors) {

            $message = implode(' ', $errors);
            $type = 'error';

        } else {

            $formattedPhone = formatAssistantPhone($phoneDigits);

            try {

                $stmt = $pdo->prepare("
                    SELECT role_id
                    FROM roles
                    WHERE role_name = 'Service Assistant'
                    LIMIT 1
                ");

                $stmt->execute();

                $roleId = (int) $stmt->fetchColumn();

                if (!$roleId) {
                    throw new RuntimeException(
                        'Service Assistant role is missing.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | DUPLICATE EMAIL / PHONE CHECK
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT user_id, email, phone
                    FROM users
                    WHERE user_id <> ?
                    AND (
                        LOWER(email) = LOWER(?)
                        OR REPLACE(REPLACE(phone, ' ', ''), '-', '') = ?
                    )
                    LIMIT 1
                ");

                $stmt->execute([
                    $id,
                    $email,
                    $phoneDigits
                ]);

                $duplicate = $stmt->fetch();

                if ($duplicate) {

                    if (strcasecmp((string) $duplicate['email'], $email) === 0) {
                        throw new RuntimeException(
                            'This email address is already registered.'
                        );
                    }

                    $existingPhone = preg_replace(
                        '/\D+/',
                        '',
                        (string) $duplicate['phone']
                    );

                    if ($existingPhone === $phoneDigits) {
                        throw new RuntimeException(
                            'This phone number is already registered.'
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE
                |--------------------------------------------------------------------------
                */

                if ($id > 0) {

                    if ($password !== '' && strlen($password) < 6) {
                        throw new RuntimeException(
                            'Password must be at least 6 characters.'
                        );
                    }

                    if ($password !== '') {

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET
                                name = ?,
                                email = ?,
                                phone = ?,
                                password = ?
                            WHERE user_id = ?
                            AND role_id = ?
                        ");

                        $stmt->execute([
                            $name,
                            $email,
                            $formattedPhone,
                            password_hash($password, PASSWORD_DEFAULT),
                            $id,
                            $roleId
                        ]);

                    } else {

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET
                                name = ?,
                                email = ?,
                                phone = ?
                            WHERE user_id = ?
                            AND role_id = ?
                        ");

                        $stmt->execute([
                            $name,
                            $email,
                            $formattedPhone,
                            $id,
                            $roleId
                        ]);
                    }

                    $message = 'Assistant updated.';
                    $type = 'success';

                } else {

                    if (strlen($password) < 6) {
                        throw new RuntimeException(
                            'Password must be at least 6 characters.'
                        );
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            name,
                            email,
                            password,
                            phone,
                            role_id
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $formattedPhone,
                        $roleId
                    ]);

                    $message = 'Assistant account created.';
                    $type = 'success';
                }

            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {
                    $message = 'This email address or phone number is already registered.';
                } else {
                    $message = 'Unable to save the Service Assistant. Please try again.';
                }

                $type = 'error';

            } catch (RuntimeException $e) {

                $message = $e->getMessage();
                $type = 'error';

            } catch (Throwable $e) {

                $message = 'Unable to save the Service Assistant. Please try again.';
                $type = 'error';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE ASSISTANT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete' && $id > 0) {

        try {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE assigned_assistant_id = ?
            ");

            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {

                $message = 'Cannot delete an assistant with booking assignments.';
                $type = 'error';

            } else {

                $stmt = $pdo->prepare("
                    DELETE FROM users
                    WHERE user_id = ?
                    AND role_id = 2
                ");

                $stmt->execute([$id]);

                $message = 'Assistant deleted.';
                $type = 'success';
            }

        } catch (Throwable $e) {

            $message = $e->getMessage();
            $type = 'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT ASSISTANT
|--------------------------------------------------------------------------
*/

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;

if ($editId) {

    $stmt = $pdo->prepare("
        SELECT
            user_id,
            name,
            email,
            phone
        FROM users
        WHERE user_id = ?
        AND role_id = 2
    ");

    $stmt->execute([$editId]);

    $edit = $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| GET SERVICE ASSISTANTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        u.user_id,
        u.name,
        u.email,
        u.phone,

        COUNT(b.id) AS current_assignments

    FROM users u

    LEFT JOIN bookings b
        ON b.assigned_assistant_id = u.user_id
        AND b.status IN (
            'pending',
            'booked',
            'confirmed',
            'service'
        )

    WHERE u.role_id = 2

    GROUP BY u.user_id

    ORDER BY u.name
");

$assistants = $stmt->fetchAll();

?>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="panel-header">
    <h2>Manage Service Assistants</h2>
</div>


<div class="assistant-management">


    <!-- =====================================================
         SUCCESS / ERROR MESSAGE
    ====================================================== -->

    <?php if ($message): ?>

        <div class="message <?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         CREATE / UPDATE FORM
    ====================================================== -->
        <form 
            method="post" 
            class="assistant-management-form"
            autocomplete="off"
        >

            <input 
                type="hidden" 
                name="assistant_action" 
                value="save"
            >

            <input 
                type="hidden" 
                name="assistant_id" 
                value="<?= (int) ($edit['user_id'] ?? 0) ?>"
            >


            <div class="assistant-form-grid">


                <!-- Full Name -->

                <div class="assistant-form-group">

                    <label for="assistant-name">
                        Full Name
                    </label>

                    <input 
                        id="assistant-name"
                        type="text"
                        name="name"
                        autocomplete="off"
                        placeholder="Enter full name"
                        maxlength="100"
                        pattern="[A-Za-z ]+"
                        title="Full name can contain letters and spaces only."
                        required
                        value="<?= htmlspecialchars($edit['name'] ?? '') ?>"
                    >

                </div>


                <!-- Email -->

                <div class="assistant-form-group">

                    <label for="assistant-email">
                        Email
                    </label>

                    <input 
                        id="assistant-email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        placeholder="assistant@veyro.lk"
                        maxlength="150"
                        pattern="^[^@\s]+@[^@\s]+\.(com|lk)$"
                        title="Email address must end with .com or .lk."
                        required
                        value="<?= htmlspecialchars($edit['email'] ?? '') ?>"
                    >

                </div>


                <!-- Phone -->

                <div class="assistant-form-group">

                    <label for="assistant-phone">
                        Phone
                    </label>

                    <input 
                        id="assistant-phone"
                        type="tel"
                        name="phone"
                        autocomplete="off"
                        placeholder="071 234 5689"
                        maxlength="12"
                        inputmode="numeric"
                        title="Enter a 10-digit phone number starting with 0."
                        required
                        value="<?= htmlspecialchars(isset($edit['phone']) ? formatAssistantPhone((string) $edit['phone']) : '') ?>"
                    >

                </div>


                <!-- Password -->

                <div class="assistant-form-group">

                    <label for="assistant-password">

                        Password

                        <?= $edit 
                            ? '(leave empty to keep current password)' 
                            : '*' 
                        ?>

                    </label>

                    <input 
                        id="assistant-password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        <?= $edit ? '' : 'required' ?>
                    >

                </div>


            </div>


            <!-- =================================================
                FORM ACTIONS
            ================================================== -->

            <div class="assistant-form-actions">

                <button 
                    type="submit" 
                    class="btn btn-primary"
                >
                    <?= $edit 
                        ? 'Update Assistant' 
                        : 'Create Assistant' 
                    ?>
                </button>


                <?php if ($edit): ?>

                    <a 
                        class="btn assistant-cancel-btn" 
                        href="?page=assistants"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>


    <!-- =====================================================
         ASSISTANTS TABLE
    ====================================================== -->

    <div class="assistant-table-wrapper">

        <div class="assistant-table">


            <!-- TABLE HEADER -->

            <div class="assistant-table-heading">

                <div class="assistant-col-name">
                    Name
                </div>

                <div class="assistant-col-email">
                    Email
                </div>

                <div class="assistant-col-phone">
                    Phone
                </div>

                <div class="assistant-col-assignments">
                    Current Assignments
                </div>

                <div class="assistant-col-actions">
                    Actions
                </div>

            </div>


            <!-- TABLE DATA -->

            <?php if (empty($assistants)): ?>

                <div class="assistant-empty-state">
                    No service assistants found.
                </div>

            <?php else: ?>


                <?php foreach ($assistants as $assistant): ?>


                    <div class="assistant-table-row">


                        <!-- Name -->

                        <div class="assistant-col-name">

                            <?= htmlspecialchars(
                                $assistant['name']
                            ) ?>

                        </div>


                        <!-- Email -->

                        <div class="assistant-col-email">

                            <?= htmlspecialchars(
                                $assistant['email']
                            ) ?>

                        </div>


                        <!-- Phone -->

                        <div class="assistant-col-phone">

                            <?= htmlspecialchars(
                                formatAssistantPhone((string) $assistant['phone'])
                            ) ?>

                        </div>


                        <!-- Current Assignments -->

                        <div class="assistant-col-assignments">

                            <?= (int)
                                $assistant['current_assignments']
                            ?>

                        </div>


                        <!-- Actions -->

                        <div class="assistant-col-actions">


                            <a
                                href="?page=assistants&edit=<?= (int) $assistant['user_id'] ?>"
                                class="assistant-action-link"
                            >
                                Edit
                            </a>


                            <form
                                method="post"
                                class="assistant-delete-form"
                            >

                                <input
                                    type="hidden"
                                    name="assistant_action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="assistant_id"
                                    value="<?= (int) $assistant['user_id'] ?>"
                                >


                                <button
                                    type="submit"
                                    class="assistant-action-link assistant-delete-link"
                                    onclick="return confirm('Delete this assistant?')"
                                >
                                    Delete
                                </button>

                            </form>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </div>


</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('.assistant-management-form');
    const nameInput = document.getElementById('assistant-name');
    const emailInput = document.getElementById('assistant-email');
    const phoneInput = document.getElementById('assistant-phone');

    function formatPhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 10);

        if (digits.length <= 3) {
            return digits;
        }

        if (digits.length <= 6) {
            return digits.slice(0, 3) + ' ' + digits.slice(3);
        }

        return digits.slice(0, 3) + ' ' +
               digits.slice(3, 6) + ' ' +
               digits.slice(6, 10);
    }

    function validateName() {
        const value = nameInput.value.trim();

        if (!/^[A-Za-z ]+$/.test(value)) {
            nameInput.setCustomValidity(
                'Full name can contain letters and spaces only.'
            );
            return false;
        }

        nameInput.setCustomValidity('');
        return true;
    }

    function validateEmail() {
        const value = emailInput.value.trim();

        if (!/^[^\s@]+@[^\s@]+\.(com|lk)$/i.test(value)) {
            emailInput.setCustomValidity(
                'Enter a valid email address ending with .com or .lk.'
            );
            return false;
        }

        emailInput.setCustomValidity('');
        return true;
    }

    function validatePhone() {
        const digits = phoneInput.value.replace(/\D/g, '');

        if (!/^0\d{9}$/.test(digits)) {
            phoneInput.setCustomValidity(
                'Phone number must contain exactly 10 digits and start with 0.'
            );
            return false;
        }

        phoneInput.setCustomValidity('');
        return true;
    }

    nameInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z ]/g, '');
        validateName();
    });

    emailInput.addEventListener('input', function () {
        this.setCustomValidity('');

        const match = this.value.match(
            /^([^\s@]+@[^\s@]*?\.(?:com|lk))/i
        );

        if (match && this.value.length > match[1].length) {
            this.value = match[1];
        }
    });

    emailInput.addEventListener('keydown', function (event) {
        const value = this.value.toLowerCase();
        const cursorAtEnd =
            this.selectionStart === this.value.length &&
            this.selectionEnd === this.value.length;

        const allowedKeys = [
            'Backspace',
            'Delete',
            'ArrowLeft',
            'ArrowRight',
            'ArrowUp',
            'ArrowDown',
            'Home',
            'End',
            'Tab',
            'Escape'
        ];

        if (
            cursorAtEnd &&
            (value.endsWith('.com') || value.endsWith('.lk')) &&
            !allowedKeys.includes(event.key) &&
            !event.ctrlKey &&
            !event.metaKey
        ) {
            event.preventDefault();
        }
    });

    emailInput.addEventListener('blur', validateEmail);

    phoneInput.addEventListener('input', function () {
        this.value = formatPhone(this.value);
        this.setCustomValidity('');
    });

    phoneInput.addEventListener('blur', validatePhone);

    if (phoneInput.value) {
        phoneInput.value = formatPhone(phoneInput.value);
    }

    form.addEventListener('submit', function (event) {

        const validName = validateName();
        const validEmail = validateEmail();
        const validPhone = validatePhone();

        if (!validName || !validEmail || !validPhone) {
            event.preventDefault();
            form.reportValidity();
        }
    });
});
</script>
