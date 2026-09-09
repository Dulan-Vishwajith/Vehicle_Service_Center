<!-- ================= FOOTER ================= -->

<?php

/*
|--------------------------------------------------------------------------
| LOAD DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Use the existing database configuration.
| Do not create a new database connection.
|
*/

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}


/*
|--------------------------------------------------------------------------
| LOAD WEBSITE CONTACT DETAILS
|--------------------------------------------------------------------------
|
| Default fallback values are used if:
| - contact_details table has no record
| - database query fails
|
*/

$footerContact = [
    'phone'   => '+94 77 123 4567',
    'email'   => 'info@veyro.lk',
    'address' => 'Colombo, Sri Lanka'
];


try {

    /*
    |----------------------------------------------------------------------
    | Get the single active contact record
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT phone, email, address
        FROM contact_details
        ORDER BY id ASC
        LIMIT 1
    ");

    $stmt->execute();

    $contactRecord = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |----------------------------------------------------------------------
    | Replace fallback values with database values
    |----------------------------------------------------------------------
    */

    if ($contactRecord) {

        $footerContact = [
            'phone'   => $contactRecord['phone'],
            'email'   => $contactRecord['email'],
            'address' => $contactRecord['address']
        ];

    }

} catch (PDOException $e) {

    /*
    |----------------------------------------------------------------------
    | Keep fallback values if database query fails
    |----------------------------------------------------------------------
    */

}

?>


<footer class="footer" id="contact">

    <div class="container footer-grid">


        <!-- =====================================================
             BRAND
             ===================================================== -->

        <div class="footer-brand">

            <a
                href="<?= $basePath ?>/index.php#home"
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
             ===================================================== -->

        <div class="footer-column">

            <h3>
                Services
            </h3>


            <a
                href="<?= $basePath ?>/index.php?all=1#services"
            >
                All Services
            </a>


            <a
                href="<?= $basePath ?>/index.php#packages"
            >
                Service Packages
            </a>


            <a
                href="<?= $basePath ?>/index.php#offers"
            >
                Special Offers
            </a>

        </div>



        <!-- =====================================================
             CUSTOMER
             ===================================================== -->

        <div class="footer-column">

            <h3>
                Customer
            </h3>


            <?php if ($isLoggedIn): ?>

                <a
                    href="<?= $basePath ?>/dashboard/dashboard.php"
                >
                    Dashboard
                </a>


                <a
                    href="<?= $basePath ?>/dashboard/dashboard.php?page=profile"
                >
                    Profile
                </a>

            <?php else: ?>

                <a
                    href="<?= $basePath ?>/register/register-form.php"
                >
                    Register
                </a>


                <a
                    href="<?= $basePath ?>/login/login-form.php"
                >
                    Login
                </a>

            <?php endif; ?>


            <a
                href="<?= $basePath ?>/booking/booking.php"
            >
                Book Appointment
            </a>

        </div>



        <!-- =====================================================
             CONTACT
             ===================================================== -->

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
         ===================================================== -->

    <div class="footer-bottom">

        <div class="container">

            <p>

                © <?= date("Y"); ?>

                VEYRO Vehicle Service Centre.

                All Rights Reserved.

            </p>

        </div>

    </div>

</footer>