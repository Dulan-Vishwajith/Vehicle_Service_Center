<?php

/*
|--------------------------------------------------------------------------
| Management - Payment Options
|--------------------------------------------------------------------------
| The supplied database contains a payments table with payment_method,
| but it does not contain a separate payment_options configuration table.
|
| Therefore this feature displays the payment methods currently recorded
| in the payments table instead of pretending that a persistent payment
| option configuration table exists.
|--------------------------------------------------------------------------
*/

function renderManagementPaymentOptions(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                payment_method,
                COUNT(*) AS usage_count
            FROM payments
            WHERE payment_method IS NOT NULL
              AND payment_method <> ''
            GROUP BY payment_method
            ORDER BY payment_method
        ";

        $stmt = $pdo->query($query);
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $methods = [];
    }

    ?>

    <section class="card">

        <h2>
            Payment Options
        </h2>

        <p class="muted">
            Payment methods currently recorded in the system.
        </p>

        <?php if ($methods): ?>

            <div class="grid">

                <?php foreach ($methods as $method): ?>

                    <div class="mini">

                        <small>
                            Payment Method
                        </small>

                        <h3>
                            <?= htmlspecialchars(
                                $method["payment_method"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h3>

                        <p>
                            Recorded payments:
                            <?= (int) $method["usage_count"] ?>
                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                No payment methods have been recorded yet.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
