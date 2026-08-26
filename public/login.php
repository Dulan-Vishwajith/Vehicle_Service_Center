<?php
session_start();
require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email == "" || $password == "") {
        $message = "Please enter your email and password.";
    } else {

        // The existing database uses user_id, name, password and role_id.
        $stmt = $conn->prepare(
            "SELECT user_id, name, password, role_id FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["role_id"] = $user["role_id"];

                // Send the user back to the main website.
                header("Location: index.php");
                exit();

            } else {
                $message = "Incorrect email or password.";
            }

        } else {
            $message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | VEYRO</title>

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
    padding: 50px 20px;
}

.card {
    width: 100%;
    max-width: 440px;
    background: white;
    border: 1px solid #e1e5ea;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 8px 25px rgba(16, 24, 32, 0.06);
}

.heading {
    text-align: center;
    margin-bottom: 28px;
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
    margin-bottom: 18px;
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
}

.submit-btn:hover {
    background: #d92d3d;
}

.message {
    background: #fff0f1;
    color: #c82032;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 14px;
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


<main class="page">

    <div class="card">

        <div class="heading">
            <div class="small">VEYRO VEHICLE CARE</div>
            <h1>Welcome Back</h1>
            <p>Login to manage your vehicle services and appointments.</p>
        </div>

        <?php if ($message != ""): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

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
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="submit-btn">
                Login
            </button>

        </form>

        <div class="bottom-text">
            Don't have an account?
            <a href="register.php">Register here</a>
        </div>

    </div>

</main>

</body>
</html>