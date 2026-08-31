<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {

    // Build redirect URL, carrying the services[] selection
    // through login (works for both a single service and a package)
    $redirectUrl = "../dashboard/dashboard.php";

    if (!empty($_SERVER["QUERY_STRING"])) {
        $redirectUrl .= "?" . $_SERVER["QUERY_STRING"];
    }

    // Save redirect URL for after login
    $_SESSION["redirect_after_login"] = $redirectUrl;

    // Redirect to login
    header("Location: ../login/login-form.php");
    exit;
}

// Determine the role of the logged-in user
$role_id = $_SESSION['role_id'] ?? null;
$role_file = '';

// Map role_id to the corresponding dashboard view file
switch ($role_id) {

    case 1:
        include 'customer/customer.php';
        break;

    case 2:
        include 'serviceAssistant/serviceAssistant.php';
        break;

    case 3:
        include 'managementEmployee/managementEmployee.php';
        break;

    default:
        header("Location: ../login/login-form.php?error=invalid_role");
        exit();
}
?>