<?php

/*
|--------------------------------------------------------------------------
| Service Assistant - Record Service Details
|--------------------------------------------------------------------------
*/

function saveServiceRecordAction(
    PDO $pdo,
    int $assistantId,
    array $data,
    string $action
): string {
    if ($action !== "save_record") {
        return "";
    }

    $appointmentId = (int) ($data["appointment_id"] ?? 0);
    $statusId = (int) ($data["status_id"] ?? 0);

    $startDate = trim($data["start_date"] ?? "");
    $endDate = trim($data["end_date"] ?? "");

    $totalCost = (float) ($data["total_cost"] ?? 0);
    $amountPaid = (float) ($data["amount_paid"] ?? 0);

    if ($appointmentId <= 0 || $statusId <= 0) {
        return "Please select an appointment and service status.";
    }

    if ($totalCost < 0 || $amountPaid < 0) {
        return "Amounts cannot be negative.";
    }

    if ($amountPaid > $totalCost) {
        return "Amount paid cannot exceed total cost.";
    }

    $query = "
        INSERT INTO service_records
            (
                appointment_id,
                assistant_id,
                status_id,
                start_date,
                end_date,
                total_cost,
                amount_paid
            )
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $appointmentId,
        $assistantId,
        $statusId,
        $startDate !== "" ? $startDate : null,
        $endDate !== "" ? $endDate : null,
        $totalCost,
        $amountPaid
    ]);

    return "Service record saved.";
}

$GLOBALS["SALES_ASSISTANT_ACTION_HANDLERS"][] = "saveServiceRecordAction";

function renderServiceRecords(
    PDO $pdo,
    int $assistantId
): void {
    try {
        $appointmentQuery = "
            SELECT
                a.appointment_id,
                u.name AS customer_name
            FROM appointments AS a
            LEFT JOIN users AS u
                ON a.user_id = u.user_id
            ORDER BY a.appointment_date DESC
        ";

        $appointmentStmt = $pdo->query($appointmentQuery);
        $appointments = $appointmentStmt->fetchAll(PDO::FETCH_ASSOC);

        $statusQuery = "
            SELECT
                status_id,
                status_name
            FROM service_status
            ORDER BY status_id
        ";

        $statusStmt = $pdo->query($statusQuery);
        $statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        $recordQuery = "
            SELECT
                sr.service_record_id,
                sr.appointment_id,
                sr.start_date,
                sr.end_date,
                sr.total_cost,
                sr.amount_paid,
                ss.status_name,
                u.name AS customer_name
            FROM service_records AS sr
            LEFT JOIN service_status AS ss
                ON sr.status_id = ss.status_id
            LEFT JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id
            LEFT JOIN users AS u
                ON a.user_id = u.user_id
            ORDER BY sr.service_record_id DESC
        ";

        $recordStmt = $pdo->query($recordQuery);
        $records = $recordStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $appointments = [];
        $statuses = [];
        $records = [];
    }

    ?>

    <section class="card">

        <h2>
            Record Service Details
        </h2>

        <form
            method="post"
            class="form-grid"
        >

            <input
                type="hidden"
                name="dashboard_action"
                value="save_record"
            >

            <label>
                Appointment

                <select
                    name="appointment_id"
                    required
                >

                    <option value="">
                        Select Appointment
                    </option>

                    <?php foreach ($appointments as $appointment): ?>

                        <option value="<?= (int) $appointment["appointment_id"] ?>">
                            #<?= (int) $appointment["appointment_id"] ?>
                            -
                            <?= htmlspecialchars(
                                $appointment["customer_name"] ?? "Customer",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>
                Status

                <select
                    name="status_id"
                    required
                >

                    <?php foreach ($statuses as $status): ?>

                        <option value="<?= (int) $status["status_id"] ?>">
                            <?= htmlspecialchars(
                                $status["status_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>
                Start

                <input
                    type="datetime-local"
                    name="start_date"
                >

            </label>

            <label>
                End

                <input
                    type="datetime-local"
                    name="end_date"
                >

            </label>

            <label>
                Total Cost

                <input
                    type="number"
                    name="total_cost"
                    min="0"
                    step="0.01"
                    value="0"
                    required
                >

            </label>

            <label>
                Amount Paid

                <input
                    type="number"
                    name="amount_paid"
                    min="0"
                    step="0.01"
                    value="0"
                    required
                >

            </label>

            <div>
                <button
                    type="submit"
                    class="button"
                >
                    Save Record
                </button>
            </div>

        </form>

    </section>

    <section class="card">

        <h2>
            Existing Service Records
        </h2>

        <?php if ($records): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Total</th>
                        <th>Paid</th>
                    </tr>

                    <?php foreach ($records as $record): ?>

                        <tr>

                            <td>
                                #<?= (int) $record["service_record_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record["customer_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record["status_name"] ?? "-",
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
                                Rs. <?= number_format(
                                    (float) $record["total_cost"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                Rs. <?= number_format(
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
