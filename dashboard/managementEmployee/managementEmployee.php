<?php
// Dynamic function module inclusion for Management Employee
$action = $_GET['action'] ?? 'default';
$functions_dir = __DIR__ . '/functions/';

// Sanitize input to prevent directory traversal
$action = basename($action);
$target_file = $functions_dir . $action . '.php';

if ($action !== 'default' && file_exists($target_file)) {
    include $target_file;
} else {
    echo "<h2>Management Dashboard</h2>";
    echo "<p>Welcome to the Management portal. Select an administrative tool to begin.</p>";
}
?>