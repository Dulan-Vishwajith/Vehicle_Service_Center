<?php

/*
|--------------------------------------------------------------------------
| REPORTS DASHBOARD
|--------------------------------------------------------------------------
| Hub page — links out to the individual report types.
| Each report page owns its own date-range filtering.
*/

$reportCategories = [

    [
        'icon' => '🛠️',
        'title' => 'Service Reports',
        'description' => 'Bookings, revenue and averages broken down by service.',
        'link' => '?page=services-report'
    ],

    [
        'icon' => '💰',
        'title' => 'Revenue Reports',
        'description' => 'Total revenue, completed bookings and average booking value.',
        'link' => '?page=revenue-report'
    ],

    [
        'icon' => '📅',
        'title' => 'Booking Reports',
        'description' => 'Ongoing and completed bookings — see Monitor Operations.',
        'link' => '?page=operations'
    ]

];

?>


<div class="panel-header">
    <h2>Reports</h2>
</div>


<div class="report-category-grid">

    <?php foreach ($reportCategories as $category): ?>

        <a href="<?= htmlspecialchars($category['link']) ?>" class="report-category-card">

            <div class="report-category-icon">
                <?= $category['icon'] ?>
            </div>

            <div>
                <h3><?= htmlspecialchars($category['title']) ?></h3>
                <p><?= htmlspecialchars($category['description']) ?></p>
            </div>

        </a>

    <?php endforeach; ?>

</div>
