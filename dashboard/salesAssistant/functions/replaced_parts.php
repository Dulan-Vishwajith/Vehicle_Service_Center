<?php

/*
|--------------------------------------------------------------------------
| Service Assistant - Replaced Parts and Warranty
|--------------------------------------------------------------------------
*/

function addReplacedPartAction(
    PDO $pdo,
    int $assistantId,
    array $data,
    string $action
): string {
    if ($action !== "add_part") {
        return "";
    }

    $serviceRecordId = (int) ($data["service_record_id"] ?? 0);
    $partId = (int) ($data["part_id"] ?? 0);
    $quantity = (int) ($data["quantity"] ?? 0);

    $warrantyPeriod = trim($data["warranty_period"] ?? "");
    $warrantyExpiry = trim($data["warranty_expiry_date"] ?? "");

    if (
        $serviceRecordId <= 0 ||
        $partId <= 0 ||
        $quantity <= 0
    ) {
        return "Please provide valid part information.";
    }

    $query = "
        INSERT INTO replaced_parts
            (
                service_record_id,
                part_id,
                quantity,
                warranty_period,
                warranty_expiry_date
            )
        VALUES
            (?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $serviceRecordId,
        $partId,
        $quantity,
        $warrantyPeriod,
        $warrantyExpiry !== "" ? $warrantyExpiry : null
    ]);

    return "Part and warranty information added.";
}

$GLOBALS["SALES_ASSISTANT_ACTION_HANDLERS"][] = "addReplacedPartAction";

function renderReplacedParts(
    PDO $pdo,
    int $assistantId
): void {
    try {
        $recordQuery = "
            SELECT service_record_id
            FROM service_records
            ORDER BY service_record_id DESC
        ";

        $recordStmt = $pdo->query($recordQuery);
        $records = $recordStmt->fetchAll(PDO::FETCH_ASSOC);

        $partQuery = "
            SELECT
                part_id,
                name,
                price
            FROM parts
            ORDER BY name
        ";

        $partStmt = $pdo->query($partQuery);
        $parts = $partStmt->fetchAll(PDO::FETCH_ASSOC);

        $replacedQuery = "
            SELECT
                rp.replaced_part_id,
                rp.service_record_id,
                p.name AS part_name,
                rp.quantity,
                rp.warranty_period,
                rp.warranty_expiry_date
            FROM replaced_parts AS rp
            LEFT JOIN parts AS p
                ON rp.part_id = p.part_id
            ORDER BY rp.replaced_part_id DESC
        ";

        $replacedStmt = $pdo->query($replacedQuery);
        $replacedParts = $replacedStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $records = [];
        $parts = [];
        $replacedParts = [];
    }

    ?>

    <section class="card">

        <h2>
            Replaced Parts & Warranty
        </h2>

        <form
            method="post"
            class="form-grid"
        >

            <input
                type="hidden"
                name="dashboard_action"
                value="add_part"
            >

            <label>
                Service Record

                <select
                    name="service_record_id"
                    required
                >

                    <option value="">
                        Select Record
                    </option>

                    <?php foreach ($records as $record): ?>

                        <option value="<?= (int) $record["service_record_id"] ?>">
                            #<?= (int) $record["service_record_id"] ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>
                Part

                <select
                    name="part_id"
                    required
                >

                    <option value="">
                        Select Part
                    </option>

                    <?php foreach ($parts as $part): ?>

                        <option value="<?= (int) $part["part_id"] ?>">
                            <?= htmlspecialchars(
                                $part["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                            -
                            Rs. <?= number_format(
                                (float) $part["price"],
                                2
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>
                Quantity

                <input
                    type="number"
                    name="quantity"
                    value="1"
                    min="1"
                    required
                >

            </label>

            <label>
                Warranty Period

                <input
                    type="text"
                    name="warranty_period"
                    placeholder="e.g. 6 Months"
                >

            </label>

            <label>
                Warranty Expiry

                <input
                    type="date"
                    name="warranty_expiry_date"
                >

            </label>

            <div>
                <button
                    type="submit"
                    class="button"
                >
                    Add Part
                </button>
            </div>

        </form>

    </section>

    <section class="card">

        <h2>
            Replaced Parts History
        </h2>

        <?php if ($replacedParts): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Service</th>
                        <th>Part</th>
                        <th>Quantity</th>
                        <th>Warranty</th>
                        <th>Expiry</th>
                    </tr>

                    <?php foreach ($replacedParts as $item): ?>

                        <tr>

                            <td>
                                #<?= (int) $item["service_record_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item["part_name"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $item["quantity"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item["warranty_period"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item["warranty_expiry_date"] ?? "-",
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
                No replaced parts recorded.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
