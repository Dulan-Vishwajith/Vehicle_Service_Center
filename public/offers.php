<?php

require_once "config/database.php";


/* =========================================================
   SESSION / MANAGEMENT EMPLOYEE CHECK
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Management Employee = role_id 3
|--------------------------------------------------------------------------
*/

$isManagementEmployee =
    isset($_SESSION['user_id']) &&
    isset($_SESSION['role_id']) &&
    (int) $_SESSION['role_id'] === 3;


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

        <!-- =================================================
             SECTION HEADING
             ================================================= -->

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


        <!-- =================================================
             OFFERS GRID
             ================================================= -->

        <div class="offers-grid">

            <?php if (!empty($offers)): ?>

                <?php foreach ($offers as $offer): ?>

                    <!-- =================================================
                         OFFER CARD
                         ================================================= -->

                    <div
                        class="offer-card <?= htmlspecialchars(
                            $offer['card_class'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >


                        <!-- =============================================
                             MANAGEMENT EMPLOYEE - EDIT
                             ============================================= -->

                        <?php if ($isManagementEmployee): ?>

                            <a
                                href="dashboard/dashboard.php?page=offer-form&id=<?= (int) $offer['id'] ?>"
                                class="offer-edit-link"
                            >
                                Edit
                            </a>

                        <?php endif; ?>


                        <!-- =============================================
                             OFFER ICON
                             ============================================= -->

                        <div class="offer-icon">

                            <?= htmlspecialchars(
                                $offer['icon'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>


                        <!-- =============================================
                             OFFER TYPE
                             ============================================= -->

                        <span class="offer-type">

                            <?= htmlspecialchars(
                                $offer['offer_type'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </span>


                        <!-- =============================================
                             OFFER TITLE
                             ============================================= -->

                        <h3>

                            <?= htmlspecialchars(
                                $offer['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </h3>


                        <!-- =============================================
                             DISCOUNT
                             ============================================= -->

                        <div class="discount">

                            <?= htmlspecialchars(
                                $offer['discount'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>


                        <!-- =============================================
                             DESCRIPTION
                             ============================================= -->

                        <p>

                            <?= htmlspecialchars(
                                $offer['description'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </p>


                        <!-- =============================================
                             VALID / TERMS
                             ============================================= -->

                        <small>

                            <?= htmlspecialchars(
                                $offer['valid_text'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </small>


                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <!-- =============================================
                     NO OFFERS
                     ============================================= -->

                <div class="no-offers">

                    <p>
                        No special offers are currently available.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>