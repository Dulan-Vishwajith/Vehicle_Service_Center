<?php

require_once "config/database.php";

/* =========================================================
   GET ACTIVE OFFERS
   ========================================================= */

$offerStatus = 1;

$offerSQL = "
    SELECT
        id,
        card_class,
        icon,
        offer_type,
        title,
        discount,
        description,
        valid_text,
        link
    FROM offers
    WHERE status = ?
    ORDER BY id ASC
";

$offerStmt = $pdo->prepare($offerSQL);

$offerStmt->execute([$offerStatus]);

$offers = $offerStmt->fetchAll();

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

            <?php if (!empty($offers)): ?>

                <?php foreach ($offers as $offer): ?>

                    <div class="offer-card <?= htmlspecialchars($offer['card_class'], ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="offer-icon">
                            <?= htmlspecialchars($offer['icon'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <span class="offer-type">
                            <?= htmlspecialchars($offer['offer_type'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($offer['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </h3>

                        <div class="discount">
                            <?= htmlspecialchars($offer['discount'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <p>
                            <?= htmlspecialchars($offer['description'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>

                        <small>
                            <?= htmlspecialchars($offer['valid_text'], ENT_QUOTES, 'UTF-8'); ?>
                        </small>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-offers">

                    <p>
                        No special offers are currently available.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>