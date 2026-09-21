<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VEYRO</title>

    <link rel="stylesheet" href="includes/css/header.css">
    <link rel="stylesheet" href="includes/css/global.css">
    <link rel="stylesheet" href="public/css/hero.css">
    <link rel="stylesheet" href="public/css/quick-features.css">
    <link rel="stylesheet" href="public/css/services.css">
    <link rel="stylesheet" href="public/css/packages.css">
    <link rel="stylesheet" href="public/css/cta.css">
    <link rel="stylesheet" href="includes/css/footer.css">
    <link rel="stylesheet" href="public/css/offers.css">
</head>

<body>

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <?php require_once __DIR__ . '/public/hero.php'; ?>

    <?php require_once __DIR__ . '/public/quick-features.php'; ?>

    <?php require_once __DIR__ . '/public/services.php'; ?>

    <?php require_once __DIR__ . '/public/packages.php'; ?>

    <?php require_once __DIR__ . '/public/offers.php'; ?>

    <?php require_once __DIR__ . '/public/cta.php'; ?>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    

    <!-- Your page content here -->

</body>
</html>