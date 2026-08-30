<?php

/*
|--------------------------------------------------------------------------
| Management - Offers
|--------------------------------------------------------------------------
*/

function managementOfferAction(
    PDO $pdo,
    int $managementId,
    array $data,
    string $action
): string {
    if ($action !== "add_offer") {
        return "";
    }

    $title = trim($data["title"] ?? "");
    $description = trim($data["description"] ?? "");
    $type = trim($data["type"] ?? "");
    $discount = (float) ($data["discount"] ?? 0);
    $startDate = trim($data["start_date"] ?? "");
    $endDate = trim($data["end_date"] ?? "");

    if (
        $title === "" ||
        $type === "" ||
        $discount < 0 ||
        $startDate === "" ||
        $endDate === ""
    ) {
        return "Please provide valid offer details.";
    }

    if ($endDate < $startDate) {
        return "Offer end date cannot be before the start date.";
    }

    $query = "
        INSERT INTO offers
            (
                title,
                description,
                type,
                discount,
                start_date,
                end_date
            )
        VALUES
            (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $title,
        $description,
        $type,
        $discount,
        $startDate,
        $endDate
    ]);

    return "Offer created successfully.";
}

$GLOBALS["MANAGEMENT_ACTION_HANDLERS"][] = "managementOfferAction";

function renderManagementOffers(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                offer_id,
                title,
                type,
                discount,
                start_date,
                end_date
            FROM offers
            ORDER BY offer_id DESC
        ";

        $stmt = $pdo->query($query);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $offers = [];
    }

    ?>

    <section class="card">

        <h2>
            Manage Offers
        </h2>

        <form
            method="post"
            class="form-grid"
        >

            <input
                type="hidden"
                name="dashboard_action"
                value="add_offer"
            >

            <label>
                Title

                <input
                    type="text"
                    name="title"
                    required
                >

            </label>

            <label>
                Description

                <input
                    type="text"
                    name="description"
                >

            </label>

            <label>
                Type

                <select name="type" required>
                    <option value="Seasonal">Seasonal</option>
                    <option value="Regular Customer">Regular Customer</option>
                </select>

            </label>

            <label>
                Discount (%)

                <input
                    type="number"
                    name="discount"
                    min="0"
                    step="0.01"
                    required
                >

            </label>

            <label>
                Start Date

                <input
                    type="date"
                    name="start_date"
                    required
                >

            </label>

            <label>
                End Date

                <input
                    type="date"
                    name="end_date"
                    required
                >

            </label>

            <div>
                <button
                    type="submit"
                    class="button"
                >
                    Create Offer
                </button>
            </div>

        </form>

    </section>

    <section class="card">

        <h2>
            Existing Offers
        </h2>

        <?php if ($offers): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Start</th>
                        <th>End</th>
                    </tr>

                    <?php foreach ($offers as $offer): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $offer["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $offer["type"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $offer["discount"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>%
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $offer["start_date"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $offer["end_date"],
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
                No offers found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
