<?php

/*
|--------------------------------------------------------------------------
| Management - Warranty Policies
|--------------------------------------------------------------------------
| The supplied database stores warranty information per replaced part
| but does not contain a separate warranty_policies table.
|
| This view therefore reports the warranty periods currently being used.
|--------------------------------------------------------------------------
*/

function renderManagementWarrantyPolicies(
    PDO $pdo,
    int $managementId
): void {
    try {
        $query = "
            SELECT
                warranty_period,
                COUNT(*) AS record_count
            FROM replaced_parts
            WHERE warranty_period IS NOT NULL
              AND warranty_period <> ''
            GROUP BY warranty_period
            ORDER BY warranty_period
        ";

        $stmt = $pdo->query($query);
        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $policies = [];
    }

    ?>

    <section class="card">

        <h2>
            Warranty Policies
        </h2>

        <p class="muted">
            Warranty periods currently recorded against replaced parts.
        </p>

        <?php if ($policies): ?>

            <div class="grid">

                <?php foreach ($policies as $policy): ?>

                    <div class="mini">

                        <small>
                            Warranty Period
                        </small>

                        <h3>
                            <?= htmlspecialchars(
                                $policy["warranty_period"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h3>

                        <p>
                            Applied to
                            <?= (int) $policy["record_count"] ?>
                            replaced-part record(s).
                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                No warranty periods have been recorded yet.
            </p>

        <?php endif; ?>

    </section>

    <?php
}

?>
