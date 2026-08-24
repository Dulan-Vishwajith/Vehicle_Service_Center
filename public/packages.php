<?php

/*
|--------------------------------------------------------------------------
| Vehicle Service Center
| Service Packages Page
|--------------------------------------------------------------------------
|
| This page currently uses static package information.
| You can connect it to MySQL later.
|
*/

$packages = [
    [
        "label" => "BASIC",
        "name" => "Essential Care",
        "price" => "Rs. 7,500",
        "features" => [
            "Engine inspection",
            "Oil level check",
            "Brake inspection",
            "Tyre inspection"
        ],
        "featured" => false
    ],

    [
        "label" => "POPULAR",
        "name" => "Complete Care",
        "price" => "Rs. 15,000",
        "features" => [
            "Full engine inspection",
            "Engine oil replacement",
            "Brake inspection",
            "Tyre inspection",
            "Battery check",
            "AC inspection"
        ],
        "featured" => true
    ],

    [
        "label" => "PREMIUM",
        "name" => "Premium Care",
        "price" => "Rs. 25,000",
        "features" => [
            "Complete vehicle inspection",
            "Engine service",
            "Oil and filter replacement",
            "Brake inspection",
            "Wheel alignment",
            "AC service",
            "Battery check"
        ],
        "featured" => false
    ]
];

?>

<section
    class="section packages-section"
    id="packages"
>

    <div class="container">


        <!-- ================= SECTION HEADING ================= -->

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


        <!-- ================= PACKAGE GRID ================= -->

        <div class="packages-grid">


            <?php foreach ($packages as $package): ?>

                <div
                    class="package-card
                    <?php echo $package['featured'] ? 'featured-package' : ''; ?>"
                >


                    <?php if ($package['featured']): ?>

                        <span class="popular-label">
                            MOST POPULAR
                        </span>

                    <?php endif; ?>


                    <span class="package-label">

                        <?php
                        echo htmlspecialchars($package['label']);
                        ?>

                    </span>


                    <h3>

                        <?php
                        echo htmlspecialchars($package['name']);
                        ?>

                    </h3>


                    <div class="package-price">

                        <?php
                        echo htmlspecialchars($package['price']);
                        ?>

                    </div>


                    <ul>

                        <?php foreach ($package['features'] as $feature): ?>

                            <li>
                                ✓
                                <?php
                                echo htmlspecialchars($feature);
                                ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                        <?php if ($package['label'] == 'POPULAR') : ?>

                            <a href="book-appointment.html" class="btn btn-red">
                                Choose Package
                            </a>

                        <?php else : ?>

                            <a href="book-appointment.html" class="btn btn-outline-dark">
                                Choose Package
                            </a>

                        <?php endif; ?>

                            
                   


                </div>

            <?php endforeach; ?>


        </div>

    </div>

</section>