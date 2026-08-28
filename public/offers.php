<?php
// Offers data
$offers = [
    [
        "class" => "dark-card",
        "icon" => "🔥",
        "type" => "SEASONAL OFFER",
        "title" => "Full Service Special",
        "discount" => "15% OFF",
        "description" => "Get 15% off your next full vehicle service.",
        "valid" => "Valid until 31 August 2026",
        "link" => "book-appointment.php"
    ],

    [
        "class" => "red-card",
        "icon" => "👑",
        "type" => "REGULAR CUSTOMER",
        "title" => "Loyalty Reward",
        "discount" => "10% OFF",
        "description" => "Special discount for returning customers.",
        "valid" => "Terms and conditions apply",
        "link" => "login.php"
    ],

    [
        "class" => "navy-card",
        "icon" => "🚗",
        "type" => "SERVICE DEAL",
        "title" => "AC Service Deal",
        "discount" => "Rs. 4,500",
        "description" => "Complete AC inspection and service at a special price.",
        "valid" => "Limited time offer",
        "link" => "book-appointment.php"
    ]
];
?>



<section class="section offers-section" id="offers">

    <div class="container">

        <div class="section-heading">

            <span class="section-label">
                SPECIAL OFFERS
            </span>

            <h2>
                Save More On Your Service
            </h2>

            <p>
                Take advantage of our latest vehicle service offers.
            </p>

        </div>


        <div class="offers-grid">

            <?php foreach ($offers as $offer): ?>

                <div class="offer-card <?= $offer['class']; ?>">

                    <div class="offer-icon">
                        <?= $offer['icon']; ?>
                    </div>

                    <span class="offer-type">
                        <?= $offer['type']; ?>
                    </span>

                    <h3>
                        <?= $offer['title']; ?>
                    </h3>

                    <div class="discount">
                        <?= $offer['discount']; ?>
                    </div>

                    <p>
                        <?= $offer['description']; ?>
                    </p>

                    <small>
                        <?= $offer['valid']; ?>
                    </small>

                    

                </div>

            <?php endforeach; ?>

        </div>


        

    </div>

</section>