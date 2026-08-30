<?php

/*
|--------------------------------------------------------------------------
| Service Assistant - Appointment Management
|--------------------------------------------------------------------------
*/

function renderAssistantAppointments(
    PDO $pdo,
    int $assistantId
): void {
    try {
        $query = "
            SELECT
                a.appointment_id,
                a.appointment_date,
                a.status,
                u.name AS customer_name,
                v.brand,
                v.model,
                v.registration_number
            FROM appointments AS a
            LEFT JOIN users AS u
                ON a.user_id = u.user_id
            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id
            ORDER BY a.appointment_date DESC
        ";

        $stmt = $pdo->query($query);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $appointments = [];
    }

    ?>

    <section class="card">

        <h2>
            Manage Appointments
        </h2>

        <?php if ($appointments): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Registration</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>

                    <?php foreach ($appointments as $appointment): ?>

                        <tr>

                            <td>
                                #<?= (int) $appointment["appointment_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $appointment["customer_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        ($appointment["brand"] ?? "") .
                                        " " .
                                        ($appointment["model"] ?? "")
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $appointment["registration_number"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $appointment["appointment_date"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $appointment["status"] ?? "-",
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
                No appointments found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
