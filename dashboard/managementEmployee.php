<?php

/*
|--------------------------------------------------------------------------
| Management Employee Dashboard Functions
|--------------------------------------------------------------------------
|
| This file contains functions related to Management Employee users.
|
| Functions:
|   1. managementAction()
|   2. renderManagement()
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Handle Management Dashboard Actions
|--------------------------------------------------------------------------
|
| Processes actions submitted from the Management dashboard.
|
| Supported actions:
|   - add_offer
|   - toggle_service
|
|--------------------------------------------------------------------------
*/

function managementAction(
    PDO $pdo,
    int $managementId,
    array $d
): string {

    /*
    |--------------------------------------------------------------------------
    | Get Requested Action
    |--------------------------------------------------------------------------
    */

    $action = $d['dashboard_action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Create New Offer
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_offer') {

        $query = "
            INSERT INTO offers
            (
                title,
                description,
                type,
                discount,
                start_date,
                end_date
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            trim($d['title']),
            trim($d['description']),
            $d['type'],
            (float) $d['discount'],
            $d['start_date'],
            $d['end_date']
        ]);


        return "Offer created successfully.";

    }


    /*
    |--------------------------------------------------------------------------
    | Enable / Disable Service
    |--------------------------------------------------------------------------
    */

    if ($action === 'toggle_service') {

        $query = "
            UPDATE services

            SET status = ?

            WHERE id = ?
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            (int) $d['new_status'],
            (int) $d['service_id']
        ]);


        return "Service status updated.";

    }


    /*
    |--------------------------------------------------------------------------
    | No Recognized Action
    |--------------------------------------------------------------------------
    */

    return "";

}


/*
|--------------------------------------------------------------------------
| Render Management Employee Dashboard
|--------------------------------------------------------------------------
|
| Retrieves management-related information from the database and
| displays the relevant sections of the dashboard.
|
|--------------------------------------------------------------------------
*/

function renderManagement(
    PDO $pdo,
    int $managementId
): void {

    /*
    |--------------------------------------------------------------------------
    | Initialize Variables
    |--------------------------------------------------------------------------
    */

    $users = [];

    $services = [];

    $packages = [];

    $branches = [];

    $offers = [];

    $feedback = [];

    $assistantRatings = [];

    $appointments = 0;

    $revenue = 0;


    try {

        /*
        |--------------------------------------------------------------------------
        | Get All Users
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                u.name,
                u.email,
                u.phone,
                r.role_name

            FROM users AS u

            LEFT JOIN roles AS r
                ON u.role_id = r.role_id

            ORDER BY u.user_id DESC
        ";


        $stmt = $pdo->query($query);

        $users = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get All Services
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                id,
                service_name,
                category,
                price,
                duration,
                status

            FROM services

            ORDER BY id DESC
        ";


        $stmt = $pdo->query($query);

        $services = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Service Packages
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                id,
                package_name,
                price,
                duration,
                status

            FROM service_packages

            ORDER BY id DESC
        ";


        $stmt = $pdo->query($query);

        $packages = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Branches
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                branch_id,
                name,
                location,
                contact_number

            FROM branches

            ORDER BY branch_id DESC
        ";


        $stmt = $pdo->query($query);

        $branches = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Offers
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                title,
                type,
                discount,
                start_date,
                end_date

            FROM offers

            ORDER BY offer_id DESC
        ";


        $stmt = $pdo->query($query);

        $offers = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Customer Feedback
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                sr.rating,
                sr.comment,
                u.name AS customer_name

            FROM service_ratings AS sr

            LEFT JOIN users AS u
                ON sr.user_id = u.user_id

            ORDER BY sr.rating_id DESC
        ";


        $stmt = $pdo->query($query);

        $feedback = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Private Service Assistant Ratings
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                ar.rating,
                ar.comment,
                c.name AS customer_name,
                a.name AS assistant_name

            FROM assistant_ratings AS ar

            LEFT JOIN users AS c
                ON ar.user_id = c.user_id

            LEFT JOIN users AS a
                ON ar.assistant_id = a.user_id

            ORDER BY ar.assistant_rating_id DESC
        ";


        $stmt = $pdo->query($query);

        $assistantRatings = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Total Appointments
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT COUNT(*)

            FROM appointments
        ";


        $appointments = (int) $pdo
            ->query($query)
            ->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | Get Total Revenue / Payments
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                COALESCE(
                    SUM(amount),
                    0
                )

            FROM payments
        ";


        $revenue = (float) $pdo
            ->query($query)
            ->fetchColumn();

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | If a Database Query Fails
        |--------------------------------------------------------------------------
        |
        | Empty values are used so that the dashboard can still load.
        |
        |--------------------------------------------------------------------------
        */

        $users = [];

        $services = [];

        $packages = [];

        $branches = [];

        $offers = [];

        $feedback = [];

        $assistantRatings = [];

        $appointments = 0;

        $revenue = 0;

    }

    ?>


    <!-- =====================================================================
         MANAGEMENT DASHBOARD STATISTICS
         ===================================================================== -->

    <div class="stats">


        <!-- Total Users -->

        <div>

            <span>
                Users
            </span>

            <b>
                <?= count($users) ?>
            </b>

        </div>


        <!-- Total Appointments -->

        <div>

            <span>
                Appointments
            </span>

            <b>
                <?= $appointments ?>
            </b>

        </div>


        <!-- Active Services -->

        <div>

            <span>
                Active Services
            </span>

            <b>

                <?=
                    count(
                        array_filter(
                            $services,
                            fn($x) =>
                                (int) $x['status'] === 1
                        )
                    )
                ?>

            </b>

        </div>


        <!-- Total Payments -->

        <div>

            <span>
                Payments
            </span>

            <b>

                Rs.

                <?= number_format(
                    $revenue,
                    2
                ) ?>

            </b>

        </div>


    </div>



    <!-- =====================================================================
         MONITOR OPERATIONS
         ===================================================================== -->

    <section class="card">


        <h2>
            Monitor Operations
        </h2>


        <p class="muted">

            Overview of appointments, services,
            users and payment activity.

        </p>


    </section>



    <!-- =====================================================================
         CUSTOMER FEEDBACK
         ===================================================================== -->

    <section class="card">


        <h2>
            Customer Feedback
        </h2>


        <?php if ($feedback): ?>


            <div class="table-wrap">


                <table>


                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Rating
                        </th>

                        <th>
                            Feedback
                        </th>

                    </tr>


                    <?php foreach ($feedback as $f): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $f['customer_name'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= $f['rating'] ?>/5

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $f['comment'] ?? ''
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <p class="empty">
                No feedback.
            </p>


        <?php endif; ?>


    </section>



    <!-- =====================================================================
         PRIVATE SERVICE ASSISTANT RATINGS
         ===================================================================== -->

    <section class="card">


        <h2>
            Private Assistant Ratings
        </h2>


        <?php if ($assistantRatings): ?>


            <div class="table-wrap">


                <table>


                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Assistant
                        </th>

                        <th>
                            Rating
                        </th>

                        <th>
                            Comment
                        </th>

                    </tr>


                    <?php foreach ($assistantRatings as $r): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $r['customer_name'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $r['assistant_name'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= $r['rating'] ?>/5

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $r['comment'] ?? ''
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <p class="empty">
                No assistant ratings.
            </p>


        <?php endif; ?>


    </section>



    <!-- =====================================================================
         MANAGE OFFERS
         ===================================================================== -->

    <section class="card">


        <h2>
            Manage Offers
        </h2>


        <form
            method="post"
            class="form-grid"
        >


            <!-- Dashboard Action -->

            <input
                type="hidden"
                name="dashboard_action"
                value="add_offer"
            >


            <!-- Offer Title -->

            <label>

                Title

                <input
                    type="text"
                    name="title"
                    required
                >

            </label>


            <!-- Offer Description -->

            <label>

                Description

                <input
                    type="text"
                    name="description"
                >

            </label>


            <!-- Offer Type -->

            <label>

                Type

                <select name="type">

                    <option value="Seasonal">
                        Seasonal
                    </option>

                    <option value="Regular Customer">
                        Regular Customer
                    </option>

                </select>

            </label>


            <!-- Discount -->

            <label>

                Discount (%)

                <input
                    type="number"
                    name="discount"
                    min="0"
                    step=".01"
                    required
                >

            </label>


            <!-- Start Date -->

            <label>

                Start

                <input
                    type="date"
                    name="start_date"
                    required
                >

            </label>


            <!-- End Date -->

            <label>

                End

                <input
                    type="date"
                    name="end_date"
                    required
                >

            </label>


            <!-- Submit -->

            <button
                class="button"
                type="submit"
            >

                Create Offer

            </button>


        </form>


    </section>



    <!-- =====================================================================
         MANAGE USERS
         ===================================================================== -->

    <section class="card">


        <h2>
            Manage Users
        </h2>


        <div class="table-wrap">


            <table>


                <tr>

                    <th>
                        Name
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Phone
                    </th>

                    <th>
                        Role
                    </th>

                </tr>


                <?php foreach ($users as $u): ?>


                    <tr>


                        <td>

                            <?= htmlspecialchars(
                                $u['name']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $u['email']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $u['phone']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $u['role_name'] ?? '-'
                            ) ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


            </table>


        </div>


    </section>



    <!-- =====================================================================
         MANAGE SERVICES
         ===================================================================== -->

    <section class="card">


        <h2>
            Manage Services
        </h2>


        <div class="table-wrap">


            <table>


                <tr>

                    <th>
                        Service
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>


                <?php foreach ($services as $s): ?>


                    <tr>


                        <!-- Service Name -->

                        <td>

                            <?= htmlspecialchars(
                                $s['service_name']
                            ) ?>

                        </td>


                        <!-- Category -->

                        <td>

                            <?= htmlspecialchars(
                                $s['category']
                            ) ?>

                        </td>


                        <!-- Price -->

                        <td>

                            Rs.

                            <?= number_format(
                                $s['price'],
                                2
                            ) ?>

                        </td>


                        <!-- Status -->

                        <td>

                            <?=
                                (int) $s['status']
                                    ? 'Active'
                                    : 'Inactive'
                            ?>

                        </td>


                        <!-- Enable / Disable -->

                        <td>


                            <form method="post">


                                <input
                                    type="hidden"
                                    name="dashboard_action"
                                    value="toggle_service"
                                >


                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?= $s['id'] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="new_status"
                                    value="<?=
                                        (int) $s['status']
                                            ? 0
                                            : 1
                                    ?>"
                                >


                                <button
                                    class="small-button"
                                    type="submit"
                                >

                                    <?=
                                        (int) $s['status']
                                            ? 'Disable'
                                            : 'Enable'
                                    ?>

                                </button>


                            </form>


                        </td>


                    </tr>


                <?php endforeach; ?>


            </table>


        </div>


    </section>



    <!-- =====================================================================
         PACKAGES AND BRANCHES
         ===================================================================== -->

    <section class="card">


        <h2>
            Packages &amp; Branches
        </h2>


        <div class="two">


            <!-- =================================================================
                 SERVICE PACKAGES
                 ================================================================= -->

            <div>


                <h3>
                    Packages
                </h3>


                <ul>


                    <?php foreach ($packages as $x): ?>


                        <li>

                            <?= htmlspecialchars(
                                $x['package_name']
                            ) ?>

                            —

                            Rs.

                            <?= number_format(
                                $x['price'],
                                2
                            ) ?>

                        </li>


                    <?php endforeach; ?>


                </ul>


            </div>



            <!-- =================================================================
                 BRANCHES
                 ================================================================= -->

            <div>


                <h3>
                    Branches
                </h3>


                <ul>


                    <?php foreach ($branches as $x): ?>


                        <li>

                            <?= htmlspecialchars(
                                $x['name']
                            ) ?>

                            —

                            <?= htmlspecialchars(
                                $x['location']
                            ) ?>

                        </li>


                    <?php endforeach; ?>


                </ul>


            </div>


        </div>


    </section>


    <?php

}