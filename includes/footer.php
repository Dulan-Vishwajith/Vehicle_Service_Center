<!-- ================= FOOTER ================= -->

<?php

/*
|--------------------------------------------------------------------------
| START SESSION IF NEEDED
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOAD DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Use the existing database configuration.
| Do not create another database connection.
|
*/

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}


/*
|--------------------------------------------------------------------------
| SAFE DEFAULT VALUES
|--------------------------------------------------------------------------
|
| These prevent undefined-variable warnings if the footer is included
| from a page where $basePath or $isLoggedIn was not created earlier.
|
*/

$basePath = $basePath ?? '';

$isLoggedIn = isset($_SESSION['user_id']);

$footerRoleId = isset($_SESSION['role_id'])
    ? (int) $_SESSION['role_id']
    : null;


/*
|--------------------------------------------------------------------------
| LOAD WEBSITE CONTACT DETAILS
|--------------------------------------------------------------------------
|
| These values are used only if the database record cannot be loaded.
|
*/

$footerContact = [
    'phone'   => '+94 77 123 4567',
    'email'   => 'info@veyro.lk',
    'address' => 'Colombo, Sri Lanka'
];


try {

    $stmt = $pdo->prepare("
        SELECT
            phone,
            email,
            address
        FROM contact_details
        ORDER BY id ASC
        LIMIT 1
    ");

    $stmt->execute();

    $contactRecord = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($contactRecord) {

        $footerContact = [
            'phone'   => $contactRecord['phone'],
            'email'   => $contactRecord['email'],
            'address' => $contactRecord['address']
        ];
    }

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE FALLBACK
    |--------------------------------------------------------------------------
    |
    | If the contact query fails, the fallback contact information above
    | will continue to be displayed.
    |
    */
}


/*
|--------------------------------------------------------------------------
| ROLE-SPECIFIC FOOTER NAVIGATION
|--------------------------------------------------------------------------
|
| Guest
|     Customer
|     - Register
|     - Login
|     - Book Appointment
|
| Customer (Role 1)
|     Customer
|     - Dashboard
|     - Profile
|     - Book Appointment
|
| Service Assistant (Role 2)
|     Service Assistant
|     - Dashboard
|     - Profile
|     - My Appointments
|
| Manager (Role 3)
|     Manager
|     - Dashboard
|     - Profile
|     - Monitor Operations
|
*/

$footerSectionTitle = 'Customer';

$footerRoleLinks = [];


/*
|--------------------------------------------------------------------------
| GUEST USER
|--------------------------------------------------------------------------
*/

if (!$isLoggedIn) {

    $footerSectionTitle = 'Customer';

    $footerRoleLinks = [

        [
            'label' => 'Register',
            'url'   => $basePath . '/register/register-form.php'
        ],

        [
            'label' => 'Login',
            'url'   => $basePath . '/login/login-form.php'
        ],

        [
            'label' => 'Book Appointment',
            'url'   => $basePath . '/booking/booking.php'
        ]

    ];


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

} elseif ($footerRoleId === 1) {

    $footerSectionTitle = 'Customer';

    $footerRoleLinks = [

        [
            'label' => 'Dashboard',
            'url'   => $basePath . '/dashboard/dashboard.php'
        ],

        [
            'label' => 'Profile',
            'url'   => $basePath . '/dashboard/dashboard.php?page=profile'
        ],

        [
            'label' => 'Book Appointment',
            'url'   => $basePath . '/booking/booking.php'
        ]

    ];


/*
|--------------------------------------------------------------------------
| SERVICE ASSISTANT
|--------------------------------------------------------------------------
*/

} elseif ($footerRoleId === 2) {

    $footerSectionTitle = 'Service Assistant';

    $footerRoleLinks = [

        [
            'label' => 'Dashboard',
            'url'   => $basePath . '/dashboard/dashboard.php'
        ],

        [
            'label' => 'Profile',
            'url'   => $basePath . '/dashboard/dashboard.php?page=profile'
        ],

        [
            'label' => 'My Appointments',
            'url'   => $basePath . '/dashboard/dashboard.php?page=appointments'
        ]

    ];


/*
|--------------------------------------------------------------------------
| MANAGER
|--------------------------------------------------------------------------
*/

} elseif ($footerRoleId === 3) {

    $footerSectionTitle = 'Manager';

    $footerRoleLinks = [

        [
            'label' => 'Dashboard',
            'url'   => $basePath . '/dashboard/dashboard.php'
        ],

        [
            'label' => 'Profile',
            'url'   => $basePath . '/dashboard/dashboard.php?page=profile'
        ],

        [
            'label' => 'Monitor Operations',
            'url'   => $basePath . '/dashboard/dashboard.php'
        ]

    ];


/*
|--------------------------------------------------------------------------
| OTHER LOGGED-IN ROLE
|--------------------------------------------------------------------------
|
| Safe fallback for another role such as Admin.
|
*/

} else {

    $footerSectionTitle = 'Account';

    $footerRoleLinks = [

        [
            'label' => 'Dashboard',
            'url'   => $basePath . '/dashboard/dashboard.php'
        ]

    ];
}

?>


<footer class="footer" id="contact">

    <div class="container footer-grid">


        <!-- =====================================================
             BRAND
        ====================================================== -->

        <div class="footer-brand">

            <a
                href="<?= htmlspecialchars($basePath) ?>/index.php#home"
                class="logo"
            >

                <span class="logo-icon">
                    ⚙
                </span>

                <span class="logo-text">
                    VEYRO
                </span>

            </a>


            <p>
                Smart vehicle service and maintenance
                designed for a better driving experience.
            </p>

        </div>


        <!-- =====================================================
             SERVICES
        ====================================================== -->

        <div class="footer-column">

            <h3>
                Services
            </h3>


            <a
                href="<?= htmlspecialchars($basePath) ?>/index.php?all=1#services"
            >
                All Services
            </a>


            <a
                href="<?= htmlspecialchars($basePath) ?>/index.php#packages"
            >
                Service Packages
            </a>


            <a
                href="<?= htmlspecialchars($basePath) ?>/index.php#offers"
            >
                Special Offers
            </a>

        </div>


        <!-- =====================================================
             ROLE-SPECIFIC NAVIGATION
        ====================================================== -->

        <div class="footer-column">

            <h3>
                <?= htmlspecialchars($footerSectionTitle) ?>
            </h3>


            <?php foreach ($footerRoleLinks as $footerLink): ?>

                <a
                    href="<?= htmlspecialchars($footerLink['url']) ?>"
                >
                    <?= htmlspecialchars($footerLink['label']) ?>
                </a>

            <?php endforeach; ?>

        </div>


        <!-- =====================================================
             CONTACT
        ====================================================== -->

        <div class="footer-column">

            <h3>
                Contact
            </h3>


            <!-- PHONE -->

            <p>

                <span class="contact-icon">
                    📞
                </span>

                <?= htmlspecialchars($footerContact['phone']) ?>

            </p>


            <!-- EMAIL -->

            <p>

                <span class="contact-icon">
                    ✉
                </span>

                <?= htmlspecialchars($footerContact['email']) ?>

            </p>


            <!-- ADDRESS -->

            <p>

                <span class="contact-icon">
                    📍
                </span>

                <?= htmlspecialchars($footerContact['address']) ?>

            </p>

        </div>

    </div>


    <!-- =====================================================
         FOOTER BOTTOM
    ====================================================== -->

    <div class="footer-bottom">

        <div class="container">

            <p>
                © <?= date('Y') ?>
                VEYRO Vehicle Service Centre.
                All Rights Reserved.
            </p>

        </div>

    </div>

</footer>