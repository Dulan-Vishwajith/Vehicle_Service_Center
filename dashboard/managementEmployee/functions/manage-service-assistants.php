<?php

$message = '';
$type = '';

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
        $password = $_POST['password'] ?? '';

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $phone === ''
        ) {

            $message = 'Enter valid name, email and phone.';
            $type = 'error';

        } else {

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
                | UPDATE
                |--------------------------------------------------------------------------
                */

                if ($id > 0) {

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
                            $phone,
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
                            $phone,
                            $id,
                            $roleId
                        ]);
                    }

                    $message = 'Assistant updated.';
                    $type = 'success';

                }

                /*
                |--------------------------------------------------------------------------
                | CREATE
                |--------------------------------------------------------------------------
                */

                else {

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
                        $phone,
                        $roleId
                    ]);

                    $message = 'Assistant account created.';
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

    <form method="post" class="assistant-management-form">

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
                    type="text"
                    name="phone"
                    required
                    value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"
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
                                $assistant['phone']
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