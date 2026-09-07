<?php

/*
|--------------------------------------------------------------------------
| REVENUE REPORTS
|--------------------------------------------------------------------------
*/

$range = $_GET['range'] ?? 'month';

$customFrom = $_GET['from'] ?? '';
$customTo = $_GET['to'] ?? '';

switch ($range) {

    case 'daily':
        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d');
        break;

    case 'weekly':
        $fromDate = date('Y-m-d', strtotime('monday this week'));
        $toDate = date('Y-m-d', strtotime('sunday this week'));
        break;

    case 'yearly':
        $fromDate = date('Y-01-01');
        $toDate = date('Y-12-31');
        break;

    case 'custom':
        $fromDate = $customFrom !== '' ? $customFrom : date('Y-m-01');
        $toDate = $customTo !== '' ? $customTo : date('Y-m-d');
        break;

    case 'monthly':
    default:
        $range = 'monthly';
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-t');
        break;
}


$totalRevenue = 0;
$completedCount = 0;
$avgBookingValue = 0;
$revenueRows = [];
$reportError = '';

try {

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(total_price), 0) AS total_revenue,
            COUNT(*) AS completed_count,
            COALESCE(AVG(total_price), 0) AS avg_value
        FROM bookings
        WHERE status = 'completed'
        AND booking_date BETWEEN ? AND ?
    ");

    $stmt->execute([$fromDate, $toDate]);

    $summary = $stmt->fetch();

    $totalRevenue = (float) $summary['total_revenue'];
    $completedCount = (int) $summary['completed_count'];
    $avgBookingValue = (float) $summary['avg_value'];


    $stmt = $pdo->prepare("
        SELECT
            b.id,
            u.name AS customer_name,
            b.booking_date,
            b.total_price
        FROM bookings b
        INNER JOIN users u
            ON u.user_id = b.user_id
        WHERE b.status = 'completed'
        AND b.booking_date BETWEEN ? AND ?
        ORDER BY b.booking_date DESC
    ");

    $stmt->execute([$fromDate, $toDate]);

    $revenueRows = $stmt->fetchAll();

} catch (PDOException $e) {

    $reportError = "Unable to load the revenue report.";
}

?>


<div class="panel-header">
    <h2>Revenue Reports</h2>
    <a href="?page=reports">&larr; Back to Reports</a>
</div>


<!-- Date Range Filters -->

<div class="report-filters">

    <a href="?page=revenue-report&range=daily" class="report-filter <?= $range === 'daily' ? 'active' : '' ?>">Daily</a>
    <a href="?page=revenue-report&range=weekly" class="report-filter <?= $range === 'weekly' ? 'active' : '' ?>">Weekly</a>
    <a href="?page=revenue-report&range=monthly" class="report-filter <?= $range === 'monthly' ? 'active' : '' ?>">Monthly</a>
    <a href="?page=revenue-report&range=yearly" class="report-filter <?= $range === 'yearly' ? 'active' : '' ?>">Yearly</a>

    <form method="get" class="report-custom-range">
        <input type="hidden" name="page" value="revenue-report">
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

<?php else: ?>

    <!-- Summary -->

    <div class="report-highlights">

        <div class="report-highlight-card">
            <span>Total Revenue</span>
            <strong>Rs. <?= number_format($totalRevenue, 2) ?></strong>
        </div>

        <div class="report-highlight-card">
            <span>Completed Bookings</span>
            <strong><?= $completedCount ?></strong>
        </div>

        <div class="report-highlight-card">
            <span>Average Booking Value</span>
            <strong>Rs. <?= number_format($avgBookingValue, 2) ?></strong>
        </div>

    </div>


    <?php if (empty($revenueRows)): ?>

        <div class="empty-message">
            <p>No completed bookings in this date range yet.</p>
        </div>

    <?php else: ?>

        <div class="ops-table">

            <div class="ops-table-heading">
                <span class="ops-col-id">Booking</span>
                <span class="ops-col-customer">Customer</span>
                <span class="ops-col-date">Date</span>
                <span class="ops-col-total">Total</span>
            </div>

            <?php foreach ($revenueRows as $row): ?>

                <div class="ops-table-row">

                    <span class="ops-col-id">#<?= (int) $row['id'] ?></span>

                    <span class="ops-col-customer"><?= htmlspecialchars($row['customer_name']) ?></span>

                    <span class="ops-col-date"><?= htmlspecialchars(date('d M Y', strtotime($row['booking_date']))) ?></span>

                    <span class="ops-col-total">Rs. <?= number_format((float) $row['total_price'], 2) ?></span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

<?php endif; ?>
