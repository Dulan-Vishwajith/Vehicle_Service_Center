<?php

/*
|--------------------------------------------------------------------------
| Service Assistant Dashboard Functions
|--------------------------------------------------------------------------
|
| This file contains functions related to Service Assistant users.
|
| Functions:
|   1. salesAssistantAction()
|   2. renderSalesAssistant()
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Handle Service Assistant Dashboard Actions
|--------------------------------------------------------------------------
|
| Processes actions submitted from the Service Assistant dashboard.
|
| Supported actions:
|   - update_status
|   - save_record
|   - add_part
|
|--------------------------------------------------------------------------
*/

function salesAssistantAction(
    PDO $pdo,
    int $assistantId,
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
    | Update Service Status
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_status') {

        $record = (int) (
            $d['service_record_id'] ?? 0
        );

        $status = (int) (
            $d['status_id'] ?? 0
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Service Record and Status
        |--------------------------------------------------------------------------
        */

        if (
            !$record ||
            !$status
        ) {

            return "Select a record and status.";

        }


        /*
        |--------------------------------------------------------------------------
        | Update Service Record
        |--------------------------------------------------------------------------
        */

        $query = "
            UPDATE service_records

            SET
                status_id = ?,
                assistant_id = ?

            WHERE service_record_id = ?
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            $status,
            $assistantId,
            $record
        ]);


        return "Service status updated.";

    }


    /*
    |--------------------------------------------------------------------------
    | Save Service Record
    |--------------------------------------------------------------------------
    */

    if ($action === 'save_record') {

        $query = "
            INSERT INTO service_records
            (
                appointment_id,
                assistant_id,
                status_id,
                start_date,
                end_date,
                total_cost,
                amount_paid
            )

            VALUES
            (
                ?,
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
            (int) $d['appointment_id'],
            $assistantId,
            (int) $d['status_id'],
            $d['start_date'] ?: null,
            $d['end_date'] ?: null,
            (float) $d['total_cost'],
            (float) $d['amount_paid']
        ]);


        return "Service record saved.";

    }


    /*
    |--------------------------------------------------------------------------
    | Add Replaced Part and Warranty Information
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_part') {

        $query = "
            INSERT INTO replaced_parts
            (
                service_record_id,
                part_id,
                quantity,
                warranty_period,
                warranty_expiry_date
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $pdo->prepare($query);

        $stmt->execute([
            (int) $d['service_record_id'],
            (int) $d['part_id'],
            (int) $d['quantity'],
            $d['warranty_period'],
            $d['warranty_expiry_date'] ?: null
        ]);


        return "Part and warranty information added.";

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
| Render Service Assistant Dashboard
|--------------------------------------------------------------------------
|
| Retrieves appointment, service record, service status and part
| information required by the Service Assistant dashboard.
|
|--------------------------------------------------------------------------
*/

function renderSalesAssistant(
    PDO $pdo,
    int $assistantId
): void {

    /*
    |--------------------------------------------------------------------------
    | Initialize Variables
    |--------------------------------------------------------------------------
    */

    $appointments = [];

    $records = [];

    $statuses = [];

    $parts = [];


    try {

        /*
        |--------------------------------------------------------------------------
        | Get Appointments
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                a.appointment_id,
                a.appointment_date,
                a.status,
                u.name AS customer_name,
                v.brand,
                v.model,
                v.registration_number

            FROM appointments AS a

            LEFT JOIN users AS u
                ON a.user_id = u.user_id

            LEFT JOIN vehicles AS v
                ON a.vehicle_id = v.vehicle_id

            ORDER BY a.appointment_date DESC
        ";


        $stmt = $pdo->query($query);

        $appointments = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Service Records
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                sr.*,
                ss.status_name,
                u.name AS customer_name

            FROM service_records AS sr

            JOIN appointments AS a
                ON sr.appointment_id = a.appointment_id

            LEFT JOIN users AS u
                ON a.user_id = u.user_id

            LEFT JOIN service_status AS ss
                ON sr.status_id = ss.status_id

            ORDER BY sr.service_record_id DESC
        ";


        $stmt = $pdo->query($query);

        $records = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Service Statuses
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                status_id,
                status_name

            FROM service_status

            ORDER BY status_id
        ";


        $stmt = $pdo->query($query);

        $statuses = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | Get Available Parts
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT
                part_id,
                name

            FROM parts

            ORDER BY name
        ";


        $stmt = $pdo->query($query);

        $parts = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | If a Database Query Fails
        |--------------------------------------------------------------------------
        |
        | Empty arrays are used so that the dashboard can still load.
        |
        |--------------------------------------------------------------------------
        */

        $appointments = [];

        $records = [];

        $statuses = [];

        $parts = [];

    }

    ?>


    <!-- =====================================================================
         SERVICE ASSISTANT DASHBOARD STATISTICS
         ===================================================================== -->

    <div class="stats">


        <!-- Total Appointments -->

        <div>

            <span>
                Appointments
            </span>

            <b>
                <?= count($appointments) ?>
            </b>

        </div>


        <!-- Total Service Records -->

        <div>

            <span>
                Service Records
            </span>

            <b>
                <?= count($records) ?>
            </b>

        </div>


        <!-- Available Parts -->

        <div>

            <span>
                Parts
            </span>

            <b>
                <?= count($parts) ?>
            </b>

        </div>


    </div>



    <!-- =====================================================================
         MANAGE APPOINTMENTS
         ===================================================================== -->

    <section class="card">


        <h2>
            Manage Appointments
        </h2>


        <?php if ($appointments): ?>


            <div class="table-wrap">


                <table>


                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>


                    <?php foreach ($appointments as $a): ?>


                        <tr>


                            <!-- Appointment ID -->

                            <td>

                                #<?= $a['appointment_id'] ?>

                            </td>


                            <!-- Customer -->

                            <td>

                                <?= htmlspecialchars(
                                    $a['customer_name'] ?? '-'
                                ) ?>

                            </td>


                            <!-- Vehicle -->

                            <td>

                                <?= htmlspecialchars(
                                    trim(
                                        ($a['brand'] ?? '') .
                                        ' ' .
                                        ($a['model'] ?? '')
                                    )
                                ) ?>

                            </td>


                            <!-- Appointment Date -->

                            <td>

                                <?= htmlspecialchars(
                                    $a['appointment_date']
                                ) ?>

                            </td>


                            <!-- Appointment Status -->

                            <td>

                                <?= htmlspecialchars(
                                    $a['status'] ?? '-'
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <p class="empty">
                No appointments.
            </p>


        <?php endif; ?>


    </section>



    <!-- =====================================================================
         UPDATE SERVICE STAGE
         ===================================================================== -->

    <section class="card">


        <h2>
            Update Service Stage
        </h2>


        <?php foreach ($records as $r): ?>


            <form
                method="post"
                class="inline-form"
            >


                <!-- Dashboard Action -->

                <input
                    type="hidden"
                    name="dashboard_action"
                    value="update_status"
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


                <!-- Service Status -->

                <select name="status_id">


                    <?php foreach ($statuses as $s): ?>


                        <option
                            value="<?= $s['status_id'] ?>"
                            <?= (
                                $s['status_id'] == $r['status_id']
                            ) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $s['status_name']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


                <!-- Update Button -->

                <button
                    class="button"
                    type="submit"
                >

                    Update

                </button>


            </form>


        <?php endforeach; ?>


    </section>



    <!-- =====================================================================
         RECORD SERVICE DETAILS
         ===================================================================== -->

    <section class="card">


        <h2>
            Record Service Details
        </h2>


        <form
            method="post"
            class="form-grid"
        >


            <!-- Dashboard Action -->

            <input
                type="hidden"
                name="dashboard_action"
                value="save_record"
            >


            <!-- Appointment -->

            <label>

                Appointment


                <select
                    name="appointment_id"
                    required
                >


                    <?php foreach ($appointments as $a): ?>


                        <option
                            value="<?= $a['appointment_id'] ?>"
                        >

                            #<?= $a['appointment_id'] ?>

                            -

                            <?= htmlspecialchars(
                                $a['customer_name'] ?? 'Customer'
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </label>


            <!-- Service Status -->

            <label>

                Status


                <select name="status_id">


                    <?php foreach ($statuses as $s): ?>


                        <option
                            value="<?= $s['status_id'] ?>"
                        >

                            <?= htmlspecialchars(
                                $s['status_name']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </label>


            <!-- Start Date -->

            <label>

                Start


                <input
                    type="datetime-local"
                    name="start_date"
                >


            </label>


            <!-- End Date -->

            <label>

                End


                <input
                    type="datetime-local"
                    name="end_date"
                >


            </label>


            <!-- Total Cost -->

            <label>

                Total Cost


                <input
                    type="number"
                    name="total_cost"
                    min="0"
                    step=".01"
                >


            </label>


            <!-- Amount Paid -->

            <label>

                Amount Paid


                <input
                    type="number"
                    name="amount_paid"
                    min="0"
                    step=".01"
                >


            </label>


            <!-- Save Button -->

            <button
                class="button"
                type="submit"
            >

                Save Record

            </button>


        </form>


    </section>



    <!-- =====================================================================
         REPLACED PARTS AND WARRANTY
         ===================================================================== -->

    <section class="card">


        <h2>
            Replaced Parts &amp; Warranty
        </h2>


        <form
            method="post"
            class="form-grid"
        >


            <!-- Dashboard Action -->

            <input
                type="hidden"
                name="dashboard_action"
                value="add_part"
            >


            <!-- Service Record -->

            <label>

                Service Record


                <select
                    name="service_record_id"
                    required
                >


                    <?php foreach ($records as $r): ?>


                        <option
                            value="<?= $r['service_record_id'] ?>"
                        >

                            #<?= $r['service_record_id'] ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </label>


            <!-- Part -->

            <label>

                Part


                <select
                    name="part_id"
                    required
                >


                    <?php foreach ($parts as $p): ?>


                        <option
                            value="<?= $p['part_id'] ?>"
                        >

                            <?= htmlspecialchars(
                                $p['name']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </label>


            <!-- Quantity -->

            <label>

                Quantity


                <input
                    type="number"
                    name="quantity"
                    value="1"
                    min="1"
                >


            </label>


            <!-- Warranty Period -->

            <label>

                Warranty Period


                <input
                    type="text"
                    name="warranty_period"
                    placeholder="e.g. 6 Months"
                >


            </label>


            <!-- Warranty Expiry -->

            <label>

                Warranty Expiry


                <input
                    type="date"
                    name="warranty_expiry_date"
                >


            </label>


            <!-- Add Part Button -->

            <button
                class="button"
                type="submit"
            >

                Add Part

            </button>


        </form>


    </section>


    <?php

}