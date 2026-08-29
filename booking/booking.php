<?php

// Get service ID from URL
$serviceId = isset($_GET['service_id'])
    ? (int) $_GET['service_id']
    : 0;

// Pass selected service ID to booking form
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>VEYRO</title>

    <link rel="stylesheet" href="../includes/css/header.css">
    <link rel="stylesheet" href="../includes/css/global.css">
    <link rel="stylesheet" href="../includes/css/footer.css">
    <link rel="stylesheet" href="css/booking.css">

</head>

<body>

    <?php include '../includes/header.php'; ?>

    <?php include 'booking-form.php'; ?>

    <?php include '../includes/footer.php'; ?>

    <script src="js/booking.js"></script>

</body>

</html>