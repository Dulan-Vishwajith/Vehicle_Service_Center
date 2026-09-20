<?php

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| MANAGEMENT EMPLOYEE CHECK
|--------------------------------------------------------------------------
| Management Employee = role_id 3
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isManagementEmployee =
    isset($_SESSION['user_id']) &&
    isset($_SESSION['role_id']) &&
    (int) $_SESSION['role_id'] === 3;

/* =========================================================
   SERVICES
   ========================================================= */

$showAll = isset($_GET['all']) && $_GET['all'] == '1';


/* =========================================================
   GET ACTIVE SERVICES
   ========================================================= */

$serviceQuery = "
    SELECT
        id,
        service_name,
        category,
        description,
        price,
        duration,
        icon,
        image
    FROM services
    WHERE status = :status
    ORDER BY id DESC
";


if (!$showAll) {
    $serviceQuery .= " LIMIT :limit";
}


$serviceStmt = $pdo->prepare($serviceQuery);

$serviceStmt->bindValue(
    ':status',
    1,
    PDO::PARAM_INT
);


if (!$showAll) {

    $serviceStmt->bindValue(
        ':limit',
        4,
        PDO::PARAM_INT
    );

}


$serviceStmt->execute();

$serviceResult = $serviceStmt->fetchAll();


/* =========================================================
   SERVICE IMAGE FUNCTION
   ========================================================= */

/*
 * Database value:
 *
 * images/services/example.jpg
 *
 * Physical location:
 *
 * public/images/services/example.jpg
 *
 * Browser URL:
 *
 * public/images/services/example.jpg
 */

function getServiceImage($image)
{
    if (
        empty($image) ||
        !is_string($image)
    ) {
        return null;
    }


    $image = trim($image);


    /*
     * Only allow service image paths.
     */

    if (
        strpos($image, 'images/services/') !== 0
    ) {
        return null;
    }


    /*
     * Prevent ../ paths.
     */

    if (
        strpos($image, '..') !== false
    ) {
        return null;
    }


    /*
     * Because this file is:
     *
     * public/services.php
     *
     * __DIR__ is:
     *
     * Vehicle_Service_Center/public
     *
     * Therefore:
     *
     * __DIR__ . '/' . $image
     *
     * becomes:
     *
     * Vehicle_Service_Center/public/images/services/file.jpg
     */

    $physicalPath =
        __DIR__ . '/' . $image;


    /*
     * Make sure the actual image exists.
     */

    if (!is_file($physicalPath)) {
        return null;
    }


    /*
     * Make sure it is an allowed image.
     */

    $extension = strtolower(
        pathinfo(
            $physicalPath,
            PATHINFO_EXTENSION
        )
    );


    $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {
        return null;
    }


    return $image;
}

?>


<!-- =========================================================
     SERVICES SECTION
     ========================================================= -->

<section
    class="section"
    id="services"
>

    <div class="container">


        <!-- =================================================
             HEADING
             ================================================= -->

        <div class="section-heading">

            <span class="section-label">
                OUR SERVICES
            </span>

            <h2>
                Vehicle Services You Can Trust
            </h2>

            <p>
                Professional services designed to keep
                your vehicle safe and reliable.
            </p>

        </div>


        <!-- =================================================
             SERVICES GRID
             ================================================= -->

        <div
            class="services-grid"
            id="servicesGrid"
        >


            <?php if (!empty($serviceResult)): ?>


                <?php foreach ($serviceResult as $service): ?>


                    <?php

                    /*
                     * Check whether an actual image exists.
                     */

                    $serviceImage = getServiceImage(
                        $service['image'] ?? null
                    );


                    /*
                     * Emoji fallback.
                     */

                    $serviceIcon =
                        !empty($service['icon'])
                            ? $service['icon']
                            : '🔧';

                    ?>


                    <!-- =================================================
                         SERVICE CARD
                         ================================================= -->

                    <article
                        class="service-card"

                        data-category="<?= htmlspecialchars(
                            $service['category'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"

                        data-name="<?= htmlspecialchars(
                            strtolower(
                                $service['service_name']
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                        <!-- =============================================
                             PHOTO / EMOJI
                             ============================================= -->

                        <div class="service-card-top">


                            <?php if ($serviceImage): ?>


                                <!-- =====================================
                                     PHOTO AVAILABLE
                                     ===================================== -->

                                <img
                                    src="public/<?= htmlspecialchars(
                                        $serviceImage,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $service['service_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    class="service-card-image"
                                    width="1200"
                                    height="675"
                                    loading="lazy"
                                >


                            <?php else: ?>


                                <!-- =====================================
                                     NO PHOTO → EMOJI
                                     ===================================== -->

                                <span
                                    class="service-card-icon"
                                    aria-label="<?= htmlspecialchars(
                                        $service['service_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $serviceIcon,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>


                            <?php endif; ?>


                        </div>


                        <!-- =============================================
                             SERVICE CONTENT
                             ============================================= -->

                        <div class="service-card-content">


                            <!-- CATEGORY -->

                            <span class="service-tag">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $service['category']
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>


                            <!-- SERVICE NAME -->

                            <h3>

                                <?= htmlspecialchars(
                                    $service['service_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>


                            <!-- DESCRIPTION -->

                            <p>

                                <?= htmlspecialchars(
                                    $service['description'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>


                            <!-- PRICE + DURATION -->

                            <div class="service-bottom">

                                <strong>

                                    Rs.

                                    <?= number_format(
                                        (float) $service['price'],
                                        2
                                    ) ?>

                                </strong>


                                <span>

                                    ⏱

                                    <?= htmlspecialchars(
                                        $service['duration'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </div>


                            <!-- BOOK SERVICE -->

                           <!-- =================================================
                                SERVICE ACTIONS
                                ================================================= -->

                            <div class="service-actions">

                                <!-- BOOK SERVICE -->
                                <a
                                    href="booking/booking.php?services[]=<?= (int) $service['id'] ?>"
                                    class="service-link"
                                >
                                    Book Service →
                                </a>


                                <?php if ($isManagementEmployee): ?>

                                    <!-- EDIT SERVICE - MANAGEMENT EMPLOYEE ONLY -->
                                    <a
                                        href="dashboard/dashboard.php?page=service-form&id=<?= (int) $service['id'] ?>"
                                        class="service-edit-link"
                                    >
                                        Edit
                                    </a>

                                <?php endif; ?>

                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <p class="database-no-services">
                    No services are currently available.
                </p>


            <?php endif; ?>


        </div>


        <!-- =================================================
             VIEW ALL
             ================================================= -->

        <div class="center-button">

            <?php if (!$showAll): ?>

                <a
                    href="index.php?all=1#services"
                    class="btn btn-dark"
                >
                    View All Services
                </a>

            <?php else: ?>

                <a
                    href="index.php#services"
                    class="btn btn-dark"
                >
                    Hide Services
                </a>

            <?php endif; ?>

        </div>


    </div>

</section>