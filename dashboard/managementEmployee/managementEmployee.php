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

                $page = $_GET['page'] ?? 'operations';

                switch ($page) {

                    case 'operations':
                        include __DIR__ . '/functions/monitor-operations.php';
                        break;

                    case 'operation-details':
                        include __DIR__ . '/functions/operations-details.php';
                        break;

                    case 'reports':
                        include __DIR__ . '/functions/reports.php';
                        break;

                    case 'services-report':
                        include __DIR__ . '/functions/services-report.php';
                        break;

                    case 'revenue-report':
                        include __DIR__ . '/functions/revenue-report.php';
                        break;

                    case 'offers':
                        include __DIR__ . '/functions/manage-offers.php';
                        break;

                    case 'offer-form':
                        include __DIR__ . '/functions/offer-form.php';
                        break;

                    case 'services':
                        include __DIR__ . '/functions/manage-services.php';
                        break;

                    case 'service-form':
                        include __DIR__ . '/functions/service-form.php';
                        break;

                    case 'packages':
                        include __DIR__ . '/functions/manage-packages.php';
                        break;

                    case 'package-form':
                        include __DIR__ . '/functions/package-form.php';
                        break;

                    case 'assistants':
                        include __DIR__ . '/functions/manage-service-assistants.php';
                        break;

                    default:
                        include __DIR__ . '/functions/monitor-operations.php';
                        break;
                }

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
