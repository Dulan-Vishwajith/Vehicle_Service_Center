<?php

/*
|--------------------------------------------------------------------------
| Customer - Warranty Details
|--------------------------------------------------------------------------
| Warranty information is obtained from replaced_parts, which is the
| warranty-related table in the supplied database.
|--------------------------------------------------------------------------
*/

function renderCustomerWarranty(PDO $pdo, int $userId): void
{
    try {
        $query = "
            SELECT
                sr.service_record_id,
                a.appointment_date,
                v.brand,
                v.model,
                p.name AS part_name,
                rp.quantity,
                rp.warranty_period,
                rp.warranty_expiry_date
            FROM replaced_parts AS rp
            INNER JOIN service_records AS sr
                ON rp.service_record_id = sr.service_record_id
            INNER JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id
            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id
            LEFT JOIN parts AS p
                ON rp.part_id = p.part_id
            WHERE a.user_id = ?
            ORDER BY rp.warranty_expiry_date DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);

        $warranties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $warranties = [];
    }

    ?>

    <section class="card">

        <h2>
            Warranty Details
        </h2>

        <?php if ($warranties): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Service</th>
                        <th>Vehicle</th>
                        <th>Part</th>
                        <th>Quantity</th>
                        <th>Warranty Period</th>
                        <th>Expiry</th>
                    </tr>

                    <?php foreach ($warranties as $warranty): ?>

                        <tr>

                            <td>
                                #<?= (int) $warranty["service_record_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        ($warranty["brand"] ?? "") .
                                        " " .
                                        ($warranty["model"] ?? "")
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $warranty["part_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $warranty["quantity"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $warranty["warranty_period"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $warranty["warranty_expiry_date"] ?? "-",
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
                No warranty records found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
