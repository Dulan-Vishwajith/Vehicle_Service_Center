<?php

/*
|--------------------------------------------------------------------------
| CUSTOMER DASHBOARD CARDS
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 0;


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$upcomingBookings = 0;
$completedBookings = 0;
$totalSpent = 0;
$servicesBooked = 0;


/*
|--------------------------------------------------------------------------
| LOAD CUSTOMER STATISTICS
|--------------------------------------------------------------------------
*/

if ($userId > 0) {

    try {

        /*
        | Upcoming Bookings
        */
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE user_id = ?
            AND booking_date >= CURDATE()
            AND status IN ('pending', 'confirmed', 'in_progress', 'in progress')
        ");

        $stmt->execute([$userId]);

        $upcomingBookings = (int) $stmt->fetchColumn();


        /*
        | Completed Bookings
        */
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE user_id = ?
            AND LOWER(status) = 'completed'
        ");

        $stmt->execute([$userId]);

        $completedBookings = (int) $stmt->fetchColumn();


        /*
        | Total Amount Spent
        */
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_price), 0)
            FROM bookings
            WHERE user_id = ?
            AND LOWER(status) = 'completed'
        ");

        $stmt->execute([$userId]);

        $totalSpent = (float) $stmt->fetchColumn();


        /*
        | Total Services Booked
        */
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT bs.service_id)
            FROM booking_services bs
            INNER JOIN bookings b
                ON b.id = bs.booking_id
            WHERE b.user_id = ?
        ");

        $stmt->execute([$userId]);

        $servicesBooked = (int) $stmt->fetchColumn();


    } catch (PDOException $e) {

        /*
        | Keep dashboard working if statistics query fails
        */
        $upcomingBookings = 0;
        $completedBookings = 0;
        $totalSpent = 0;
        $servicesBooked = 0;
    }
}

?>


<div class="dashboard-cards customer-dashboard-cards">


    <!-- Upcoming Bookings -->

    <div class="dashboard-card">

        <div class="card-icon">
            📅
        </div>

        <div>

            <span>
                Upcoming Bookings
            </span>

            <strong>
                <?= $upcomingBookings ?>
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



    <!-- Total Spent -->

    <div class="dashboard-card">

        <div class="card-icon">
            💰
        </div>

        <div>

            <span>
                Total Spent
            </span>

            <strong>
                Rs. <?= number_format($totalSpent, 2) ?>
            </strong>

        </div>

    </div>



    <!-- Services Used -->

    <div class="dashboard-card">

        <div class="card-icon">
            🔧
        </div>

        <div>

            <span>
                Services Used
            </span>

            <strong>
                <?= $servicesBooked ?>
            </strong>

        </div>

    </div>


</div>