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
| DASHBOARD CARDS 
|-------------------------------------------------------------------------- 
*/ 
 
$dashboardCards = [ 
 
    [ 
        'icon' => '📅', 
        'label' => 'Upcoming Bookings', 
        'value' => '3' 
    ], 
 
    [ 
        'icon' => '✓', 
        'label' => 'Completed', 
        'value' => '12' 
    ], 
 
    [ 
        'icon' => '💰', 
        'label' => 'Total Spent', 
        'value' => 'Rs. 25,500' 
    ], 
 
    [ 
        'icon' => '⭐', 
        'label' => 'Reviews', 
        'value' => '5' 
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
        'title' => 'Make a Booking', 
        'link' => '../booking/booking-form.php' 
    ], 
 
    [ 
        'icon' => '📋', 
        'title' => 'My Bookings', 
        'link' => '#' 
    ], 
 
    [ 
        'icon' => '👤', 
        'title' => 'My Profile', 
        'link' => '#' 
    ], 
 
    [ 
        'icon' => '⭐', 
        'title' => 'My Reviews', 
        'link' => '#' 
    ] 
 
]; 
 
?> 
 
<link rel="stylesheet" href="../includes/css/header.css"> 
<link rel="stylesheet" href="../includes/css/global.css"> 
<link rel="stylesheet" href="./css/dashboard.css"> 
<link rel="stylesheet" href="../includes/css/footer.css"> 
 
 
<?php   include_once '../includes/header.php';  ?> 
 
 
<main class="dashboard-content"> 
    <section class="role-dashboard"> 
        
 
 
 
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
            //Include customer functions 
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
 
 
<?php   include_once '../includes/footer.php';  ?>