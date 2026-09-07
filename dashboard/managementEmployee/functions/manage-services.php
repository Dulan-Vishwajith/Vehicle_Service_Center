<?php

/*
|--------------------------------------------------------------------------
| MANAGE SERVICES
|--------------------------------------------------------------------------
*/

$servicesMessage = '';
$servicesMessageType = '';


/*
|--------------------------------------------------------------------------
| HANDLE TOGGLE STATUS / DELETE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_action'])) {

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $action = $_POST['service_action'];

    if ($serviceId > 0) {

        try {

            if ($action === 'toggle_status') {

                $stmt = $pdo->prepare("UPDATE services SET status = IF(status = 1, 0, 1) WHERE id = ?");
                $stmt->execute([$serviceId]);

                $servicesMessage = "Service status updated.";
                $servicesMessageType = "success";

            } elseif ($action === 'delete') {

                /*
                | Before deleting: check it isn't attached to any
                | booking or package. Deleting it would corrupt
                | history (booking_services references it) and
                | break any package that includes it.
                */

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_services WHERE service_id = ?");
                $stmt->execute([$serviceId]);
                $inBookings = (int) $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM package_services WHERE service_id = ?");
                $stmt->execute([$serviceId]);
                $inPackages = (int) $stmt->fetchColumn();

                if ($inBookings > 0) {

                    $servicesMessage = "This service can't be deleted — it's used in {$inBookings} booking(s). Deactivate it instead.";
                    $servicesMessageType = "error";

                } elseif ($inPackages > 0) {

                    $servicesMessage = "This service can't be deleted — it's included in {$inPackages} package(s). Remove it from those packages first.";
                    $servicesMessageType = "error";

                } else {

                    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                    $stmt->execute([$serviceId]);

                    $servicesMessage = "Service deleted.";
                    $servicesMessageType = "success";
                }
            }

        } catch (PDOException $e) {

            $servicesMessage = "Unable to update the service.";
            $servicesMessageType = "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD SERVICES
|--------------------------------------------------------------------------
*/

$services = [];
$servicesError = '';

try {

    $services = $pdo->query("
        SELECT id, service_name, category, description, price, duration, status
        FROM services
        ORDER BY service_name ASC
    ")->fetchAll();

} catch (PDOException $e) {

    $servicesError = "Unable to load services.";
}

?>


<div class="panel-header">
    <h2>Manage Services</h2>
    <a href="?page=service-form">+ New Service</a>
</div>


<?php if ($servicesMessage): ?>
    <div class="admin-message <?= $servicesMessageType ?>"><?= htmlspecialchars($servicesMessage) ?></div>
<?php endif; ?>


<?php if ($servicesError): ?>

    <div class="ops-error-message"><?= htmlspecialchars($servicesError) ?></div>

<?php elseif (empty($services)): ?>

    <div class="empty-message">
        <p>No services have been added yet.</p>
    </div>

<?php else: ?>

    <div class="ops-table">

        <div class="ops-table-heading">
            <span class="admin-col-name">Service Name</span>
            <span class="admin-col-mid">Description</span>
            <span class="admin-col-mid">Price</span>
            <span class="admin-col-mid">Duration</span>
            <span class="admin-col-status">Status</span>
            <span class="admin-col-actions">Actions</span>
        </div>

        <?php foreach ($services as $service): ?>

            <div class="ops-table-row">

                <span class="admin-col-name"><?= htmlspecialchars($service['service_name']) ?></span>

                <span class="admin-col-mid admin-col-truncate"><?= htmlspecialchars($service['description']) ?></span>

                <span class="admin-col-mid">Rs. <?= number_format((float) $service['price'], 2) ?></span>

                <span class="admin-col-mid"><?= htmlspecialchars($service['duration']) ?></span>

                <span class="admin-col-status">
                    <span class="status <?= $service['status'] ? 'status-completed' : 'status-cancelled' ?>">
                        <?= $service['status'] ? 'Active' : 'Inactive' ?>
                    </span>
                </span>

                <span class="admin-col-actions">

                    <a href="?page=service-form&id=<?= (int) $service['id'] ?>" class="admin-action-link">Edit</a>

                    <form method="post" class="admin-inline-form">
                        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                        <input type="hidden" name="service_action" value="toggle_status">
                        <button type="submit" class="admin-action-link">
                            <?= $service['status'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>

                    <form method="post" class="admin-inline-form" onsubmit="return confirm('Delete this service? This cannot be undone.');">
                        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                        <input type="hidden" name="service_action" value="delete">
                        <button type="submit" class="admin-action-link admin-action-danger">Delete</button>
                    </form>

                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>
