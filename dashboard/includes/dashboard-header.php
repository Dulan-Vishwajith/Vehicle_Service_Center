<div class="dashboard-header">

    <div>

        <span class="section-label">
            <?= htmlspecialchars($dashboardRole ?? 'DASHBOARD') ?>
        </span>

        <h1>
            <?= htmlspecialchars($dashboardTitle ?? 'Dashboard') ?>
        </h1>

        <p>
            <?= htmlspecialchars($dashboardDescription ?? '') ?>
        </p>

    </div>

    <?php if (!empty($dashboardButtonText)): ?>

        <a
            href="<?= htmlspecialchars($dashboardButtonLink ?? '#') ?>"
            class="btn btn-primary"
        >
            <?= htmlspecialchars($dashboardButtonText) ?>
        </a>

    <?php endif; ?>

</div>