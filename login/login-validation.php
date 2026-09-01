<?php

/*
|--------------------------------------------------------------------------
| START SESSION SAFELY
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login-form.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($email === "" || $password === "") {

    $_SESSION["login_message"] =
        "Please enter your email and password.";

    header("Location: login-form.php");
    exit();
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION["login_message"] =
        "Please enter a valid email address.";

    header("Location: login-form.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| FIND USER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT user_id, name, password, role_id
     FROM users
     WHERE email = ?"
);

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if ($user && password_verify($password, $user["password"])) {


    /*
    |--------------------------------------------------------------------------
    | REGENERATE SESSION ID
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | STORE USER INFORMATION
    |--------------------------------------------------------------------------
    */

    $_SESSION["user_id"] = $user["user_id"];

    $_SESSION["user_name"] = $user["name"];

    $_SESSION["role_id"] = $user["role_id"];


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BACK TO PREVIOUS PAGE
    |--------------------------------------------------------------------------
    */

    if (isset($_SESSION["redirect_after_login"])) {

        $redirect = $_SESSION["redirect_after_login"];

        /*
        | Remove redirect after using it.
        | This prevents the redirect from being reused later.
        */

        unset($_SESSION["redirect_after_login"]);


        /*
        | Redirect to booking page
        */

        header("Location: " . $redirect);
        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | NORMAL LOGIN
    |--------------------------------------------------------------------------
    | If the user did not come from booking,
    | send them to the home page.
    |--------------------------------------------------------------------------
    */

    header("Location: ../dashboard/dashboard.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| LOGIN FAILED
|--------------------------------------------------------------------------
*/

$_SESSION["login_message"] ="Incorrect email or password.";

header("Location: login-form.php");
exit();

?>