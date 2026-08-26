<?php

session_start();

require_once "../config/database.php";


// Get form values
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";


// Validation
if (
    $name == "" ||
    $email == "" ||
    $phone == "" ||
    $password == ""
) {

    $_SESSION["register_message"] = "Please fill in all fields.";
    $_SESSION["register_message_type"] = "error";

    header("Location: register-form.php");
    exit;
}


// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION["register_message"] = "Please enter a valid email address.";
    $_SESSION["register_message_type"] = "error";

    header("Location: register-form.php");
    exit;
}


// Check password confirmation
if ($password != $confirmPassword) {

    $_SESSION["register_message"] = "Passwords do not match.";
    $_SESSION["register_message_type"] = "error";

    header("Location: register-form.php");
    exit;
}


// Check password length
if (strlen($password) < 6) {

    $_SESSION["register_message"] =
        "Password must contain at least 6 characters.";

    $_SESSION["register_message_type"] = "error";

    header("Location: register-form.php");
    exit;
}


// Check whether email already exists
$check = $pdo->prepare(
    "SELECT user_id FROM users WHERE email = ?"
);

$check->execute([$email]);


if ($check->fetch()) {

    $_SESSION["register_message"] =
        "An account with this email already exists.";

    $_SESSION["register_message_type"] = "error";

    header("Location: register-form.php");
    exit;
}


// Customer role
$roleId = 1;


// Hash password
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);


// Insert user
$stmt = $pdo->prepare(
    "INSERT INTO users
    (name, email, password, phone, role_id)
    VALUES (?, ?, ?, ?, ?)"
);


$success = $stmt->execute([
    $name,
    $email,
    $hashedPassword,
    $phone,
    $roleId
]);


if ($success) {

    // Get ID of newly created user
    $userId = $pdo->lastInsertId();

    // Log the user in
    $_SESSION["user_id"] = $userId;
    $_SESSION["user_name"] = $name;
    $_SESSION["role_id"] = $roleId;

    $_SESSION["register_message"] =
        "Registration successful! You are now logged in.";

    $_SESSION["register_message_type"] = "success";

    // Redirect to home page
    header("Location: ../index.php");
    exit;

} else {

    $_SESSION["register_message"] =
        "Registration failed. Please try again.";

    $_SESSION["register_message_type"] = "error";

    // Return to registration form
    header("Location: register-form.php");
    exit;
}

?>