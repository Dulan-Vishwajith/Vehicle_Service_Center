<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["user_id"]);

/*
|--------------------------------------------------------------------------
| Project Root
|--------------------------------------------------------------------------
| Your project folder is:
| Vehicle_Service_Center
|
| If you rename the project folder, change this value.
|--------------------------------------------------------------------------
*/
$basePath = "/Vehicle_Service_Center";


/*
|--------------------------------------------------------------------------
| Profile Image
|--------------------------------------------------------------------------
*/
$profileImage = $basePath . "/public/images/profile.png";

?>

<header class="site-header">

    <div class="container header-container">

        <!-- =====================================================
             LOGO
             ===================================================== -->
        <a href="<?= $basePath ?>/index.php#home" class="logo">

            <span class="logo-icon">⚙</span>

            <span class="logo-text">
                VEYRO
            </span>

        </a>


        <!-- =====================================================
             NAVIGATION
             ===================================================== -->
        <nav class="main-nav">

            <a
                href="<?= $basePath ?>/index.php#home"
                class="active"
            >
                Home
            </a>


            <a
                href="<?= $basePath ?>/index.php#services"
            >
                Services
            </a>


            <a
                href="<?= $basePath ?>/index.php#packages"
            >
                Packages
            </a>


            <a
                href="<?= $basePath ?>/index.php#offers"
            >
                Offers
            </a>


            <a
                href="#contact"
            >
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
                    href="<?= $basePath ?>/dashboard/dashboard.php"
                    class="dashboard-btn"
                >
                    Dashboard
                </a>


            <!-- Profile Dropdown -->

            <div class="profile-dropdown">

                <button
                    type="button"
                    class="profile-avatar profile-dropdown-toggle"
                    title="Profile menu"
                    aria-label="Open profile menu"
                >
                    <img
                        src="<?= htmlspecialchars($profileImage) ?>"
                        alt="Profile"
                    >
                </button>


                <!-- Dropdown Menu -->
                <div class="profile-dropdown-menu">

                    <!-- Profile -->
                    <a href="<?= $basePath ?>/dashboard/dashboard.php?page=profile">
                        Profile
                    </a>


                    <!-- Dashboard -->
                    <a href="<?= $basePath ?>/dashboard/dashboard.php">
                        Dashboard
                    </a>


                    <!-- My Bookings -->
                    <a href="<?= $basePath ?>/dashboard/dashboard.php?page=bookings">
                        My Bookings
                    </a>


                    <div class="dropdown-divider"></div>


                    <!-- Logout -->
                    <a
                        href="<?= $basePath ?>/login/logout.php"
                        class="logout-link"
                    >
                        Logout
                    </a>

                </div>

            </div>


        <?php else: ?>
                <!-- Not Logged In -->

                <a
                    href="<?= $basePath ?>/login/login-form.php"
                    class="login-btn"
                >
                    Login
                </a>


                <a
                    href="<?= $basePath ?>/register/register-form.php"
                    class="register-btn"
                >
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>

<script src="<?= $basePath ?>/includes/js/profile-dropdown.js"></script>