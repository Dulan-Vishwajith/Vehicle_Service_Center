<?php
session_start();

$message = $_SESSION["login_message"] ?? "";
unset($_SESSION["login_message"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | VEYRO</title>

    <link rel="stylesheet" href="../includes/css/global.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

<main class="login-page">

    <div class="login-card">

        <div class="login-heading">

            <div class="login-label">
                VEYRO VEHICLE CARE
            </div>

            <h1>Welcome Back</h1>

            <p>
                Login to manage your vehicle services and appointments.
            </p>

        </div>

        <?php if ($message !== ""): ?>

            <div class="login-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="login-validation.php">

            <div class="login-form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="login-form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="login-submit"
            >
                Login
            </button>

        </form>

        <div class="login-bottom-text">

            Don't have an account?

            <a href="register.php">
                Register here
            </a>

        </div>

    </div>

</main>

</body>
</html>