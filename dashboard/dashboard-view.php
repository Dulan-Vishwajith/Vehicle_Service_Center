<?php
// Include standard header layout
include_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="dashboard.css">

<div class="dashboard-wrapper">
    <main class="dashboard-content">
        <?php 
        if (isset($role_file) && file_exists($role_file)) {
            include $role_file;
        } else {
            echo "<p>Error: Dashboard view module not found.</p>";
        }
        ?>
    </main>
</div>

<?php
// Include standard footer layout
include_once __DIR__ . '/../includes/footer.php';
?>