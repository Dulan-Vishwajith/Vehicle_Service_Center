<?php
session_start();

// If user is already logged in, don't allow access to login page
if (isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

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

        <!-- Back Button -->
        <div class="login-back">
            <a href="../index.php" class="back-btn">
                ← Back to Home
            </a>
        </div>


        <!-- Heading -->
        <div class="login-heading">

            <div class="login-label">
                VEYRO VEHICLE CARE
            </div>

            <h1>Welcome Back</h1>

            <p>
                Login to manage your vehicle services and appointments.
            </p>

        </div>


        <!-- Message -->
        <?php if ($message !== ""): ?>

            <div class="login-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <!-- Login Form -->
        <form
            method="POST"
            action="login-validation.php"
        >

            <!-- Email -->
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


            <!-- Password -->
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


            <!-- Submit -->
            <button
                type="submit"
                class="login-submit"
            >
                Login
            </button>

        </form>


        <!-- Register Link -->
        <div class="login-bottom-text">

            Don't have an account?

            <a href="../register/register-form.php">
                Register here
            </a>

        </div>

    </div>

</main>

</body>
</html>