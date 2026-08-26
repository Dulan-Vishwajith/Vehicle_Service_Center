<?php

session_start();

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login-form.php");
    exit();
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";


/* =========================================================
   VALIDATION
   ========================================================= */

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


/* =========================================================
   FIND USER
   ========================================================= */

$stmt = $pdo->prepare(
    "SELECT user_id, name, password, role_id
     FROM users
     WHERE email = ?"
);

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   CHECK LOGIN
   ========================================================= */

if ($user && password_verify($password, $user["password"])) {

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["role_id"] = $user["role_id"];

    header("Location: ../index.php");
    exit();
}


/* =========================================================
   LOGIN FAILED
   ========================================================= */

$_SESSION["login_message"] =
    "Incorrect email or password.";

header("Location: login-form.php");
exit();

?>