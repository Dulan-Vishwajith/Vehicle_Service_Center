<div class="panel-header">

    <h2>
        Quick Actions
    </h2>

</div>

<?php foreach ($quickActions as $action): ?>

    <a
        href="<?= htmlspecialchars($action['link']) ?>"
        class="quick-action"
    >

        <span>
            <?= $action['icon'] ?>
        </span>

        <?= htmlspecialchars($action['title']) ?>

    </a>

<?php endforeach; ?>