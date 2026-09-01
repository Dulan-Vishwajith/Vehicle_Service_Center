<?php
/*
|--------------------------------------------------------------------------
| SERVICE ASSISTANT DASHBOARD
|--------------------------------------------------------------------------
*/

$dashboardRole = "SERVICE ASSISTANT";

$dashboardTitle = "Service Dashboard";

$dashboardDescription = "Manage today's bookings, services and customers.";

$dashboardButtonText = "View Schedule";

$dashboardButtonLink = "#";


/*
|--------------------------------------------------------------------------
| DASHBOARD CARDS
|--------------------------------------------------------------------------
*/

$dashboardCards = [

    [
        'icon' => '📅',
        'label' => "Today's Bookings",
        'value' => '8'
    ],

    [
        'icon' => '⏳',
        'label' => 'Pending',
        'value' => '3'
    ],

    [
        'icon' => '🔧',
        'label' => 'In Progress',
        'value' => '2'
    ],

    [
        'icon' => '✓',
        'label' => 'Completed Today',
        'value' => '3'
    ]

];

/*
|--------------------------------------------------------------------------
| QUICK ACTIONS
|--------------------------------------------------------------------------
*/

$quickActions = [

    [
        'icon' => '📅',
        'title' => "Today's Schedule",
        'link' => '#'
    ],

    [
        'icon' => '📋',
        'title' => 'All Bookings',
        'link' => '#'
    ],

    [
        'icon' => '👥',
        'title' => 'Customers',
        'link' => '#'
    ],

    [
        'icon' => '💬',
        'title' => 'Customer Messages',
        'link' => '#'
    ]

];

?>


<main class="dashboard-content">

    <section class="role-dashboard">

        <!-- Dashboard Header -->
        <?php
        include __DIR__ . '/../includes/dashboard-header.php';
        ?>


        <!-- Dashboard Cards -->
        <?php
        include __DIR__ . '/../includes/dashboard-cards.php';
        ?>


        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Functions --> 
            <div class="dashboard-panel"> 
    
                <?php 
                //Include service assistant functions 
                ?> 
    
            </div>


            <!-- Quick Actions -->
            <div class="dashboard-panel">

                <?php
                include __DIR__ . '/../includes/quick-action-panel.php';
                ?>

            </div>

        </div>

    </section>

</main>


