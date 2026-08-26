<?php

require_once "config/database.php";


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
        icon

    FROM services

    WHERE status = :status

    ORDER BY id DESC

    LIMIT :limit
";


$serviceStmt = $pdo->prepare($serviceQuery);


$status = 1;
$limit = 4;


$serviceStmt->bindValue(
    ':status',
    $status,
    PDO::PARAM_INT
);


$serviceStmt->bindValue(
    ':limit',
    $limit,
    PDO::PARAM_INT
);


$serviceStmt->execute();


$serviceResult = $serviceStmt->fetchAll();

?>


<!-- =========================================================
     SERVICES SECTION
     ========================================================= -->

<section class="section" id="services">

    <div class="container">


        <!-- =================================================
             SECTION HEADING
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


                    <!-- =================================================
                         SERVICE CARD
                         ================================================= -->

                    <article
                        class="service-card"

                        data-category="<?php

                            echo htmlspecialchars(
                                $service['category'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"

                        data-name="<?php

                            echo htmlspecialchars(
                                strtolower(
                                    $service['service_name']
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                    >


                        <!-- =============================================
                             SERVICE ICON
                             ============================================= -->

                        <div class="service-card-top">

                            <?php

                            echo htmlspecialchars(
                                $service['icon'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </div>


                        <!-- =============================================
                             SERVICE CONTENT
                             ============================================= -->

                        <div class="service-card-content">


                            <!-- SERVICE CATEGORY -->

                            <span class="service-tag">

                                <?php

                                echo htmlspecialchars(
                                    ucfirst(
                                        $service['category']
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </span>


                            <!-- SERVICE NAME -->

                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $service['service_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </h3>


                            <!-- SERVICE DESCRIPTION -->

                            <p>

                                <?php

                                echo htmlspecialchars(
                                    $service['description'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </p>


                            <!-- =========================================
                                 PRICE AND DURATION
                                 ========================================= -->

                            <div class="service-bottom">


                                <!-- PRICE -->

                                <strong>

                                    Rs.

                                    <?php

                                    echo number_format(
                                        (float) $service['price'],
                                        2
                                    );

                                    ?>

                                </strong>


                                <!-- DURATION -->

                                <span>

                                    ⏱

                                    <?php

                                    echo htmlspecialchars(
                                        $service['duration'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </span>


                            </div>


                            <!-- =========================================
                                 BOOK SERVICE
                                 ========================================= -->

                            <a
                                href="book-appointment.php?service_id=<?php echo (int) $service['id']; ?>"
                                class="service-link"
                            >

                                Book Service →

                            </a>


                        </div>

                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- =============================================
                     NO SERVICES
                     ============================================= -->

                <p class="database-no-services">

                    No services are currently available.

                </p>


            <?php endif; ?>


        </div>


        <!-- =================================================
             NO SEARCH RESULTS
             ================================================= -->

        <p
            class="no-results"
            id="noResults"
            style="display: none;"
        >
            No services found.
        </p>


        <!-- =================================================
             VIEW ALL SERVICES
             ================================================= -->

        <div class="center-button">

            <a
                href="services.php"
                class="btn btn-dark"
            >

                View All Services

            </a>

        </div>


    </div>

</section>