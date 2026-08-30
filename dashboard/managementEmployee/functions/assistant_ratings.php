<?php

/*
|--------------------------------------------------------------------------
| Management - Private Service Assistant Ratings
|--------------------------------------------------------------------------
*/

function renderAssistantRatings(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                ar.rating,
                ar.comment,
                c.name AS customer_name,
                a.name AS assistant_name
            FROM assistant_ratings AS ar
            LEFT JOIN users AS c
                ON ar.user_id = c.user_id
            LEFT JOIN users AS a
                ON ar.assistant_id = a.user_id
            ORDER BY ar.assistant_rating_id DESC
        ";

        $stmt = $pdo->query($query);
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $ratings = [];
    }

    ?>

    <section class="card">

        <h2>
            Private Assistant Ratings
        </h2>

        <?php if ($ratings): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Customer</th>
                        <th>Assistant</th>
                        <th>Rating</th>
                        <th>Comment</th>
                    </tr>

                    <?php foreach ($ratings as $rating): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $rating["customer_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $rating["assistant_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $rating["rating"] ?>/5
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $rating["comment"] ?? "",
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
                No assistant ratings found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
