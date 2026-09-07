<?php

/*
|--------------------------------------------------------------------------
| PACKAGE FORM
|--------------------------------------------------------------------------
| One form for both Create (no id) and Edit (valid id).
*/

$packageId = (int) ($_GET['id'] ?? 0);

$package = [
    'package_name' => '',
    'description' => '',
    'price' => '',
    'duration' => '',
    'status' => 1
];

$selectedServiceIds = [];
$formError = '';
$isEdit = false;


if ($packageId > 0) {

    try {

        $stmt = $pdo->prepare("SELECT * FROM service_packages WHERE id = ? LIMIT 1");
        $stmt->execute([$packageId]);
        $existing = $stmt->fetch();

        if ($existing) {

            $package = array_merge($package, $existing);
            $isEdit = true;

            $stmt = $pdo->prepare("SELECT service_id FROM package_services WHERE package_id = ?");
            $stmt->execute([$packageId]);
            $selectedServiceIds = array_column($stmt->fetchAll(), 'service_id');

        } else {

            $formError = "That package could not be found. Creating a new one instead.";
        }

    } catch (PDOException $e) {

        $formError = "Unable to load this package.";
    }
}


$allServices = [];

try {

    $allServices = $pdo->query("SELECT id, service_name, price FROM services WHERE status = 1 ORDER BY service_name")->fetchAll();

} catch (PDOException $e) {
    // Checklist just stays empty; rest of the form still works.
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {

    $packageName = trim($_POST['package_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $postedServiceIds = array_map('intval', $_POST['services'] ?? []);
    $postedServiceIds = array_values(array_unique(array_filter($postedServiceIds, fn($id) => $id > 0)));

    $package = array_merge($package, $_POST);
    $package['status'] = $status;
    $selectedServiceIds = $postedServiceIds;

    if ($packageName === '' || $duration === '') {

        $formError = "Please fill in all required fields.";

    } elseif ($price <= 0) {

        $formError = "Please enter a valid price greater than zero.";

    } elseif (empty($postedServiceIds)) {

        $formError = "Please select at least one included service.";

    } else {

        try {

            $pdo->beginTransaction();

            if ($isEdit) {

                $stmt = $pdo->prepare("
                    UPDATE service_packages SET
                        package_name = ?, price = ?, duration = ?, status = ?
                    WHERE id = ?
                ");

                $stmt->execute([$packageName, $price, $duration, $status, $packageId]);

                $stmt = $pdo->prepare("DELETE FROM package_services WHERE package_id = ?");
                $stmt->execute([$packageId]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO service_packages (package_name, price, duration, status)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([$packageName, $price, $duration, $status]);

                $packageId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("
                INSERT INTO package_services (package_id, service_id) VALUES (?, ?)
            ");

            foreach ($postedServiceIds as $serviceId) {
                $stmt->execute([$packageId, $serviceId]);
            }

            $pdo->commit();

            header("Location: ?page=packages");
            exit();

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $formError = "Unable to save this package. Please try again.";
        }
    }
}

?>


<div class="panel-header">
    <h2><?= $isEdit ? 'Edit Package' : 'New Package' ?></h2>
    <a href="?page=packages">&larr; Back to Packages</a>
</div>


<?php if ($formError): ?>
    <div class="admin-message error"><?= htmlspecialchars($formError) ?></div>
<?php endif; ?>


<form method="post" class="admin-form">

    <div class="admin-form-grid">

        <div class="admin-form-group">
            <label>Package Name</label>
            <input type="text" name="package_name" value="<?= htmlspecialchars($package['package_name']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Status</label>
            <select name="status">
                <option value="1" <?= $package['status'] ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !$package['status'] ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="admin-form-group admin-form-full">
            <label>Description</label>
            <textarea name="description" rows="3"><?= htmlspecialchars($package['description'] ?? '') ?></textarea>
        </div>

        <div class="admin-form-group">
            <label>Package Price (Rs.)</label>
            <input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string) $package['price']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Duration Label</label>
            <input type="text" name="duration" placeholder="e.g. 3 Hours" value="<?= htmlspecialchars($package['duration']) ?>" required>
        </div>

        <div class="admin-form-group admin-form-full">

            <label>Included Services</label>

            <?php if (empty($allServices)): ?>

                <p>No active services available. Create a service first.</p>

            <?php else: ?>

                <div class="admin-checkbox-grid">

                    <?php foreach ($allServices as $svc): ?>

                        <label class="admin-checkbox-item">
                            <input
                                type="checkbox"
                                name="services[]"
                                value="<?= (int) $svc['id'] ?>"
                                <?= in_array((int) $svc['id'], $selectedServiceIds, true) ? 'checked' : '' ?>
                            >
                            <?= htmlspecialchars($svc['service_name']) ?>
                            <small>Rs. <?= number_format((float) $svc['price'], 2) ?></small>
                        </label>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div class="admin-form-actions">
        <a href="?page=packages" class="admin-cancel-btn">Cancel</a>
        <button type="submit" name="save_package" value="1" class="admin-save-btn">
            <?= $isEdit ? 'Save Changes' : 'Create Package' ?>
        </button>
    </div>

</form>
