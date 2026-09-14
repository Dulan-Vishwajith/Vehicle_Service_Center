<?php

/*
|--------------------------------------------------------------------------
| MANAGE PACKAGES
|--------------------------------------------------------------------------
*/

$packagesMessage = '';
$packagesMessageType = '';
$packagesError = '';

/*
|--------------------------------------------------------------------------
| PACKAGE ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_action'])) {

    $packageId = (int) ($_POST['package_id'] ?? 0);
    $action = $_POST['package_action'];

    if ($packageId > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | Toggle Package Status
            |--------------------------------------------------------------------------
            */

            if ($action === 'toggle_status') {

                $stmt = $pdo->prepare("
                    UPDATE service_packages
                    SET status = IF(status = 1, 0, 1)
                    WHERE id = ?
                ");

                $stmt->execute([$packageId]);

                $packagesMessage = "Package status updated successfully.";
                $packagesMessageType = "success";
            }


            /*
            |--------------------------------------------------------------------------
            | Delete Package
            |--------------------------------------------------------------------------
            */

            elseif ($action === 'delete') {

                $pdo->beginTransaction();

                /*
                | Remove services linked to the package first.
                */
                $stmt = $pdo->prepare("
                    DELETE FROM package_services
                    WHERE package_id = ?
                ");

                $stmt->execute([$packageId]);


                /*
                | Then remove the package.
                */
                $stmt = $pdo->prepare("
                    DELETE FROM service_packages
                    WHERE id = ?
                ");

                $stmt->execute([$packageId]);

                $pdo->commit();

                $packagesMessage = "Package deleted successfully.";
                $packagesMessageType = "success";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $packagesMessage = "Unable to update the package.";
            $packagesMessageType = "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD PACKAGES
|--------------------------------------------------------------------------
*/

$packages = [];

try {

    /*
    | IMPORTANT:
    | service_packages does NOT have a price column.
    | It has package_name, duration and status.
    */

    $stmt = $pdo->query("
        SELECT
            sp.id,
            sp.package_name,
            sp.duration,
            sp.status,
            GROUP_CONCAT(
                s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS included_services
        FROM service_packages sp

        LEFT JOIN package_services ps
            ON ps.package_id = sp.id

        LEFT JOIN services s
            ON s.id = ps.service_id

        GROUP BY
            sp.id,
            sp.package_name,
            sp.duration,
            sp.status

        ORDER BY sp.package_name ASC
    ");

    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $packagesError = "Unable to load packages.";
}

?>


<!--
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
-->

<div class="panel-header">

    <h2>Manage Packages</h2>

    <a href="?page=package-form">
        + New Package
    </a>

</div>


<!--
|--------------------------------------------------------------------------
| SUCCESS / ERROR MESSAGE
|--------------------------------------------------------------------------
-->

<?php if ($packagesMessage): ?>

    <div class="admin-message <?= htmlspecialchars($packagesMessageType) ?>">
        <?= htmlspecialchars($packagesMessage) ?>
    </div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| DATABASE ERROR
|--------------------------------------------------------------------------
-->

<?php if ($packagesError): ?>

    <div class="ops-error-message">
        <?= htmlspecialchars($packagesError) ?>
    </div>


<!--
|--------------------------------------------------------------------------
| NO PACKAGES
|--------------------------------------------------------------------------
-->

<?php elseif (empty($packages)): ?>

    <div class="empty-message">

        <p>No packages have been created yet.</p>

    </div>


<!--
|--------------------------------------------------------------------------
| PACKAGE TABLE
|--------------------------------------------------------------------------
-->

<?php else: ?>

    <div class="ops-table package-table">


        <!-- TABLE HEADER -->

        <div class="ops-table-heading">

            <span class="admin-col-name">
                Package Name
            </span>

            <span class="admin-col-service">
                Included Services
            </span>

            <span class="admin-col-mid">
                Duration
            </span>

            <span class="admin-col-status">
                Status
            </span>

            <span class="admin-col-actions">
                Actions
            </span>

        </div>


        <!-- PACKAGE ROWS -->

        <?php foreach ($packages as $package): ?>

            <div class="ops-table-row">


                <!-- PACKAGE NAME -->

                <span class="admin-col-name">

                    <?= htmlspecialchars(
                        $package['package_name']
                    ) ?>

                </span>


                <!-- INCLUDED SERVICES -->

                <span class="admin-col-service admin-col-truncate">

                    <?= htmlspecialchars(
                        $package['included_services'] ?? '—'
                    ) ?>

                </span>


                <!-- DURATION -->

                <span class="admin-col-mid">

                    <?= htmlspecialchars(
                        $package['duration'] ?? '—'
                    ) ?>

                </span>


                <!-- STATUS -->

                <span class="admin-col-status">

                    <span class="status <?= $package['status']
                        ? 'status-completed'
                        : 'status-cancelled' ?>">

                        <?= $package['status']
                            ? 'Active'
                            : 'Inactive' ?>

                    </span>

                </span>


                <!-- ACTIONS -->

                <span class="admin-col-actions">


                    <!-- EDIT -->

                    <a
                        href="?page=package-form&id=<?= (int) $package['id'] ?>"
                        class="admin-action-link"
                    >
                        Edit
                    </a>


                    <!-- ACTIVATE / DEACTIVATE -->

                    <form
                        method="post"
                        class="admin-inline-form"
                    >

                        <input
                            type="hidden"
                            name="package_id"
                            value="<?= (int) $package['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="package_action"
                            value="toggle_status"
                        >

                        <button
                            type="submit"
                            class="admin-action-link"
                        >
                            <?= $package['status']
                                ? 'Deactivate'
                                : 'Activate' ?>
                        </button>

                    </form>


                    <!-- DELETE -->

                    <form
                        method="post"
                        class="admin-inline-form"
                        onsubmit="return confirm('Delete this package? This cannot be undone.');"
                    >

                        <input
                            type="hidden"
                            name="package_id"
                            value="<?= (int) $package['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="package_action"
                            value="delete"
                        >

                        <button
                            type="submit"
                            class="admin-action-link admin-action-danger"
                        >
                            Delete
                        </button>

                    </form>


                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>