<?php
/*
|--------------------------------------------------------------------------
| MANAGEMENT EMPLOYEE DASHBOARD
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ROLE GUARD
|--------------------------------------------------------------------------
| dashboard.php already routes here only when role_id = 3, but this file
| can also be requested directly, so it must defend itself too.
*/

$managementId = $_SESSION['user_id'] ?? 0;

if (($_SESSION['role_id'] ?? null) !== 3) {

    header("Location: ../../login/login-form.php?error=invalid_role");
    exit();
}


require_once __DIR__ . '/../../config/database.php';


$dashboardRole = "MANAGEMENT";

$dashboardTitle = "Management Dashboard";

$dashboardDescription = "Monitor operations, services, employees and business performance.";

$dashboardButtonText = "View Reports";

$dashboardButtonLink = "?page=reports";


/*
|--------------------------------------------------------------------------
| QUICK ACTIONS
|--------------------------------------------------------------------------
*/

$quickActions = [

    [
        'icon' => '💳',
        'title' => 'Manage Payments',
        'link' => '?page=payments'
    ],

    [
        'icon' => '📋',
        'title' => 'Confirm & Assign Bookings',
        'link' => '?page=bookings'
    ],
    
    [
        'icon' => '📡',
        'title' => 'Monitor Operations',
        'link' => '?page=operations'
    ],

    [
        'icon' => '📊',
        'title' => 'View Reports',
        'link' => '?page=reports'
    ],

    [
        'icon' => '🏷️',
        'title' => 'Manage Offers',
        'link' => '?page=offers'
    ],

    [
        'icon' => '🛠️',
        'title' => 'Manage Services',
        'link' => '?page=services'
    ],

    [
        'icon' => '📦',
        'title' => 'Manage Packages',
        'link' => '?page=packages'
    ],

    [
        'icon' => '👷',
        'title' => 'Manage Assistants',
        'link' => '?page=assistants'
    ],

    [
        'icon' => '📞',
        'title' => 'Manage Contact Details',
        'link' => '?page=contact-details'
    ],

    [
        'icon' => '👤',
        'title' => 'My Profile',
        'link' => '?page=profile'
    ],

    
    [
    'icon' => '🏦',
    'title' => 'Manage Bank Details',
    'link' => '?page=bank-details'
    ],
    
    [
        'icon' => '📜',
        'title' => 'Terms & Conditions',
        'link' => '?page=terms-conditions'
    ]


];

?>

<link rel="stylesheet" href="managementEmployee/functions/functions.css">

<?php // Management dashboard page ?>

<main class="dashboard-content">

    <section class="role-dashboard">

        <!-- Dashboard Header -->
        <?php
            include __DIR__ . '/../includes/dashboard-header.php';
        ?>


        <!-- Dashboard Cards -->
        <?php
            include __DIR__ . '/functions/management-dashboard-cards.php';
        ?>



        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Functions -->
            <div class="dashboard-panel">

                <?php

                /*
                 |----------------------------------------------------------
                 | PAGE ROUTER
                 |----------------------------------------------------------
                 | Keep all management pages in one whitelist. This removes
                 | repetitive switch cases while preserving the existing URLs
                 | and folder structure.
                 */
                $page = $_GET['page'] ?? 'operations';

                $managementPages = [
                    'operations'        => 'monitor-operations.php',
                    'operation-details' => 'operations-details.php',
                    'payments'          => 'manage-payments.php',
                    'bookings'          => 'manage-bookings.php',
                    'reports'           => 'reports.php',
                    'services-report'   => 'services-report.php',
                    'revenue-report'    => 'revenue-report.php',
                    'offers'            => 'manage-offers.php',
                    'offer-form'        => 'offer-form.php',
                    'services'          => 'manage-services.php',
                    'service-form'      => 'service-form.php',
                    'packages'          => 'manage-packages.php',
                    'package-form'      => 'package-form.php',
                    'assistants'        => 'manage-service-assistants.php',
                    'contact-details'   => 'manage-contact-details.php',
                    'profile'           => 'my-profile.php',
                    'terms-conditions' => 'manage-terms-conditions.php',
                    'bank-details' => 'manage-bank-details.php'
                
                    ];


                $pageFile = $managementPages[$page]
                    ?? $managementPages['operations'];

                include __DIR__ . '/functions/' . $pageFile;

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
