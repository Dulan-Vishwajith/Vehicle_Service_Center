<?php
session_start();
require_once "../config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($name == "" || $email == "" || $phone == "" || $password == "") {
        $message = "Please fill in all fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password != $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } else {

        // Check whether the email is already registered.
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "An account with this email already exists.";
            $messageType = "error";

        } else {

            // The existing roles table has Customer = role_id 1.
            $roleId = 1;

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into the existing users table.
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, phone, role_id)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssi",
                $name,
                $email,
                $hashedPassword,
                $phone,
                $roleId
            );

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | VEYRO</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f6f8;
    color: #101820;
}

.navbar {
    height: 70px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 8%;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 22px;
    font-weight: bold;
}

.logo-box {
    width: 34px;
    height: 34px;
    background: #ed3344;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-links {
    display: flex;
    gap: 20px;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: #101820;
    font-size: 14px;
    font-weight: 600;
}

.nav-links a:hover {
    color: #ed3344;
}

.login-btn {
    border: 1px solid #d9dee5;
    padding: 11px 20px;
    border-radius: 7px;
}

.register-btn {
    background: #ed3344;
    color: white !important;
    padding: 11px 20px;
    border-radius: 7px;
}

.page {
    min-height: calc(100vh - 70px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.card {
    width: 100%;
    max-width: 500px;
    background: white;
    border: 1px solid #e1e5ea;
    border-radius: 12px;
    padding: 38px;
    box-shadow: 0 8px 25px rgba(16, 24, 32, 0.06);
}

.heading {
    text-align: center;
    margin-bottom: 25px;
}

.heading .small {
    color: #ed3344;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 2px;
}

.heading h1 {
    margin: 8px 0;
    font-size: 30px;
}

.heading p {
    color: #687386;
    font-size: 14px;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #ccd2da;
    border-radius: 7px;
    font-size: 14px;
    outline: none;
}

input:focus {
    border-color: #ed3344;
}

.submit-btn {
    width: 100%;
    border: 0;
    background: #ed3344;
    color: white;
    padding: 14px;
    border-radius: 7px;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
    margin-top: 5px;
}

.submit-btn:hover {
    background: #d92d3d;
}

.message {
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 14px;
}

.error {
    background: #fff0f1;
    color: #c82032;
}

.success {
    background: #eefbf2;
    color: #23733b;
}

.bottom-text {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #687386;
}

.bottom-text a {
    color: #ed3344;
    font-weight: bold;
    text-decoration: none;
}

@media (max-width: 800px) {
    .navbar {
        padding: 0 5%;
    }

    .nav-links a:not(.login-btn):not(.register-btn) {
        display: none;
    }
}
</style>
</head>

<body>

<header class="navbar">

    <div class="logo">
        <div class="logo-box">⚙</div>
        VEYRO
    </div>

    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="index.php#services">Services</a>
        <a href="index.php#packages">Packages</a>
        <a href="index.php#offers">Offers</a>
        <a href="index.php#contact">Contact</a>
        <a class="login-btn" href="login.php">Login</a>
        <a class="register-btn" href="register.php">Register</a>
    </nav>

</header>

<main class="page">

    <div class="card">

        <div class="heading">
            <div class="small">VEYRO VEHICLE CARE</div>
            <h1>Create Account</h1>
            <p>Register to book and manage your vehicle services.</p>
        </div>

        <?php if ($message != ""): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="07XXXXXXXX"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="At least 6 characters"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Enter password again"
                    required
                >
            </div>

            <button type="submit" class="submit-btn">
                Create Account
            </button>

        </form>

        <div class="bottom-text">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>

    </div>

</main>

</body>
</html>