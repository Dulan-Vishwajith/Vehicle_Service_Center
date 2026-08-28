<?php

/*
|--------------------------------------------------------------------------
| VEYRO - Dashboard Controller
|--------------------------------------------------------------------------
|
| This file handles the dashboard logic.
|
| It:
|   1. Starts the user session
|   2. Identifies the logged-in user
|   3. Gets the user's role from the database
|   4. Processes dashboard actions
|   5. Loads the dashboard view
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

session_start();


/*
|--------------------------------------------------------------------------
| Include Required Files
|--------------------------------------------------------------------------
*/

require_once "../config/database.php";

require_once "customer.php";

require_once "salesAssistant.php";

require_once "managementEmployee.php";


/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
|
| The user ID can come from:
|
| 1. POST variable sent from the login process
| 2. Existing session
|
|--------------------------------------------------------------------------
*/

$userId = 0;


if (isset($_POST['user_id'])) {

    $userId = (int) $_POST['user_id'];

} elseif (isset($_SESSION['user_id'])) {

    $userId = (int) $_SESSION['user_id'];

}


/*
|--------------------------------------------------------------------------
| Check User Login
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {

    header("Location: ../login/login-form.php");

    exit();

}


/*
|--------------------------------------------------------------------------
| Get User Information
|--------------------------------------------------------------------------
|
| The user's role is obtained from the database.
|
| Role IDs:
|
|   1 = Customer
|   2 = Service Assistant
|   3 = Management
|   4 = Admin
|
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


$userStmt->bindValue(
    ":user_id",
    $userId,
    PDO::PARAM_INT
);


$userStmt->execute();


$user = $userStmt->fetch(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| Check Whether User Exists
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_unset();

    session_destroy();

    header("Location: ../login/login-form.php");

    exit();

}


/*
|--------------------------------------------------------------------------
| Get User Role
|--------------------------------------------------------------------------
*/

$roleId = (int) $user['role_id'];


/*
|--------------------------------------------------------------------------
| Store User Information In Session
|--------------------------------------------------------------------------
*/

$_SESSION['user_id'] = (int) $user['user_id'];

$_SESSION['user_name'] = $user['name'];

$_SESSION['role_id'] = $roleId;


/*
|--------------------------------------------------------------------------
| Process Dashboard Actions
|--------------------------------------------------------------------------
*/

$message = "";


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['dashboard_action'])
) {

    try {


        /*
        |--------------------------------------------------------------------------
        | Customer Actions
        |--------------------------------------------------------------------------
        */

        if ($roleId === 1) {

            $message = customerAction(
                $pdo,
                $userId,
                $_POST
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Service Assistant Actions
        |--------------------------------------------------------------------------
        */

        elseif ($roleId === 2) {

            $message = salesAssistantAction(
                $pdo,
                $userId,
                $_POST
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Management Actions
        |--------------------------------------------------------------------------
        */

        elseif ($roleId === 3) {

            $message = managementAction(
                $pdo,
                $userId,
                $_POST
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        elseif ($roleId === 4) {

            $message = "Admin dashboard is not available yet.";

        }

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | Database Error
        |--------------------------------------------------------------------------
        |
        | Do not display technical database errors to users.
        |
        |--------------------------------------------------------------------------
        */

        $message = "The requested action could not be completed.";

    }

}


/*
|--------------------------------------------------------------------------
| Load Dashboard View
|--------------------------------------------------------------------------
*/

require_once "dashboard-view.php";

?>