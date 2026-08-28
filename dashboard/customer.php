<?php

/*
|--------------------------------------------------------------------------
| Customer Dashboard Functions
|--------------------------------------------------------------------------
|
| This file contains functions related to Customer users.
|
| Functions:
|   1. customerAction()
|   2. renderCustomer()
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Handle Customer Dashboard Actions
|--------------------------------------------------------------------------
|
| Processes actions submitted from the Customer dashboard.
|
|--------------------------------------------------------------------------
*/

function customerAction(
    PDO $pdo,
    int $userId,
    array $d
): string {

    /*
    |--------------------------------------------------------------------------
    | Check Requested Action
    |--------------------------------------------------------------------------
    */

    if (($d['dashboard_action'] ?? '') !== 'add_rating') {

        return "";

    }


    /*
    |--------------------------------------------------------------------------
    | Get Rating Details
    |--------------------------------------------------------------------------
    */

    $record = (int) ($d['service_record_id'] ?? 0);

    $rating = (int) ($d['rating'] ?? 0);

    $comment = trim(
        $d['comment'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Validate Rating
    |--------------------------------------------------------------------------
    */

    if (
        $record < 1 ||
        $rating < 1 ||
        $rating > 5
    ) {

        return "Please select a valid rating.";

    }


    /*
    |--------------------------------------------------------------------------
    | Verify Service Record
    |--------------------------------------------------------------------------
    |
    | Make sure that the selected service record actually belongs
    | to the currently logged-in customer.
    |
    |--------------------------------------------------------------------------
    */

    $query = "
        SELECT
            sr.service_record_id

        FROM service_records AS sr

        JOIN appointments AS a
            ON sr.appointment_id = a.appointment_id

        WHERE sr.service_record_id = ?
        AND a.user_id = ?
    ";


    $stmt = $pdo->prepare($query);

    $stmt->execute([
        $record,
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Invalid Service Record
    |--------------------------------------------------------------------------
    */

    if (!$stmt->fetchColumn()) {

        return "Invalid service record.";

    }


    /*
    |--------------------------------------------------------------------------
    | Insert Rating
    |--------------------------------------------------------------------------
    */

    $query = "
        INSERT INTO service_ratings
        (
            user_id,
            service_record_id,
            rating,
            comment
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ";


    $stmt = $pdo->prepare($query);

    $stmt->execute([
        $userId,
        $record,
        $rating,
        $comment
    ]);


    return "Rating submitted successfully.";

}


/*
|--------------------------------------------------------------------------
| Render Customer Dashboard
|--------------------------------------------------------------------------
|
| Retrieves the information belonging to the logged-in Customer and
| displays the Customer dashboard sections.
|
|--------------------------------------------------------------------------
*/

function renderCustomer(
    PDO $pdo,
    int $userId
): void {

    /*
    |--------------------------------------------------------------------------
    | Initialize Data
    |--------------------------------------------------------------------------
    */

    $appointments = [];

    $records = [];

    $offers = [];


    try {

        /*
        |--------------------------------------------------------------------------
        | Get Customer Appointments
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                a.appointment_id,
                a.appointment_date,
                a.status,
                v.brand,
                v.model,
                v.registration_number

            FROM appointments AS a

            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id

            WHERE a.user_id = ?

            ORDER BY a.appointment_date DESC
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            $userId
        ]);


        $appointments = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Customer Service Records
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                sr.service_record_id,
                a.appointment_date,
                ss.status_name,
                sr.total_cost,
                sr.amount_paid,
                v.brand,
                v.model

            FROM service_records AS sr

            JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id

            LEFT JOIN service_status AS ss
                ON sr.status_id = ss.status_id

            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id

            WHERE a.user_id = ?

            ORDER BY sr.service_record_id DESC
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            $userId
        ]);


        $records = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Current Offers
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                title,
                description,
                type,
                discount,
                start_date,
                end_date

            FROM offers

            WHERE start_date <= CURDATE()
            AND end_date >= CURDATE()

            ORDER BY end_date
        ";


        $stmt = $pdo->query($query);


        $offers = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | If Database Query Fails
        |--------------------------------------------------------------------------
        */

        $appointments = [];

        $records = [];

        $offers = [];

    }

    ?>


    <!-- =====================================================================
         CUSTOMER DASHBOARD STATISTICS
         ===================================================================== -->

    <div class="stats">


        <!-- Appointments Count -->

        <div>

            <span>
                Appointments
            </span>

            <b>
                <?= count($appointments) ?>
            </b>

        </div>


        <!-- Service Records Count -->

        <div>

            <span>
                Service Records
            </span>

            <b>
                <?= count($records) ?>
            </b>

        </div>


        <!-- Current Offers Count -->

        <div>

            <span>
                Current Offers
            </span>

            <b>
                <?= count($offers) ?>
            </b>

        </div>


    </div>



    <!-- =====================================================================
         MY APPOINTMENTS
         ===================================================================== -->

    <section class="card">


        <div class="title-row">


            <h2>
                My Appointments
            </h2>


            <a
                class="button"
                href="book-appointment.php"
            >
                Book Service
            </a>


        </div>


        <?php if ($appointments): ?>


            <div class="table-wrap">


                <table>


                    <tr>

                        <th>
                            Date
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Registration
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>


                    <?php foreach ($appointments as $a): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $a['appointment_date']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    trim(
                                        ($a['brand'] ?? '') .
                                        ' ' .
                                        ($a['model'] ?? '')
                                    )
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $a['registration_number'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $a['status'] ?? 'Pending'
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <p class="empty">
                No appointments found.
            </p>


        <?php endif; ?>


    </section>



    <!-- =====================================================================
         SERVICE TRACKING AND HISTORY
         ===================================================================== -->

    <section class="card">


        <h2>
            Service Tracking &amp; History
        </h2>


        <?php if ($records): ?>


            <div class="table-wrap">


                <table>


                    <tr>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Stage
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Paid
                        </th>

                    </tr>


                    <?php foreach ($records as $r): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    trim(
                                        ($r['brand'] ?? '') .
                                        ' ' .
                                        ($r['model'] ?? '')
                                    )
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $r['appointment_date']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $r['status_name'] ?? 'Pending'
                                ) ?>

                            </td>


                            <td>

                                Rs.

                                <?= number_format(
                                    (float) $r['total_cost'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                Rs.

                                <?= number_format(
                                    (float) $r['amount_paid'],
                                    2
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <p class="empty">
                No service records found.
            </p>


        <?php endif; ?>


    </section>



    <!-- =====================================================================
         CURRENT OFFERS
         ===================================================================== -->

    <section class="card">


        <h2>
            Offers
        </h2>


        <div class="grid">


            <?php foreach ($offers as $o): ?>


                <div class="mini">


                    <small>

                        <?= htmlspecialchars(
                            $o['type']
                        ) ?>

                    </small>


                    <h3>

                        <?= htmlspecialchars(
                            $o['title']
                        ) ?>

                    </h3>


                    <p>

                        <?= htmlspecialchars(
                            $o['description']
                        ) ?>

                    </p>


                    <b>

                        <?= htmlspecialchars(
                            $o['discount']
                        ) ?>% discount

                    </b>


                </div>


            <?php endforeach; ?>


            <?php if (!$offers): ?>


                <p class="empty">
                    No current offers.
                </p>


            <?php endif; ?>


        </div>


    </section>



    <!-- =====================================================================
         SERVICE RATING
         ===================================================================== -->

    <section class="card">


        <h2>
            Rate a Completed Service
        </h2>


        <?php if ($records): ?>


            <div class="forms">


                <?php foreach ($records as $r): ?>


                    <form
                        method="post"
                        class="inline-form"
                    >


                        <!-- Dashboard Action -->

                        <input
                            type="hidden"
                            name="dashboard_action"
                            value="add_rating"
                        >


                        <!-- Service Record ID -->

                        <input
                            type="hidden"
                            name="service_record_id"
                            value="<?= $r['service_record_id'] ?>"
                        >


                        <!-- Service Record Number -->

                        <b>

                            Service #<?= $r['service_record_id'] ?>

                        </b>


                        <!-- Rating -->

                        <select
                            name="rating"
                            required
                        >

                            <option value="">
                                Rating
                            </option>

                            <option value="5">
                                5
                            </option>

                            <option value="4">
                                4
                            </option>

                            <option value="3">
                                3
                            </option>

                            <option value="2">
                                2
                            </option>

                            <option value="1">
                                1
                            </option>

                        </select>


                        <!-- Feedback -->

                        <input
                            type="text"
                            name="comment"
                            placeholder="Feedback"
                        >


                        <!-- Submit -->

                        <button
                            class="button"
                            type="submit"
                        >
                            Submit
                        </button>


                    </form>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <p class="empty">
                Complete a service to submit a rating.
            </p>


        <?php endif; ?>


    </section>


    <?php

}