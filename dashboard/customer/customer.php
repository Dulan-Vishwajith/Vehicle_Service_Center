<?php
// Dynamic function module inclusion for Customer
$action = $_GET['action'] ?? 'default';
$functions_dir = __DIR__ . '/functions/';

// Sanitize input to prevent directory traversal
$action = basename($action);
$target_file = $functions_dir . $action . '.php';

if ($action !== 'default' && file_exists($target_file)) {
    include $target_file;
} else {
    echo "<h2>Customer Dashboard</h2>";
    echo "<p>Welcome to your customer portal. Select an option to proceed.</p>";
}
?>