<?php

require_once "config/database.php";

/* =========================================================
   GET ACTIVE PACKAGES
   ========================================================= */

$packageStatus = 1;

$packageSQL = "
    SELECT
        id,
        package_name,
        price,
        duration
    FROM service_packages
    WHERE status = ?
    ORDER BY id ASC
";

$packageStmt = $pdo->prepare($packageSQL);
$packageStmt->execute([$packageStatus]);

$packages = $packageStmt->fetchAll();


/* =========================================================
   GET SERVICES FOR EACH PACKAGE
   ========================================================= */

$serviceSQL = "
    SELECT
        s.id,
        s.service_name
    FROM package_services ps

    INNER JOIN services s
        ON ps.service_id = s.id

    WHERE ps.package_id = ?

    ORDER BY s.id ASC
";

$serviceStmt = $pdo->prepare($serviceSQL);


/* =========================================================
   ADD SERVICES TO EACH PACKAGE
   ========================================================= */

foreach ($packages as &$package) {

    $packageId = $package['id'];

    $serviceStmt->execute([$packageId]);

    $package['services'] = $serviceStmt->fetchAll();
}

unset($package);


/* =========================================================
   FEATURED PACKAGE
   ========================================================= */

$featuredPackageId = null;

if (isset($packages[1])) {

    $featuredPackageId = $packages[1]['id'];
}

?>


<!-- =========================================================
     PACKAGES SECTION
     ========================================================= -->

<section class="section packages-section" id="packages">

    <div class="container">


        <!-- =================================================
             SECTION HEADING
             ================================================= -->

        <div class="section-heading">

            <span class="section-label">
                SERVICE PACKAGES
            </span>

            <h2>
                Choose The Right Package
            </h2>

            <p>
                Convenient service packages for different
                vehicle maintenance needs.
            </p>

        </div>


        <!-- =================================================
             PACKAGE GRID
             ================================================= -->

        <div class="packages-grid">


            <?php if (!empty($packages)): ?>


                <?php foreach ($packages as $package): ?>


                    <?php

                    $isFeatured =
                        ($package['id'] == $featuredPackageId);

                    ?>


                    <!-- =================================================
                         PACKAGE CARD
                         ================================================= -->

                    <div
                        class="package-card
                        <?php
                        echo $isFeatured
                            ? 'featured-package'
                            : '';
                        ?>"
                    >


                        <!-- =============================================
                             POPULAR LABEL
                             ============================================= -->

                        <?php if ($isFeatured): ?>

                            <span class="popular-label">
                                MOST POPULAR
                            </span>

                        <?php endif; ?>


                        <!-- =============================================
                             PACKAGE LABEL
                             ============================================= -->

                        <span class="package-label">

                            <?php

                            if ($isFeatured) {

                                echo "POPULAR";

                            } elseif ($package['id'] == 1) {

                                echo "BASIC";

                            } else {

                                echo "PREMIUM";

                            }

                            ?>

                        </span>


                        <!-- =============================================
                             PACKAGE NAME
                             ============================================= -->

                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $package['package_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </h3>


                        <!-- =============================================
                             PRICE
                             ============================================= -->

                        <div class="package-price">

                            Rs.

                            <?php

                            echo number_format(
                                (float) $package['price'],
                                2
                            );

                            ?>

                        </div>


                        <!-- =============================================
                             DURATION
                             ============================================= -->

                        <div class="package-duration">

                            Duration:

                            <?php

                            echo htmlspecialchars(
                                $package['duration'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </div>


                        <!-- =============================================
                             INCLUDED SERVICES
                             ============================================= -->

                        <ul>

                            <?php if (!empty($package['services'])): ?>


                                <?php foreach (
                                    $package['services']
                                    as $service
                                ): ?>


                                    <li>

                                        ✓

                                        <?php

                                        echo htmlspecialchars(
                                            $service['service_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>

                                    </li>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <li>
                                    No services available
                                </li>


                            <?php endif; ?>

                        </ul>


                        <!-- =============================================
                             CHOOSE PACKAGE BUTTON
                             ============================================= -->

                        <?php if ($isFeatured): ?>


                            <a
                                href="book-appointment.php?package_id=<?php echo (int) $package['id']; ?>"
                                class="btn btn-red"
                            >
                                Choose Package
                            </a>


                        <?php else: ?>


                            <a
                                href="book-appointment.php?package_id=<?php echo (int) $package['id']; ?>"
                                class="btn btn-outline-dark"
                            >
                                Choose Package
                            </a>


                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- =============================================
                     NO PACKAGES
                     ============================================= -->

                <div class="no-packages">

                    <p>
                        No service packages are currently available.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>

</section>