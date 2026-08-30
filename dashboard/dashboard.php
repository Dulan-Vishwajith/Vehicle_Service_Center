<?php

/*
|--------------------------------------------------------------------------
| VEYRO - Dashboard Controller
|--------------------------------------------------------------------------
| Identifies the authenticated user, verifies the user's role from the
| database, processes dashboard actions, and loads the dashboard view.
|
| Role IDs:
|   1 = Customer
|   2 = Service Assistant
|   3 = Management
|   4 = Admin
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/customer/customer.php";
require_once __DIR__ . "/salesAssistant/salesAssistant.php";
require_once __DIR__ . "/managementEmployee/managementEmployee.php";

/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
| The login form may send user_id through POST. If the user already has
| an active session, the session value is used instead.
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION["user_id"] ?? 0);

/*
 * If there is no authenticated session, allow the login flow to pass the
 * user ID through POST. Once a session exists, never replace it with a
 * user ID supplied by the browser.
 */
if ($userId <= 0 && isset($_POST["user_id"])) {
    $userId = (int) $_POST["user_id"];
}

if ($userId <= 0) {
    header("Location: ../login/login-form.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Get User From Database
|--------------------------------------------------------------------------
| The database is the source of truth for the user's role.
| A hidden POST role value is never trusted by itself.
|--------------------------------------------------------------------------
*/

$userQuery = "
    SELECT
        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.role_id,
        r.role_name
    FROM users AS u
    LEFT JOIN roles AS r
        ON u.role_id = r.role_id
    WHERE u.user_id = :user_id
";

$userStmt = $pdo->prepare($userQuery);
$userStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$userStmt->execute();

$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: ../login/login-form.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Verify Optional Hidden POST Role
|--------------------------------------------------------------------------
| The login page can send role_id as a hidden field. It is checked against
| the database, but the verified database value is always used.
|--------------------------------------------------------------------------
*/

if (isset($_POST["role_id"])) {
    $postedRoleId = (int) $_POST["role_id"];
    $databaseRoleId = (int) $user["role_id"];

    if ($postedRoleId !== $databaseRoleId) {
        header("Location: ../login/login-form.php");
        exit();
    }
}

$roleId = (int) $user["role_id"];

/*
|--------------------------------------------------------------------------
| Store Authenticated User Information
|--------------------------------------------------------------------------
*/

$_SESSION["user_id"] = (int) $user["user_id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["role_id"] = $roleId;

/*
|--------------------------------------------------------------------------
| Process Dashboard Actions
|--------------------------------------------------------------------------
*/

$message = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["dashboard_action"])
) {
    try {
        switch ($roleId) {
            case 1:
                $message = customerHandleAction($pdo, $userId, $_POST);
                break;

            case 2:
                $message = salesAssistantHandleAction($pdo, $userId, $_POST);
                break;

            case 3:
                $message = managementHandleAction($pdo, $userId, $_POST);
                break;

            case 4:
                $message = "Admin dashboard is not included in this version.";
                break;

            default:
                $message = "Your account role is not configured correctly.";
        }
    } catch (PDOException $e) {
        $message = "The requested action could not be completed.";
    }
}

/*
|--------------------------------------------------------------------------
| Load Dashboard View
|--------------------------------------------------------------------------
*/

require __DIR__ . "/dashboard-view.php";
?>
