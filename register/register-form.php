<?php

session_start();

/*
|--------------------------------------------------------------------------
| Check Login Status
|--------------------------------------------------------------------------
| If the user is already logged in, redirect them to the home page.
*/
if (isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Initialize Messages
|--------------------------------------------------------------------------
*/
$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| Get Registration Message
|--------------------------------------------------------------------------
| The register-submit.php file stores messages in the session.
| We retrieve them here and then remove them from the session.
*/
if (isset($_SESSION["register_message"])) {

    $message = $_SESSION["register_message"];
    $messageType = $_SESSION["register_message_type"] ?? "";

    unset($_SESSION["register_message"]);
    unset($_SESSION["register_message_type"]);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Basic Page Settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | VEYRO</title>


    <!-- =========================================================
         CSS FILES
         ========================================================= -->

    <!-- Global CSS -->
    <link rel="stylesheet" href="../includes/css/global.css">

    <!-- Register Page CSS -->
    <link rel="stylesheet" href="css/register.css">

</head>


<body>

    <!-- =========================================================
         REGISTER PAGE
         ========================================================= -->

    <main class="register-page">

        <!-- =====================================================
             REGISTER CARD
             ===================================================== -->

        <div class="register-card">


            <!-- =================================================
                 BACK TO HOME
                 ================================================= -->

            <div class="register-back">

                <a href="../index.php" class="back-btn">
                    ← Back to Home
                </a>

            </div>


            <!-- =================================================
                 REGISTER HEADING
                 ================================================= -->

            <div class="register-heading">

                <span class="section-label">
                    VEYRO VEHICLE CARE
                </span>

                <h1>
                    Create Account
                </h1>

                <p>
                    Register to book and manage your vehicle services.
                </p>

            </div>


            <!-- =================================================
                 MESSAGE
                 ================================================= -->

            <?php if ($message !== ""): ?>

                <div class="register-message <?php echo htmlspecialchars($messageType); ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 REGISTRATION FORM
                 ================================================= -->

            <form
                action="register-submit.php"
                method="POST"
                class="register-form"
            >


                <!-- =============================================
                     FULL NAME
                     ============================================= -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >

                </div>


                <!-- =============================================
                     EMAIL
                     ============================================= -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                </div>


                <!-- =============================================
                     PHONE
                     ============================================= -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        placeholder="07XXXXXXXX"
                        required
                    >

                </div>


                <!-- =============================================
                     PASSWORD
                     ============================================= -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 6 characters"
                        required
                    >

                </div>


                <!-- =============================================
                     CONFIRM PASSWORD
                     ============================================= -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Enter password again"
                        required
                    >

                </div>


                <!-- =============================================
                     SUBMIT BUTTON
                     ============================================= -->

                <button
                    type="submit"
                    class="btn btn-primary register-submit"
                >
                    Create Account
                </button>


            </form>


            <!-- =================================================
                 LOGIN LINK
                 ================================================= -->

            <div class="register-bottom-text">

                <span>
                    Already have an account?
                </span>

                <a href="../login/login-form.php">
                    Login here
                </a>

            </div>


        </div>

    </main>


</body>

</html>