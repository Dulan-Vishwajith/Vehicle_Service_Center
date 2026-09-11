<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login-form.php");
    exit;
}

$assistantId = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../../config/database.php';


$dashboardRole = 'SERVICE ASSISTANT';

$dashboardTitle = 'Service Assistant Dashboard';

$dashboardDescription =
    'Manage your appointments and customer services.';

$dashboardButtonText =
    "View Today's Schedule";

$dashboardButtonLink =
    '?page=today';


$quickActions = [

    [
        'icon' => '📅',
        'title' => "Today's Schedule",
        'link' => '?page=today'
    ],

    [
        'icon' => '📋',
        'title' => 'My Appointments',
        'link' => '?page=appointments'
    ],

    [
        'icon' => '🚗',
        'title' => 'Available Bookings',
        'link' => '?page=available'
    ],

    [
        'icon' => '👥',
        'title' => 'View Customers',
        'link' => '?page=customers'
    ],

    [
        'icon' => '👤',
        'title' => 'My Profile',
        'link' => '?page=profile'
    ]

];


$page = $_GET['page'] ?? 'today';


$allowedPages = [

    'today',
    'appointments',
    'available',
    'details',
    'profile',
    'customers',
    'replaced-parts'

];


if (!in_array($page, $allowedPages, true)) {

    $page = 'today';

}

?>


<link rel="stylesheet" href="./serviceAssistant/functions/functions.css">


<main class="dashboard-content">

    <section class="role-dashboard">


        <!-- DASHBOARD HEADER -->

        <?php

        include __DIR__
            . '/../includes/dashboard-header.php';

        ?>


        <!-- DASHBOARD CARDS -->

        <?php

        include __DIR__
            . '/functions/service-assistant-dashboard-cards.php';

        ?>


        <!-- SUCCESS MESSAGE -->

        <?php if (
            isset($_GET['success'])
            && $_GET['success'] === 'booking_confirmed'
        ): ?>

            <div class="booking-success-message">

                Booking confirmed and assigned to you successfully.

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if (isset($_GET['error'])): ?>

            <div class="booking-error-message">

                <?php

                $error = $_GET['error'];


                if ($error === 'booking_unavailable') {

                    echo 'This booking is no longer available.';

                } elseif ($error === 'invalid_booking') {

                    echo 'Invalid booking.';

                } elseif ($error === 'unauthorized') {

                    echo 'You are not authorized to perform this action.';

                } elseif ($error === 'update') {

                    echo 'Unable to update the booking.';

                } else {

                    echo 'An error occurred. Please try again.';

                }

                ?>

            </div>

        <?php endif; ?>


        <div class="dashboard-grid">


            <!-- MAIN CONTENT -->

            <div class="dashboard-panel">


                <?php


                switch ($page) {


                    case 'appointments':

                        include __DIR__
                            . '/functions/manage-appointments.php';

                        break;


                    case 'available':

                        include __DIR__
                            . '/functions/available-bookings.php';

                        break;


                    case 'details':

                        include __DIR__
                            . '/functions/appointment-details.php';

                        break;


                    case 'customers':

                        include __DIR__
                            . '/functions/view-customers.php';

                        break;

                    case 'profile':
                        include __DIR__ . '/functions/my-profile.php';
                        
                        break;

                    
                    case 'replaced-parts':

                        include __DIR__
                            . '/functions/replaced-parts.php';

                        break;
                                    
                    
                    case 'today':

                    default:

                        include __DIR__
                            . '/functions/today-schedule.php';

                        break;

                }


                ?>


            </div>


            <!-- QUICK ACTIONS -->

            <div class="dashboard-panel">


                <?php

                include __DIR__
                    . '/../includes/quick-action-panel.php';

                ?>


            </div>


        </div>


    </section>

</main>