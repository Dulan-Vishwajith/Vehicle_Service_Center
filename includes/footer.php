<!-- ================= FOOTER ================= -->

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
                    href="<?= $basePath ?>/public/dashboard.php"
                >
                    Dashboard
                </a>

                <a
                    href="<?= $basePath ?>/public/profile.php"
                >
                    Profile
                </a>

                <a
                    href="<?= $basePath ?>/booking/booking.php"
                >
                    Book Appointment
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

                <a
                    href="<?= $basePath ?>/public/booking.php"
                >
                    Book Appointment
                </a>

            <?php endif; ?>

        </div>


        <!-- =====================================================
             CONTACT
             ===================================================== -->
        <div class="footer-column">

            <h3>
                Contact
            </h3>

            <p>
                <span class="contact-icon">📞</span>
                +94 77 123 4567
            </p>

            <p>
                <span class="contact-icon">✉</span>
                info@veyro.lk
            </p>

            <p>
                <span class="contact-icon">📍</span>
                Colombo, Sri Lanka
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
