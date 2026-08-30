<?php

/*
|--------------------------------------------------------------------------
| Customer - Appointments
|--------------------------------------------------------------------------
*/

function customerAppointmentAction(
    PDO $pdo,
    int $userId,
    array $data,
    string $action
): string {
    if ($action !== "book_appointment") {
        return "";
    }

    $vehicleId = (int) ($data["vehicle_id"] ?? 0);
    $serviceId = (int) ($data["service_id"] ?? 0);
    $appointmentDate = trim($data["appointment_date"] ?? "");

    if ($vehicleId <= 0 || $serviceId <= 0 || $appointmentDate === "") {
        return "Please complete all appointment fields.";
    }

    /*
     * Verify that the selected vehicle belongs to the logged-in customer.
     */
    $vehicleQuery = "
        SELECT vehicle_id
        FROM vehicles
        WHERE vehicle_id = ?
          AND user_id = ?
    ";

    $vehicleStmt = $pdo->prepare($vehicleQuery);
    $vehicleStmt->execute([$vehicleId, $userId]);

    if (!$vehicleStmt->fetchColumn()) {
        return "Invalid vehicle selected.";
    }

    /*
     * Verify that the selected service is active.
     */
    $serviceQuery = "
        SELECT id
        FROM services
        WHERE id = ?
          AND status = 1
    ";

    $serviceStmt = $pdo->prepare($serviceQuery);
    $serviceStmt->execute([$serviceId]);

    if (!$serviceStmt->fetchColumn()) {
        return "Invalid service selected.";
    }

    /*
     * Create appointment.
     */
    $appointmentQuery = "
        INSERT INTO appointments
            (user_id, vehicle_id, appointment_date, status)
        VALUES
            (?, ?, ?, ?)
    ";

    $appointmentStmt = $pdo->prepare($appointmentQuery);
    $appointmentStmt->execute([
        $userId,
        $vehicleId,
        $appointmentDate,
        "Pending"
    ]);

    $appointmentId = (int) $pdo->lastInsertId();

    /*
     * Attach selected service to the appointment.
     */
    $serviceLinkQuery = "
        INSERT INTO appointment_services
            (appointment_id, service_id)
        VALUES
            (?, ?)
    ";

    $serviceLinkStmt = $pdo->prepare($serviceLinkQuery);
    $serviceLinkStmt->execute([
        $appointmentId,
        $serviceId
    ]);

    return "Appointment booked successfully.";
}

$GLOBALS["CUSTOMER_ACTION_HANDLERS"][] = "customerAppointmentAction";

function renderCustomerAppointments(PDO $pdo, int $userId): void
{
    try {
        $vehicleQuery = "
            SELECT
                vehicle_id,
                brand,
                model,
                registration_number
            FROM vehicles
            WHERE user_id = ?
            ORDER BY vehicle_id DESC
        ";

        $vehicleStmt = $pdo->prepare($vehicleQuery);
        $vehicleStmt->execute([$userId]);
        $vehicles = $vehicleStmt->fetchAll(PDO::FETCH_ASSOC);

        $serviceQuery = "
            SELECT
                id,
                service_name
            FROM services
            WHERE status = 1
            ORDER BY service_name
        ";

        $serviceStmt = $pdo->query($serviceQuery);
        $services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

        $appointmentQuery = "
            SELECT
                a.appointment_id,
                a.appointment_date,
                a.status,
                v.brand,
                v.model,
                v.registration_number
            FROM appointments AS a
            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id
            WHERE a.user_id = ?
            ORDER BY a.appointment_date DESC
        ";

        $appointmentStmt = $pdo->prepare($appointmentQuery);
        $appointmentStmt->execute([$userId]);
        $appointments = $appointmentStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $vehicles = [];
        $services = [];
        $appointments = [];
    }

    ?>
    <section class="card">

        <div class="title-row">
            <h2>Book Service Appointment</h2>
        </div>

        <?php if ($vehicles && $services): ?>

            <form method="post" class="form-grid">

                <input
                    type="hidden"
                    name="dashboard_action"
                    value="book_appointment"
                >

                <label>
                    Vehicle

                    <select name="vehicle_id" required>

                        <option value="">
                            Select Vehicle
                        </option>

                        <?php foreach ($vehicles as $vehicle): ?>

                            <option value="<?= (int) $vehicle["vehicle_id"] ?>">
                                <?= htmlspecialchars(
                                    trim(($vehicle["brand"] ?? "") . " " . ($vehicle["model"] ?? "")),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                                -
                                <?= htmlspecialchars(
                                    $vehicle["registration_number"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

                <label>
                    Service

                    <select name="service_id" required>

                        <option value="">
                            Select Service
                        </option>

                        <?php foreach ($services as $service): ?>

                            <option value="<?= (int) $service["id"] ?>">
                                <?= htmlspecialchars(
                                    $service["service_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

                <label>
                    Appointment Date & Time

                    <input
                        type="datetime-local"
                        name="appointment_date"
                        required
                    >

                </label>

                <div>
                    <button
                        type="submit"
                        class="button"
                    >
                        Book Appointment
                    </button>
                </div>

            </form>

        <?php else: ?>

            <p class="empty">
                Add a vehicle and make sure services are available before booking.
            </p>

        <?php endif; ?>

    </section>

    <section class="card">

        <h2>My Appointments</h2>

        <?php if ($appointments): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Registration</th>
                        <th>Status</th>
                    </tr>

                    <?php foreach ($appointments as $appointment): ?>

                        <tr>

                            <td>
                                #<?= (int) $appointment["appointment_id"] ?>
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
                                    $appointment["status"] ?? "Pending",
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
