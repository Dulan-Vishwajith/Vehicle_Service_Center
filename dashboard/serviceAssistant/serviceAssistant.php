<?php
// Dynamic function module inclusion for Service Assistant
$action = $_GET['action'] ?? 'default';
$functions_dir = __DIR__ . '/functions/';

// Sanitize input to prevent directory traversal
$action = basename($action);
$target_file = $functions_dir . $action . '.php';

if ($action !== 'default' && file_exists($target_file)) {
    include $target_file;
} else {
    echo "<h2>Service Assistant Dashboard</h2>";
    echo "<p>Welcome to the Service Assistant portal. Select an action from the menu.</p>";
}
?>