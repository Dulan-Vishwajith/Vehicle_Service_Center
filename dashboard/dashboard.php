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

?>

<!-- Stylesheets -->
<link rel="stylesheet" href="../includes/css/header.css"> 
<link rel="stylesheet" href="../includes/css/global.css"> 
<link rel="stylesheet" href="./css/dashboard.css"> 
<link rel="stylesheet" href="../includes/css/footer.css"> 


<!-- Include the header -->
<?php   include_once '../includes/header.php';  ?> 


<?php

// Include the appropriate dashboard based on the user's role
switch ($role_id) {

    case 1:
        include __DIR__ . '/customer/customer.php';
        break;

    case 2:
        include __DIR__ . '/serviceAssistant/serviceAssistant.php';
        break;

    case 3:
        include __DIR__ . '/managementEmployee/managementEmployee.php';
        break;

    default:
        header("Location: ../login/login-form.php?error=invalid_role");
        exit();
}
?>

<!--Include the footer -->
<?php   include_once '../includes/footer.php';  ?>