<?php
/*
|--------------------------------------------------------------------------
| HERO SECTION
|--------------------------------------------------------------------------
*/
?>

<main>

    <section id="home" class="hero">

        <div class="container hero-container">

            <div class="hero-content">


                <!-- =====================================================
                     HERO BADGE
                     ===================================================== -->

                <span class="hero-badge">
                    🚗 SMART VEHICLE CARE
                </span>


                <!-- =====================================================
                     HERO TITLE
                     ===================================================== -->

                <h1>
                    Expert Care
                    <br>
                    For Your Vehicle
                </h1>


                <!-- =====================================================
                     HERO DESCRIPTION
                     ===================================================== -->

                <p>
                    Book your vehicle service online, track your
                    vehicle progress and enjoy professional
                    automotive care with VEYRO.
                </p>


                <!-- =====================================================
                     HERO BUTTONS
                     ===================================================== -->

                <div class="hero-buttons">

                    <a
                        href="booking/booking.php"
                        class="btn btn-primary btn-large"
                    >
                        Book Appointment
                    </a>

                    <a
                        href="#services"
                        class="btn btn-light btn-large"
                    >
                        Explore Services
                    </a>

                </div>


                <!-- =====================================================
                     CUSTOMER SERVICE RATINGS
                     ===================================================== -->

                <?php

                /*
                |--------------------------------------------------------------------------
                | GET CUSTOMER SERVICE RATINGS
                |--------------------------------------------------------------------------
                |
                | reviews
                |     ↓
                | user_id
                |     ↓
                | users
                |
                | We only need:
                |
                | service_rating
                | customer name
                |
                */

                $serviceRatings = [];


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE CONNECTION
                    |--------------------------------------------------------------------------
                    |
                    | hero.php:
                    | public/hero.php
                    |
                    | database.php:
                    | config/database.php
                    |
                    */

                    require_once __DIR__ . '/../config/database.php';


                    /*
                    |--------------------------------------------------------------------------
                    | GET REVIEWS
                    |--------------------------------------------------------------------------
                    */

                    $ratingQuery = "
                        SELECT
                            r.service_rating,
                            u.name AS customer_name

                        FROM reviews r

                        INNER JOIN users u
                            ON u.user_id = r.user_id

                        WHERE
                            r.service_rating IS NOT NULL
                            AND r.service_rating BETWEEN 1 AND 5

                        ORDER BY
                            r.created_at DESC

                        LIMIT 10
                    ";


                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE QUERY
                    |--------------------------------------------------------------------------
                    */

                    $ratingStmt =
                        $pdo->prepare(
                            $ratingQuery
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | EXECUTE
                    |--------------------------------------------------------------------------
                    */

                    $ratingStmt->execute();


                    /*
                    |--------------------------------------------------------------------------
                    | FETCH REVIEWS
                    |--------------------------------------------------------------------------
                    */

                    $serviceRatings =
                        $ratingStmt->fetchAll(
                            PDO::FETCH_ASSOC
                        );


                } catch (Throwable $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE ERROR
                    |--------------------------------------------------------------------------
                    |
                    | Don't break the homepage if the reviews cannot be loaded.
                    |
                    */

                    error_log(
                        'VEYRO hero ratings error: ' .
                        $e->getMessage()
                    );


                    $serviceRatings = [];

                }

                ?>


                <!-- =====================================================
                     RATING CONTAINER
                     ===================================================== -->

                <div class="hero-ratings">

                    <div class="hero-ratings-track">


                        <?php if (!empty($serviceRatings)): ?>


                            <?php

                            /*
                            |--------------------------------------------------------------------------
                            | DUPLICATE REVIEWS
                            |--------------------------------------------------------------------------
                            |
                            | Duplicate the records so the animation can
                            | continuously scroll.
                            |
                            */

                            $ratingsForScroll =
                                array_merge(
                                    $serviceRatings,
                                    $serviceRatings
                                );

                            ?>


                            <?php foreach (
                                $ratingsForScroll
                                as $rating
                            ): ?>


                                <?php

                                /*
                                |--------------------------------------------------------------------------
                                | SERVICE RATING
                                |--------------------------------------------------------------------------
                                */

                                $ratingValue =
                                    (float) (
                                        $rating[
                                            'service_rating'
                                        ] ?? 0
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | CUSTOMER NAME
                                |--------------------------------------------------------------------------
                                */

                                $customerName =
                                    htmlspecialchars(
                                        $rating[
                                            'customer_name'
                                        ] ?? 'Customer',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                ?>


                                <!-- =========================================
                                     RATING CARD
                                     ========================================= -->

                                <div class="hero-rating-card">


                                    <!-- =====================================
                                         STARS
                                         ===================================== -->

                                    <div class="hero-rating-stars">

                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>

                                            <span
                                                class="<?= $i <= round($ratingValue)
                                                    ? 'active'
                                                    : '' ?>"
                                            >
                                                ★
                                            </span>

                                        <?php endfor; ?>


                                    </div>


                                    <!-- =====================================
                                         RATING NUMBER
                                         ===================================== -->

                                    <strong
                                        class="hero-rating-score"
                                    >

                                        <?= number_format(
                                            $ratingValue,
                                            1
                                        ) ?>

                                    </strong>


                                    <!-- =====================================
                                         CUSTOMER NAME
                                         ===================================== -->

                                    <div
                                        class="hero-rating-customer"
                                    >

                                        <?= $customerName ?>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <!-- =================================================
                                 FALLBACK
                                 ================================================= -->

                            <div class="hero-rating-card">


                                <!-- STARS -->

                                <div class="hero-rating-stars">

                                    <span class="active">
                                        ★
                                    </span>

                                    <span class="active">
                                        ★
                                    </span>

                                    <span class="active">
                                        ★
                                    </span>

                                    <span class="active">
                                        ★
                                    </span>

                                    <span class="active">
                                        ★
                                    </span>

                                </div>


                                <!-- RATING -->

                                <strong
                                    class="hero-rating-score"
                                >
                                    5.0
                                </strong>


                                <!-- CUSTOMER -->

                                <div
                                    class="hero-rating-customer"
                                >
                                    Customer
                                </div>


                            </div>


                        <?php endif; ?>


                    </div>

                </div>


            </div>

        </div>

    </section>

</main>