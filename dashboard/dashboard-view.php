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


    <!-- ================================================================
         MAIN WEBSITE STYLESHEET
         ================================================================ -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- ================================================================
         DASHBOARD STYLESHEET
         ================================================================ -->

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>


<body>


<!-- =====================================================================
     WEBSITE HEADER
     ===================================================================== -->

<?php

require_once "../includes/header.php";

?>



<!-- =====================================================================
     MAIN DASHBOARD
     ===================================================================== -->

<main class="dashboard">


    <!-- =================================================================
         WELCOME SECTION
         ================================================================= -->

    <section class="welcome">


        <small>
            VEYRO VEHICLE SERVICE CENTER
        </small>


        <h1>

            Welcome,

            <?php

            echo htmlspecialchars(
                $user['name'],
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </h1>


        <p>

            <?php

            echo htmlspecialchars(
                $user['role_name'] ?? 'User',
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

            Dashboard

        </p>


    </section>



    <!-- =================================================================
         ACTION MESSAGE
         ================================================================= -->

    <?php if (!empty($message)): ?>


        <div class="message">

            <?php

            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =================================================================
         DISPLAY ROLE-SPECIFIC DASHBOARD
         ================================================================= -->

    <?php


    /*
    |--------------------------------------------------------------------------
    | Customer Dashboard
    |--------------------------------------------------------------------------
    */

    if ($roleId === 1) {

        renderCustomer(
            $pdo,
            $userId
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Service Assistant Dashboard
    |--------------------------------------------------------------------------
    */

    elseif ($roleId === 2) {

        renderSalesAssistant(
            $pdo,
            $userId
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Management Dashboard
    |--------------------------------------------------------------------------
    */

    elseif ($roleId === 3) {

        renderManagement(
            $pdo,
            $userId
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    elseif ($roleId === 4) {

        ?>

        <section class="card">

            <h2>
                Admin Dashboard
            </h2>


            <p>
                Admin dashboard functionality is not available yet.
            </p>

        </section>

        <?php

    }


    /*
    |--------------------------------------------------------------------------
    | Unknown Role
    |--------------------------------------------------------------------------
    */

    else {

        ?>

        <section class="card">

            <h2>
                Dashboard Not Available
            </h2>


            <p>
                Your account role is not configured correctly.
            </p>

        </section>

        <?php

    }

    ?>


</main>



<!-- =====================================================================
     WEBSITE FOOTER
     ===================================================================== -->

<?php

require_once "../includes/footer.php";

?>


</body>

</html>