<?php

/*
|--------------------------------------------------------------------------
| MANAGE PACKAGES
|--------------------------------------------------------------------------
*/

$packagesMessage = '';
$packagesMessageType = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_action'])) {

    $packageId = (int) ($_POST['package_id'] ?? 0);
    $action = $_POST['package_action'];

    if ($packageId > 0) {

        try {

            if ($action === 'toggle_status') {

                $stmt = $pdo->prepare("UPDATE service_packages SET status = IF(status = 1, 0, 1) WHERE id = ?");
                $stmt->execute([$packageId]);

                $packagesMessage = "Package status updated.";
                $packagesMessageType = "success";

            } elseif ($action === 'delete') {

                /*
                | This schema doesn't track packages against bookings
                | directly (booking_services only stores individual
                | service_ids), so the only real relationship to check
                | is package_services. We remove those links first,
                | then the package itself, inside a transaction.
                */
                {

                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("DELETE FROM package_services WHERE package_id = ?");
                    $stmt->execute([$packageId]);

                    $stmt = $pdo->prepare("DELETE FROM service_packages WHERE id = ?");
                    $stmt->execute([$packageId]);

                    $pdo->commit();

                    $packagesMessage = "Package deleted.";
                    $packagesMessageType = "success";
                }
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


$packages = [];
$packagesError = '';

try {

    $stmt = $pdo->query("
        SELECT
            sp.id,
            sp.package_name,
            sp.price,
            sp.status,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS included_services
        FROM service_packages sp
        LEFT JOIN package_services ps
            ON ps.package_id = sp.id
        LEFT JOIN services s
            ON s.id = ps.service_id
        GROUP BY sp.id
        ORDER BY sp.package_name ASC
    ");

    $packages = $stmt->fetchAll();

} catch (PDOException $e) {

    $packagesError = "Unable to load packages.";
}

?>


<div class="panel-header">
    <h2>Manage Packages</h2>
    <a href="?page=package-form">+ New Package</a>
</div>


<?php if ($packagesMessage): ?>
    <div class="admin-message <?= $packagesMessageType ?>"><?= htmlspecialchars($packagesMessage) ?></div>
<?php endif; ?>


<?php if ($packagesError): ?>

    <div class="ops-error-message"><?= htmlspecialchars($packagesError) ?></div>

<?php elseif (empty($packages)): ?>

    <div class="empty-message">
        <p>No packages have been created yet.</p>
    </div>

<?php else: ?>

    <div class="ops-table">

        <div class="ops-table-heading">
            <span class="admin-col-name">Package Name</span>
            <span class="admin-col-service">Included Services</span>
            <span class="admin-col-mid">Price</span>
            <span class="admin-col-status">Status</span>
            <span class="admin-col-actions">Actions</span>
        </div>

        <?php foreach ($packages as $package): ?>

            <div class="ops-table-row">

                <span class="admin-col-name"><?= htmlspecialchars($package['package_name']) ?></span>

                <span class="admin-col-service admin-col-truncate"><?= htmlspecialchars($package['included_services'] ?? '—') ?></span>

                <span class="admin-col-mid">Rs. <?= number_format((float) $package['price'], 2) ?></span>

                <span class="admin-col-status">
                    <span class="status <?= $package['status'] ? 'status-completed' : 'status-cancelled' ?>">
                        <?= $package['status'] ? 'Active' : 'Inactive' ?>
                    </span>
                </span>

                <span class="admin-col-actions">

                    <a href="?page=package-form&id=<?= (int) $package['id'] ?>" class="admin-action-link">Edit</a>

                    <form method="post" class="admin-inline-form">
                        <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
                        <input type="hidden" name="package_action" value="toggle_status">
                        <button type="submit" class="admin-action-link">
                            <?= $package['status'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>

                    <form method="post" class="admin-inline-form" onsubmit="return confirm('Delete this package? This cannot be undone.');">
                        <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
                        <input type="hidden" name="package_action" value="delete">
                        <button type="submit" class="admin-action-link admin-action-danger">Delete</button>
                    </form>

                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>
