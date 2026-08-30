<?php

/*
|--------------------------------------------------------------------------
| Management - Services and Packages
|--------------------------------------------------------------------------
*/

function managementServiceAction(
    PDO $pdo,
    int $managementId,
    array $data,
    string $action
): string {
    if ($action !== "toggle_service") {
        return "";
    }

    $serviceId = (int) ($data["service_id"] ?? 0);
    $newStatus = (int) ($data["new_status"] ?? 0);

    if ($serviceId <= 0 || !in_array($newStatus, [0, 1], true)) {
        return "Invalid service information.";
    }

    $query = "
        UPDATE services
        SET status = ?
        WHERE id = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $newStatus,
        $serviceId
    ]);

    return "Service status updated.";
}

$GLOBALS["MANAGEMENT_ACTION_HANDLERS"][] = "managementServiceAction";

function renderManagementServicesAndPackages(
    PDO $pdo,
    int $managementId
): void {
    try {
        $serviceQuery = "
            SELECT
                id,
                service_name,
                category,
                price,
                duration,
                status
            FROM services
            ORDER BY id DESC
        ";

        $serviceStmt = $pdo->query($serviceQuery);
        $services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

        $packageQuery = "
            SELECT
                id,
                package_name,
                price,
                duration,
                status
            FROM service_packages
            ORDER BY id DESC
        ";

        $packageStmt = $pdo->query($packageQuery);
        $packages = $packageStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $services = [];
        $packages = [];
    }

    ?>

    <section class="card">

        <h2>
            Manage Services
        </h2>

        <?php if ($services): ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($services as $service): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $service["service_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $service["category"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                Rs. <?= number_format(
                                    (float) $service["price"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $service["duration"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (
                                    (int) $service["status"] === 1
                                ) ? "Active" : "Inactive" ?>
                            </td>

                            <td>

                                <form method="post">

                                    <input
                                        type="hidden"
                                        name="dashboard_action"
                                        value="toggle_service"
                                    >

                                    <input
                                        type="hidden"
                                        name="service_id"
                                        value="<?= (int) $service["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="new_status"
                                        value="<?= (
                                            (int) $service["status"] === 1
                                        ) ? 0 : 1 ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="small-button"
                                    >
                                        <?= (
                                            (int) $service["status"] === 1
                                        ) ? "Disable" : "Enable" ?>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">
                No services found.
            </p>

        <?php endif; ?>

    </section>

    <section class="card">

        <h2>
            View Service Packages
        </h2>

        <?php if ($packages): ?>

            <div class="grid">

                <?php foreach ($packages as $package): ?>

                    <div class="mini">

                        <small>
                            Package
                        </small>

                        <h3>
                            <?= htmlspecialchars(
                                $package["package_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h3>

                        <p>
                            Duration:
                            <?= htmlspecialchars(
                                $package["duration"] ?? "-",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                        <b>
                            Rs. <?= number_format(
                                (float) $package["price"],
                                2
                            ) ?>
                        </b>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                No service packages found.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
