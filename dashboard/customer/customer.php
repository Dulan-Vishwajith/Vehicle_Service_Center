<?php 
/* 
|-------------------------------------------------------------------------- 
| CUSTOMER DASHBOARD 
|-------------------------------------------------------------------------- 
*/ 
 
$dashboardRole = "CUSTOMER"; 
 
$dashboardTitle = "Welcome Back!"; 
 
$dashboardDescription = "Manage your bookings, services and profile."; 
 
$dashboardButtonText = "New Booking"; 
 
$dashboardButtonLink = "../booking/booking.php"; 
 
 
 
/* 
|-------------------------------------------------------------------------- 
| QUICK ACTIONS 
|-------------------------------------------------------------------------- 
*/ 
 
$quickActions = [ 
 
    [ 
        'icon' => '📅', 
        'title' => 'Make a Booking', 
        'link' => '../booking/booking.php' 
    ], 
 
     [
        'icon' => '📋',
        'title' => 'My Bookings',
        'link' => '?page=bookings'
    ],

    [
        'icon' => '👤',
        'title' => 'My Profile',
        'link' => '?page=profile'
    ],

    [
        'icon' => '⭐',
        'title' => 'My Reviews',
        'link' => '?page=reviews'
    ]
];


require_once __DIR__ . '/../../config/database.php';

?> 
 
<link rel="stylesheet" href="customer/functions/functions.css">

<?php // Customer dashboard page ?> 

<main class="dashboard-content"> 
    <section class="role-dashboard"> 
        
 
 
<section class="role-dashboard"> 
 
    <!-- Dashboard Header --> 
    <?php 
        include __DIR__ . '/../includes/dashboard-header.php';
    ?> 
 
 
    <!-- Dashboard Cards --> 
    <?php
        include __DIR__ . '/functions/customer-dashboard-cards.php';
    ?> 
 
    <!-- Dashboard Grid --> 
    <div class="dashboard-grid"> 
 
        <!-- Functions --> 

        <div class="dashboard-panel">

            <?php

            $page = $_GET['page'] ?? 'status';

            switch ($page) {

                case 'bookings':
                    include __DIR__ . '/functions/my-bookings.php';
                    break;

                case 'profile':
                    include __DIR__ . '/functions/my-profile.php';
                    break;

                case 'reviews':
                    include __DIR__ . '/functions/my-reviews.php';
                    break;

                default:
                    include __DIR__ . '/functions/booking-status-panel.php';
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
 
 
