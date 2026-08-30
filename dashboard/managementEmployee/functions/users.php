<?php

/*
|--------------------------------------------------------------------------
| Management - Manage Users
|--------------------------------------------------------------------------
| User management is kept read-only here because deleting or changing
| staff roles without a dedicated confirmation workflow is unsafe.
|--------------------------------------------------------------------------
*/

function renderManagementUsers(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                u.user_id,
                u.name,
                u.email,
                u.phone,
                r.role_name
            FROM users AS u
            LEFT JOIN roles AS r
                ON u.role_id = r.role_id
            ORDER BY u.user_id DESC
        ";

        $stmt = $pdo->query($query);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $users = [];
    }

    ?>

    <section class="card">

        <h2>
            Manage Users
        </h2>

        <?php if ($users): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                    </tr>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td>
                                #<?= (int) $user["user_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["phone"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["role_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">
                No users found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
