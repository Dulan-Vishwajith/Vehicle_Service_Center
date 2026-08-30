<?php

/*
|--------------------------------------------------------------------------
| Management - Branches
|--------------------------------------------------------------------------
*/

function renderManagementBranches(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                branch_id,
                name,
                location,
                contact_number
            FROM branches
            ORDER BY branch_id DESC
        ";

        $stmt = $pdo->query($query);
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $branches = [];
    }

    ?>

    <section class="card">

        <h2>
            Manage Branches
        </h2>

        <?php if ($branches): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                    </tr>

                    <?php foreach ($branches as $branch): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $branch["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $branch["location"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $branch["contact_number"],
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
                No branches found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
