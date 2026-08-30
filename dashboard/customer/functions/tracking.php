<?php

/*
|--------------------------------------------------------------------------
| Customer - Service Tracking
|--------------------------------------------------------------------------
*/

function renderCustomerTracking(PDO $pdo, int $userId): void
{
    try {
        $query = "
            SELECT
                sr.service_record_id,
                sr.start_date,
                sr.end_date,
                sr.total_cost,
                sr.amount_paid,
                ss.status_name,
                v.brand,
                v.model,
                v.registration_number
            FROM service_records AS sr
            INNER JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id
            LEFT JOIN service_status AS ss
                ON sr.status_id = ss.status_id
            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id
            WHERE a.user_id = ?
            ORDER BY sr.service_record_id DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $records = [];
    }

    ?>

    <section class="card">

        <h2>
            Service Tracking & History
        </h2>

        <?php if ($records): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Vehicle</th>
                        <th>Stage</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Total</th>
                        <th>Paid</th>
                    </tr>

                    <?php foreach ($records as $record): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        ($record["brand"] ?? "") .
                                        " " .
                                        ($record["model"] ?? "")
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                                <br>
                                <small>
                                    <?= htmlspecialchars(
                                        $record["registration_number"] ?? "-",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record["status_name"] ?? "Pending",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record["start_date"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record["end_date"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                Rs.
                                <?= number_format(
                                    (float) $record["total_cost"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                Rs.
                                <?= number_format(
                                    (float) $record["amount_paid"],
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">
                No service records found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
