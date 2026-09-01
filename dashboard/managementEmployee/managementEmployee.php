<?php
/*
|--------------------------------------------------------------------------
| MANAGEMENT EMPLOYEE DASHBOARD
|--------------------------------------------------------------------------
*/

$dashboardRole = "MANAGEMENT";

$dashboardTitle = "Management Dashboard";

$dashboardDescription = "Monitor business performance and manage operations.";

$dashboardButtonText = "Generate Report";

$dashboardButtonLink = "#";


/*
|--------------------------------------------------------------------------
| DASHBOARD CARDS
|--------------------------------------------------------------------------
*/

$dashboardCards = [

    [
        'icon' => '👥',
        'label' => 'Total Customers',
        'value' => '1,245'
    ],

    [
        'icon' => '📅',
        'label' => 'Total Bookings',
        'value' => '328'
    ],

    [
        'icon' => '💰',
        'label' => 'Monthly Revenue',
        'value' => 'Rs. 850K'
    ],

    [
        'icon' => '🔧',
        'label' => 'Active Services',
        'value' => '14'
    ]

];



/*
|--------------------------------------------------------------------------
| QUICK ACTIONS
|--------------------------------------------------------------------------
*/

$quickActions = [

    [
        'icon' => '👥',
        'title' => 'Manage Employees',
        'link' => '#'
    ],

    [
        'icon' => '📅',
        'title' => 'Manage Bookings',
        'link' => '#'
    ],

    [
        'icon' => '🛠️',
        'title' => 'Manage Services',
        'link' => '#'
    ],

    [
        'icon' => '📊',
        'title' => 'View Reports',
        'link' => '#'
    ],

    [
        'icon' => '⚙️',
        'title' => 'System Settings',
        'link' => '#'
    ]

];

?>



<main class="dashboard-content">

    <section class="role-dashboard">

        <!-- Dashboard Header -->
        <?php
        include './includes/dashboard-header.php';
        ?>


        <!-- Dashboard Cards -->
        <?php
        include './includes/dashboard-cards.php';
        ?>


        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Functions --> 
            <div class="dashboard-panel"> 
    
                <?php 
                //Include Management Employee functions 
                ?> 
        
            </div>


            <!-- Quick Actions -->
            <div class="dashboard-panel">

                <?php
                include './includes/quick-action-panel.php';
                ?>

            </div>

        </div>

    </section>

</main>

