<?php

/*
|--------------------------------------------------------------------------
| Customer - Offers
|--------------------------------------------------------------------------
*/

function renderCustomerOffers(PDO $pdo, int $userId): void
{
    try {
        $query = "
            SELECT
                title,
                description,
                type,
                discount,
                start_date,
                end_date
            FROM offers
            WHERE start_date <= CURDATE()
              AND end_date >= CURDATE()
            ORDER BY end_date
        ";

        $stmt = $pdo->query($query);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $offers = [];
    }

    ?>

    <section class="card">

        <h2>
            Current Offers
        </h2>

        <div class="grid">

            <?php foreach ($offers as $offer): ?>

                <div class="mini">

                    <small>
                        <?= htmlspecialchars(
                            $offer["type"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </small>

                    <h3>
                        <?= htmlspecialchars(
                            $offer["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $offer["description"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <b>
                        <?= htmlspecialchars(
                            $offer["discount"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>% discount
                    </b>

                    <p class="muted">
                        Valid until
                        <?= htmlspecialchars(
                            $offer["end_date"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                </div>

            <?php endforeach; ?>

            <?php if (!$offers): ?>

                <p class="empty">
                    No current offers.
                </p>

            <?php endif; ?>

        </div>

    </section>

    <?php
}

?>
