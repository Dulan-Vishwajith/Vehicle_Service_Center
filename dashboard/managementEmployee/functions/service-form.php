<?php

/*
|--------------------------------------------------------------------------
| SERVICE FORM
|--------------------------------------------------------------------------
| One form for both Create (no id) and Edit (valid id).
*/

$serviceId = (int) ($_GET['id'] ?? 0);

$service = [
    'service_name' => '',
    'category' => 'maintenance',
    'description' => '',
    'price' => '',
    'duration' => '',
    'duration_minutes' => '',
    'icon' => '🔧',
    'status' => 1
];

$formError = '';
$isEdit = false;


if ($serviceId > 0) {

    try {

        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
        $stmt->execute([$serviceId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $service = array_merge($service, $existing);
            $isEdit = true;
        } else {
            $formError = "That service could not be found. Creating a new one instead.";
        }

    } catch (PDOException $e) {

        $formError = "Unable to load this service.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {

    $serviceName = trim($_POST['service_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
    $icon = trim($_POST['icon'] ?? '🔧');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $service = array_merge($service, $_POST);
    $service['status'] = $status;

    if ($serviceName === '' || $category === '' || $description === '' || $duration === '') {

        $formError = "Please fill in all required fields.";

    } elseif ($price <= 0) {

        $formError = "Please enter a valid price greater than zero.";

    } elseif ($durationMinutes <= 0) {

        $formError = "Please enter the duration in minutes.";

    } else {

        try {

            if ($isEdit) {

                $stmt = $pdo->prepare("
                    UPDATE services SET
                        service_name = ?, category = ?, description = ?,
                        price = ?, duration = ?, duration_minutes = ?,
                        icon = ?, status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $serviceName, $category, $description, $price,
                    $duration, $durationMinutes, $icon, $status, $serviceId
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO services
                        (service_name, category, description, price, duration, duration_minutes, icon, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $serviceName, $category, $description, $price,
                    $duration, $durationMinutes, $icon, $status
                ]);
            }

            header("Location: ?page=services");
            exit();

        } catch (PDOException $e) {

            $formError = "Unable to save this service. Please try again.";
        }
    }
}

?>


<div class="panel-header">
    <h2><?= $isEdit ? 'Edit Service' : 'New Service' ?></h2>
    <a href="?page=services">&larr; Back to Services</a>
</div>


<?php if ($formError): ?>
    <div class="admin-message error"><?= htmlspecialchars($formError) ?></div>
<?php endif; ?>


<form method="post" class="admin-form">

    <div class="admin-form-grid">

        <div class="admin-form-group">
            <label>Service Name</label>
            <input type="text" name="service_name" value="<?= htmlspecialchars($service['service_name']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Category</label>
            <select name="category">
                <?php foreach (['maintenance', 'repair', 'inspection'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= $service['category'] === $cat ? 'selected' : '' ?>>
                        <?= ucfirst($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-form-group admin-form-full">
            <label>Description</label>
            <textarea name="description" rows="3" required><?= htmlspecialchars($service['description']) ?></textarea>
        </div>

        <div class="admin-form-group">
            <label>Price (Rs.)</label>
            <input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string) $service['price']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Duration Label</label>
            <input type="text" name="duration" placeholder="e.g. 1 Hour" value="<?= htmlspecialchars($service['duration']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Duration (Minutes)</label>
            <input type="number" min="1" name="duration_minutes" value="<?= htmlspecialchars((string) $service['duration_minutes']) ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Icon (emoji)</label>
            <input type="text" name="icon" maxlength="10" value="<?= htmlspecialchars($service['icon']) ?>">
        </div>

        <div class="admin-form-group">
            <label>Status</label>
            <select name="status">
                <option value="1" <?= $service['status'] ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !$service['status'] ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

    </div>

    <div class="admin-form-actions">
        <a href="?page=services" class="admin-cancel-btn">Cancel</a>
        <button type="submit" name="save_service" value="1" class="admin-save-btn">
            <?= $isEdit ? 'Save Changes' : 'Create Service' ?>
        </button>
    </div>

</form>
