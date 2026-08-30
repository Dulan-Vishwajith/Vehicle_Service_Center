<?php

/*
|--------------------------------------------------------------------------
| VEYRO - Dashboard View
|--------------------------------------------------------------------------
| This file contains the dashboard page structure only.
| Authentication, role detection and action handling are performed by
| dashboard.php. Role-specific content is rendered by the files loaded
| through the customer, salesAssistant and managementEmployee folders.
|--------------------------------------------------------------------------
*/

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        VEYRO Dashboard
    </title>

    <!-- Main website styles -->
    <link
        rel="stylesheet"
        href="../includes/css/header.css"
    >

    <link
        rel="stylesheet"
        href="../includes/css/global.css"
    >

    <link
        rel="stylesheet"
        href="../includes/css/footer.css"
    >

    <!-- Dashboard styles -->
    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>

<body>

<?php

/* Main website header */
require_once "../includes/header.php";

?>

<!-- =====================================================================
     MAIN DASHBOARD CONTENT
     ===================================================================== -->

<main class="dashboard">

    <!-- Welcome section -->
    <section class="welcome">

        <small>
            VEYRO VEHICLE SERVICE CENTER
        </small>

        <h1>
            Welcome,
            <?php
            echo htmlspecialchars(
                $user["name"],
                ENT_QUOTES,
                "UTF-8"
            );
            ?>
        </h1>

        <p>
            <?php
            echo htmlspecialchars(
                $user["role_name"] ?? "User",
                ENT_QUOTES,
                "UTF-8"
            );
            ?>
            Dashboard
        </p>

    </section>

    <!-- Action message -->
    <?php if (!empty($message)): ?>

        <div class="message">
            <?php
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>
        </div>

    <?php endif; ?>

    <!-- =================================================================
         ROLE-SPECIFIC DASHBOARD
         ================================================================= -->

    <?php

    switch ($roleId) {

        case 1:
            renderCustomer($pdo, $userId);
            break;

        case 2:
            renderSalesAssistant($pdo, $userId);
            break;

        case 3:
            renderManagement($pdo, $userId);
            break;

        case 4:
            ?>
            <section class="card">

                <h2>
                    Admin Dashboard
                </h2>

                <p class="empty">
                    Admin functionality is not included in this dashboard.
                </p>

            </section>
            <?php
            break;

        default:
            ?>
            <section class="card">

                <h2>
                    Dashboard Not Available
                </h2>

                <p class="empty">
                    Your account role is not configured correctly.
                </p>

            </section>
            <?php
            break;
    }

    ?>

</main>

<?php

/* Main website footer */
require_once "../includes/footer.php";

?>

</body>
</html>
