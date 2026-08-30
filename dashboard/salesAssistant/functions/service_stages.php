<?php

/*
|--------------------------------------------------------------------------
| Service Assistant - Update Service Stages
|--------------------------------------------------------------------------
*/

function updateServiceStageAction(
    PDO $pdo,
    int $assistantId,
    array $data,
    string $action
): string {
    if ($action !== "update_status") {
        return "";
    }

    $recordId = (int) ($data["service_record_id"] ?? 0);
    $statusId = (int) ($data["status_id"] ?? 0);

    if ($recordId <= 0 || $statusId <= 0) {
        return "Select a service record and status.";
    }

    $query = "
        UPDATE service_records
        SET
            status_id = ?,
            assistant_id = ?
        WHERE service_record_id = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $statusId,
        $assistantId,
        $recordId
    ]);

    return "Service status updated.";
}

$GLOBALS["SALES_ASSISTANT_ACTION_HANDLERS"][] = "updateServiceStageAction";

function renderServiceStages(
    PDO $pdo,
    int $assistantId
): void {
    try {
        $recordsQuery = "
            SELECT
                sr.service_record_id,
                sr.status_id,
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

        $recordsStmt = $pdo->query($recordsQuery);
        $records = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);

        $statusQuery = "
            SELECT
                status_id,
                status_name
            FROM service_status
            ORDER BY status_id
        ";

        $statusStmt = $pdo->query($statusQuery);
        $statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $records = [];
        $statuses = [];
    }

    ?>

    <section class="card">

        <h2>
            Update Service Stage
        </h2>

        <?php if ($records && $statuses): ?>

            <div class="forms">

                <?php foreach ($records as $record): ?>

                    <form
                        method="post"
                        class="inline-form"
                    >

                        <input
                            type="hidden"
                            name="dashboard_action"
                            value="update_status"
                        >

                        <input
                            type="hidden"
                            name="service_record_id"
                            value="<?= (int) $record["service_record_id"] ?>"
                        >

                        <b>
                            Service #<?= (int) $record["service_record_id"] ?>
                            -
                            <?= htmlspecialchars(
                                $record["customer_name"] ?? "Customer",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </b>

                        <select name="status_id" required>

                            <?php foreach ($statuses as $status): ?>

                                <option
                                    value="<?= (int) $status["status_id"] ?>"
                                    <?= (
                                        (int) $status["status_id"] ===
                                        (int) $record["status_id"]
                                    ) ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars(
                                        $status["status_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <span>
                            Current:
                            <?= htmlspecialchars(
                                $record["status_name"] ?? "Pending",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                        <button
                            class="button"
                            type="submit"
                        >
                            Update
                        </button>

                    </form>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                No service records or statuses found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
