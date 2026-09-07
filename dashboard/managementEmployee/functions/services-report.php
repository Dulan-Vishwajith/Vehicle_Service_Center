<?php

/*
|--------------------------------------------------------------------------
| SERVICE REPORTS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| DATE RANGE
|--------------------------------------------------------------------------
*/

$range = $_GET['range'] ?? 'month';

$customFrom = $_GET['from'] ?? '';
$customTo = $_GET['to'] ?? '';

switch ($range) {

    case 'today':
        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d');
        break;

    case 'week':
        $fromDate = date('Y-m-d', strtotime('monday this week'));
        $toDate = date('Y-m-d', strtotime('sunday this week'));
        break;

    case 'year':
        $fromDate = date('Y-01-01');
        $toDate = date('Y-12-31');
        break;

    case 'custom':
        $fromDate = $customFrom !== '' ? $customFrom : date('Y-m-01');
        $toDate = $customTo !== '' ? $customTo : date('Y-m-d');
        break;

    case 'month':
    default:
        $range = 'month';
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-t');
        break;
}


$serviceStats = [];
$reportError = '';

try {

    /*
    | Only completed bookings count as "valid" booking data
    | for reporting, per the database's booking lifecycle.
    */
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.service_name,
            COUNT(bs.id) AS times_booked,
            SUM(bs.service_price) AS revenue,
            AVG(bs.service_price) AS avg_price
        FROM booking_services bs
        INNER JOIN bookings b
            ON b.id = bs.booking_id
        INNER JOIN services s
            ON s.id = bs.service_id
        WHERE b.status = 'completed'
        AND b.booking_date BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY times_booked DESC
    ");

    $stmt->execute([$fromDate, $toDate]);

    $serviceStats = $stmt->fetchAll();

} catch (PDOException $e) {

    $reportError = "Unable to load the service report.";
}


$mostPopular = null;
$leastPopular = null;
$highestRevenue = null;

if (!empty($serviceStats)) {

    $mostPopular = $serviceStats[0];

    $byPopularityAsc = $serviceStats;
    usort($byPopularityAsc, function ($a, $b) {
        return $a['times_booked'] <=> $b['times_booked'];
    });
    $leastPopular = $byPopularityAsc[0];

    $byRevenueDesc = $serviceStats;
    usort($byRevenueDesc, function ($a, $b) {
        return $b['revenue'] <=> $a['revenue'];
    });
    $highestRevenue = $byRevenueDesc[0];
}

?>


<div class="panel-header">
    <h2>Service Reports</h2>
    <a href="?page=reports">&larr; Back to Reports</a>
</div>


<!-- Date Range Filters -->

<div class="report-filters">

    <a href="?page=services-report&range=today" class="report-filter <?= $range === 'today' ? 'active' : '' ?>">Today</a>
    <a href="?page=services-report&range=week" class="report-filter <?= $range === 'week' ? 'active' : '' ?>">This Week</a>
    <a href="?page=services-report&range=month" class="report-filter <?= $range === 'month' ? 'active' : '' ?>">This Month</a>
    <a href="?page=services-report&range=year" class="report-filter <?= $range === 'year' ? 'active' : '' ?>">This Year</a>

    <form method="get" class="report-custom-range">
        <input type="hidden" name="page" value="services-report">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="<?= htmlspecialchars($range === 'custom' ? $fromDate : '') ?>">
        <span>to</span>
        <input type="date" name="to" value="<?= htmlspecialchars($range === 'custom' ? $toDate : '') ?>">
        <button type="submit" class="report-filter-submit">Apply</button>
    </form>

</div>

<p class="report-range-label">
    Showing completed bookings from <?= htmlspecialchars(date('d M Y', strtotime($fromDate))) ?>
    to <?= htmlspecialchars(date('d M Y', strtotime($toDate))) ?>
</p>


<?php if ($reportError): ?>

    <div class="ops-error-message"><?= htmlspecialchars($reportError) ?></div>

<?php elseif (empty($serviceStats)): ?>

    <div class="empty-message">
        <p>No completed bookings in this date range yet.</p>
    </div>

<?php else: ?>

    <!-- Highlights -->

    <div class="report-highlights">

        <div class="report-highlight-card">
            <span>Most Popular</span>
            <strong><?= htmlspecialchars($mostPopular['service_name']) ?></strong>
            <small><?= (int) $mostPopular['times_booked'] ?> bookings</small>
        </div>

        <div class="report-highlight-card">
            <span>Least Popular</span>
            <strong><?= htmlspecialchars($leastPopular['service_name']) ?></strong>
            <small><?= (int) $leastPopular['times_booked'] ?> bookings</small>
        </div>

        <div class="report-highlight-card">
            <span>Highest Revenue</span>
            <strong><?= htmlspecialchars($highestRevenue['service_name']) ?></strong>
            <small>Rs. <?= number_format((float) $highestRevenue['revenue'], 2) ?></small>
        </div>

    </div>


    <!-- Table -->

    <div class="ops-table">

        <div class="ops-table-heading">
            <span class="ops-col-service">Service Name</span>
            <span class="ops-col-status">Bookings</span>
            <span class="ops-col-total">Revenue</span>
            <span class="ops-col-total">Avg. Price</span>
        </div>

        <?php foreach ($serviceStats as $row): ?>

            <div class="ops-table-row">

                <span class="ops-col-service"><?= htmlspecialchars($row['service_name']) ?></span>

                <span class="ops-col-status"><?= (int) $row['times_booked'] ?></span>

                <span class="ops-col-total">Rs. <?= number_format((float) $row['revenue'], 2) ?></span>

                <span class="ops-col-total">Rs. <?= number_format((float) $row['avg_price'], 2) ?></span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>
