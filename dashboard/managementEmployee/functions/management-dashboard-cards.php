<?php

/*
|--------------------------------------------------------------------------
| MANAGEMENT DASHBOARD CARDS
|--------------------------------------------------------------------------
*/

$ongoingBookings = 0;
$completedBookings = 0;
$activeAssistants = 0;
$totalRevenue = 0;

try {

    /*
    | Ongoing Bookings
    | Anything not yet finished and not cancelled.
    */
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE status IN ('pending', 'booked', 'confirmed', 'service')
    ");

    $ongoingBookings = (int) $stmt->fetchColumn();


    /*
    | Completed Bookings
    */
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE status = 'completed'
    ");

    $completedBookings = (int) $stmt->fetchColumn();


    /*
    | Active Service Assistants
    | Requires the `status` column added by
    | migration_management_dashboard.sql
    */
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role_id = 2
    ");

    $activeAssistants = (int) $stmt->fetchColumn();


    /*
    | Total Revenue
    | Only counts completed bookings — pending/cancelled
    | bookings never generated real revenue.
    */
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_price), 0)
        FROM bookings
        WHERE status = 'completed'
    ");

    $totalRevenue = (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    /*
    | Keep dashboard working even if the migration
    | hasn't been applied yet or a query fails.
    */
    $ongoingBookings = 0;
    $completedBookings = 0;
    $activeAssistants = 0;
    $totalRevenue = 0;
}

?>


<div class="dashboard-cards management-dashboard-cards">


    <!-- Ongoing Bookings -->

    <div class="dashboard-card">

        <div class="card-icon">
            📅
        </div>

        <div>

            <span>
                Ongoing Bookings
            </span>

            <strong>
                <?= $ongoingBookings ?>
            </strong>

        </div>

    </div>


    <!-- Completed Bookings -->

    <div class="dashboard-card">

        <div class="card-icon">
            ✓
        </div>

        <div>

            <span>
                Completed Bookings
            </span>

            <strong>
                <?= $completedBookings ?>
            </strong>

        </div>

    </div>


    <!-- Active Assistants -->

    <div class="dashboard-card">

        <div class="card-icon">
            👷
        </div>

        <div>

            <span>
                Active Assistants
            </span>

            <strong>
                <?= $activeAssistants ?>
            </strong>

        </div>

    </div>


    <!-- Total Revenue -->

    <div class="dashboard-card">

        <div class="card-icon">
            💰
        </div>

        <div>

            <span>
                Total Revenue
            </span>

            <strong>
                Rs. <?= number_format($totalRevenue, 2) ?>
            </strong>

        </div>

    </div>


</div>
