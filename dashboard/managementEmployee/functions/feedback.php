<?php

/*
|--------------------------------------------------------------------------
| Management - Customer Feedback
|--------------------------------------------------------------------------
*/

function renderManagementFeedback(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                sr.rating,
                sr.comment,
                u.name AS customer_name
            FROM service_ratings AS sr
            LEFT JOIN users AS u
                ON sr.user_id = u.user_id
            ORDER BY sr.rating_id DESC
        ";

        $stmt = $pdo->query($query);
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $feedback = [];
    }

    ?>

    <section class="card">

        <h2>
            Customer Feedback
        </h2>

        <?php if ($feedback): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                    </tr>

                    <?php foreach ($feedback as $item): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $item["customer_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $item["rating"] ?>/5
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item["comment"] ?? "",
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
                No customer feedback found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
