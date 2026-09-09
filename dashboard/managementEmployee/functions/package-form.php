<?php

/*
|--------------------------------------------------------------------------
| PACKAGE FORM
|--------------------------------------------------------------------------
| One form for both Create (no id) and Edit (valid id).
|
| Matches the current `service_packages` table exactly:
| id, package_name, price, duration, status, created_at.
| No description column — if you add one later (see
| migration_management_dashboard.sql), the description
| block below can be restored.
|
| Price and Duration Label auto-fill from whichever services are
| checked (see the <script> block at the bottom) — sum of each
| selected service's price and duration_minutes. Both fields stay
| editable afterwards, since a package is often sold at a discount
| below the sum of its individual services.
*/

$packageId = (int) ($_GET['id'] ?? 0);

$package = [
    'package_name' => '',
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


/*
|--------------------------------------------------------------------------
| SERVICES FOR THE CHECKLIST
|--------------------------------------------------------------------------
| duration_minutes is needed here (not just price) so the JS below
| can sum both and auto-fill Price + Duration Label.
*/

$allServices = [];

try {

    $allServices = $pdo->query("
        SELECT id, service_name, price, duration_minutes
        FROM services
        WHERE status = 1
        ORDER BY service_name
    ")->fetchAll();

} catch (PDOException $e) {
    // Checklist just stays empty; rest of the form still works.
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {

    $packageName = trim($_POST['package_name'] ?? '');
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
                    VALUES (?, ?, ?, ?)
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

            /*
            | Development-mode detail: shows the real DB error instead of
            | a generic message. Remove the $e->getMessage() part before
            | this goes to production — raw DB errors shouldn't reach
            | end users on a live site.
            */
            $formError = "Unable to save this package: " . $e->getMessage();
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


<form method="post" class="admin-form" id="packageForm">

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

        <div class="admin-form-group">
            <label>
                Package Price (Rs.)
                <small class="admin-autofill-hint">auto-filled — adjust for a package discount</small>
            </label>
            <input type="number" step="0.01" min="0" name="price" id="packagePrice" value="<?= htmlspecialchars((string) $package['price']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>
                Duration Label
                <small class="admin-autofill-hint">auto-filled — edit if you want different wording</small>
            </label>
            <input type="text" name="duration" id="packageDuration" placeholder="e.g. 3 Hours" value="<?= htmlspecialchars($package['duration']) ?>" required>
        </div>

        <div class="admin-form-group admin-form-full">

            <label>Included Services</label>

            <?php if (empty($allServices)): ?>

                <p>No active services available. Create a service first.</p>

            <?php else: ?>

                <div class="admin-checkbox-grid" id="packageServiceChecklist">

                    <?php foreach ($allServices as $svc): ?>

                        <label class="admin-checkbox-item">
                            <input
                                type="checkbox"
                                name="services[]"
                                value="<?= (int) $svc['id'] ?>"
                                data-price="<?= htmlspecialchars((string) $svc['price']) ?>"
                                data-duration-minutes="<?= htmlspecialchars((string) ($svc['duration_minutes'] ?? 0)) ?>"
                                <?= in_array((int) $svc['id'], $selectedServiceIds, true) ? 'checked' : '' ?>
                            >
                            <?= htmlspecialchars($svc['service_name']) ?>
                            <small>Rs. <?= number_format((float) $svc['price'], 2) ?></small>
                        </label>

                    <?php endforeach; ?>

                </div>

                <p class="admin-checklist-total">
                    Selected total:
                    <strong id="packageSelectedSummary">Rs. 0.00 · 0 minutes</strong>
                </p>

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


<script>
(function () {

    var checklist = document.getElementById('packageServiceChecklist');

    if (!checklist) {
        return;
    }

    var priceInput = document.getElementById('packagePrice');
    var durationInput = document.getElementById('packageDuration');
    var summaryEl = document.getElementById('packageSelectedSummary');

    // Always recalculates from whatever is checked — on page load AND
    // on every checkbox change — for both a brand-new package and an
    // existing one being edited. Price/Duration always match the
    // actual selection. If you want a package priced below the sum of
    // its services (a discount), check the services first, then edit
    // the price/duration fields directly right before saving.

    function minutesToLabel(totalMinutes) {

        if (totalMinutes <= 0) {
            return '';
        }

        if (totalMinutes < 60) {
            return totalMinutes + ' Minutes';
        }

        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;

        if (minutes === 0) {
            return hours + (hours === 1 ? ' Hour' : ' Hours');
        }

        if (minutes === 30) {
            return (hours + 0.5) + ' Hours';
        }

        return hours + 'h ' + minutes + 'm';
    }

    function recalculate() {

        var checkboxes = checklist.querySelectorAll('input[type="checkbox"]:checked');

        var totalPrice = 0;
        var totalMinutes = 0;

        checkboxes.forEach(function (checkbox) {
            totalPrice += parseFloat(checkbox.dataset.price || '0');
            totalMinutes += parseInt(checkbox.dataset.durationMinutes || '0', 10);
        });

        summaryEl.textContent = 'Rs. ' + totalPrice.toFixed(2) + ' · ' + totalMinutes + ' minutes';

        priceInput.value = totalPrice > 0 ? totalPrice.toFixed(2) : '';
        durationInput.value = minutesToLabel(totalMinutes);
    }

    checklist.addEventListener('change', function (event) {

        if (event.target.type !== 'checkbox') {
            return;
        }

        recalculate();
    });

    recalculate();

})();
</script>