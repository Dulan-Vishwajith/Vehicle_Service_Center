<div class="dashboard-cards">

    <?php foreach ($dashboardCards as $card): ?>

        <div class="dashboard-card">

            <div class="card-icon">
                <?= $card['icon'] ?>
            </div>

            <div>

                <span>
                    <?= htmlspecialchars($card['label']) ?>
                </span>

                <strong>
                    <?= htmlspecialchars($card['value']) ?>
                </strong>

            </div>

        </div>

    <?php endforeach; ?>

</div>