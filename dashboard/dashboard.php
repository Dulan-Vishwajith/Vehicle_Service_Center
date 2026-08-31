<?php
session_start();

// Ensure user is authenticated
if (!isset($_SESSION['user_id']) && !isset($_POST['role_id'])) {
    header("Location: ../login/login-form.php");
    exit();
}

// Set role_id from POST if coming directly from login form, or fallback to SESSION
if (isset($_POST['role_id'])) {
    $_SESSION['role_id'] = intval($_POST['role_id']);
}

$role_id = $_SESSION['role_id'] ?? null;
$role_file = '';

switch ($role_id) {
    case 1:
        $role_file = __DIR__ . '/customer/customer.php';
        break;
    case 2:
        $role_file = __DIR__ . '/serviceAssistant/serviceAssistant.php';
        break;
    case 3:
        $role_file = __DIR__ . '/managementEmployee/managementEmployee.php';
        break;
    default:
        header("Location: ../login/login-form.php?error=invalid_role");
        exit();
}

include __DIR__ . '/dashboard-view.php';
?>