<?php

require_once "config/database.php";

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
    WHERE status = 1
    ORDER BY id DESC
    LIMIT 3
";

$serviceResult = $conn->query($serviceQuery);

?>


<!-- ================= SERVICES ================= -->

<section class="section" id="services">

    <div class="container">

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


        <!-- ================= SEARCH ================= -->

        <div class="service-tools">

            <input
                type="text"
                id="serviceSearch"
                placeholder="Search services..."
            >

            <select id="serviceCategory">

                <option value="all">
                    All Categories
                </option>

                <option value="maintenance">
                    Maintenance
                </option>

                <option value="repair">
                    Repair
                </option>

                <option value="inspection">
                    Inspection & Diagnostics
                </option>

                <option value="other">
                    Other Services
                </option>

            </select>

        </div>


        <!-- ================= SERVICES GRID ================= -->

        <div
            class="services-grid"
            id="servicesGrid"
        >

            <?php if ($serviceResult && $serviceResult->num_rows > 0): ?>

                <?php while ($service = $serviceResult->fetch_assoc()): ?>

                    <article
                        class="service-card"
                        data-category="<?php echo htmlspecialchars($service['category']); ?>"
                        data-name="<?php echo htmlspecialchars(strtolower($service['service_name'])); ?>"
                    >

                        <!-- Service Icon -->

                        <div class="service-card-top">

                            <?php
                            echo htmlspecialchars($service['icon']);
                            ?>

                        </div>


                        <!-- Service Content -->

                        <div class="service-card-content">

                            <span class="service-tag">

                                <?php
                                echo htmlspecialchars(
                                    ucfirst($service['category'])
                                );
                                ?>

                            </span>


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $service['service_name']
                                );
                                ?>

                            </h3>


                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $service['description']
                                );
                                ?>

                            </p>


                            <div class="service-bottom">

                                <strong>

                                    Rs.
                                    <?php
                                    echo number_format(
                                        $service['price'],
                                        2
                                    );
                                    ?>

                                </strong>


                                <span>

                                    ⏱
                                    <?php
                                    echo htmlspecialchars(
                                        $service['duration']
                                    );
                                    ?>

                                </span>

                            </div>


                            <a
                                href="book-appointment.php?service_id=<?php echo $service['id']; ?>"
                                class="service-link"
                            >

                                Book Service →

                            </a>

                        </div>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="database-no-services">
                    No services are currently available.
                </p>

            <?php endif; ?>

        </div>


        <!-- ================= NO SEARCH RESULTS ================= -->

        <p
            class="no-results"
            id="noResults"
        >
            No services found.
        </p>


        <!-- ================= VIEW ALL ================= -->

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

