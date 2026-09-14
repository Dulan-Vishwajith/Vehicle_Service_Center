<?php

/*
|--------------------------------------------------------------------------
| PACKAGE FORM
|--------------------------------------------------------------------------
| One form for both Create (no id) and Edit (valid id).
|
| Matches the package fields used by this form:
| id, package_name, duration, status, created_at.
| Package price is not stored in service_packages.
| Service prices remain part of the individual services.
|
| Duration Label auto-fills from whichever services are checked
| (see the <script> block at the bottom).
*/

$packageId = (int) ($_GET['id'] ?? 0);

$package = [
    'package_name' => '',
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
    $duration = trim($_POST['duration'] ?? '');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $postedServiceIds = array_map('intval', $_POST['services'] ?? []);
    $postedServiceIds = array_values(array_unique(array_filter($postedServiceIds, fn($id) => $id > 0)));

    $package = array_merge($package, $_POST);
    $package['status'] = $status;
    $selectedServiceIds = $postedServiceIds;

    if ($packageName === '' || $duration === '') {

        $formError = "Please fill in all required fields.";

    } elseif (empty($postedServiceIds)) {

        $formError = "Please select at least one included service.";

    } else {

        try {

            $pdo->beginTransaction();

            if ($isEdit) {

                $stmt = $pdo->prepare("
                    UPDATE service_packages SET
                        package_name = ?, duration = ?, status = ?
                    WHERE id = ?
                ");

                $stmt->execute([$packageName, $duration, $status, $packageId]);

                $stmt = $pdo->prepare("DELETE FROM package_services WHERE package_id = ?");
                $stmt->execute([$packageId]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO service_packages (package_name, duration, status)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([$packageName, $duration, $status]);

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
                                data-duration-minutes="<?= htmlspecialchars((string) ($svc['duration_minutes'] ?? 0)) ?>"
                                <?= in_array((int) $svc['id'], $selectedServiceIds, true) ? 'checked' : '' ?>
                            >
                            <?= htmlspecialchars($svc['service_name']) ?>
                        </label>

                    <?php endforeach; ?>

                </div>

                <p class="admin-checklist-total">
                    Selected duration:
                    <strong id="packageSelectedSummary">0 minutes</strong>
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

    var durationInput = document.getElementById('packageDuration');
    var summaryEl = document.getElementById('packageSelectedSummary');

    // Recalculate the duration from the selected services.

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

        var totalMinutes = 0;

        checkboxes.forEach(function (checkbox) {
            totalMinutes += parseInt(checkbox.dataset.durationMinutes || '0', 10);
        });

        summaryEl.textContent = totalMinutes + ' minutes';
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