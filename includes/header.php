<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["user_id"]);

/*
 * Default profile image
 * Change this path if your image is stored somewhere else.
 */
$profileImage = "./public/images/profile.jpg";

?>

<header class="site-header">

    <div class="container header-container">

        <!-- =====================================================
             LOGO
             ===================================================== -->
        <a href="#home" class="logo">

            <span class="logo-icon">⚙</span>

            <span class="logo-text">
                VEYRO
            </span>

        </a>


        <!-- =====================================================
             NAVIGATION
             ===================================================== -->
        <nav class="main-nav">

            <a href="#home" class="active">
                Home
            </a>

            <a href="#services">
                Services
            </a>

            <a href="#packages">
                Packages
            </a>

            <a href="#offers">
                Offers
            </a>

            <a href="#contact">
                Contact
            </a>

        </nav>


        <!-- =====================================================
             AUTHENTICATION
             ===================================================== -->
        <div class="auth-buttons">

            <?php if ($isLoggedIn): ?>

                <!-- Logged In -->

                <a
                    href="./public/dashboard.php"
                    class="dashboard-btn"
                >
                    Dashboard
                </a>


                <!-- Profile Picture -->

                <a
                    href="./public/profile.php"
                    class="profile-avatar"
                    title="Profile"
                >

                    <img
                        src="<?php echo htmlspecialchars($profileImage); ?>"
                        alt="Profile"
                    >

                </a>


            <?php else: ?>

                <!-- Not Logged In -->

                <a
                    href="./public/login.php"
                    class="login-btn"
                >
                    Login
                </a>


                <a
                    href="./public/register.php"
                    class="register-btn"
                >
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>