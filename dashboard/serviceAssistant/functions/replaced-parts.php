<?php

/*
|--------------------------------------------------------------------------
| REPLACED PARTS - SERVICE ASSISTANT
|--------------------------------------------------------------------------
*/

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$successMessage = '';
$errorMessage = '';

$parts = [];


/*
|--------------------------------------------------------------------------
| VALIDATE BOOKING ID
|--------------------------------------------------------------------------
*/

if ($bookingId <= 0) {

    echo '
        <div class="empty-message">
            <h3>Invalid Appointment</h3>
            <p>No valid booking was selected.</p>
        </div>
    ';

    return;
}


/*
|--------------------------------------------------------------------------
| VERIFY APPOINTMENT BELONGS TO CURRENT ASSISTANT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.status,
            b.total_price,
            u.name AS customer_name

        FROM bookings b

        INNER JOIN users u
            ON b.user_id = u.user_id

        WHERE b.id = ?
        AND b.assigned_assistant_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $bookingId,
        $assistantId
    ]);

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $booking = null;
}


/*
|--------------------------------------------------------------------------
| CHECK BOOKING
|--------------------------------------------------------------------------
*/

if (!$booking) {

    echo '
        <div class="empty-message">
            <h3>Appointment Not Found</h3>
            <p>This appointment does not belong to you.</p>
        </div>
    ';

    return;
}


/*
|--------------------------------------------------------------------------
| ONLY ALLOW ADDING / REMOVING PARTS DURING SERVICE
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'service_ongoing'
];

if (!in_array($booking['status'], $allowedStatuses, true)) {

    $errorMessage =
        'Replaced parts can only be added while the service is ongoing.';
}


/*
|--------------------------------------------------------------------------
| DELETE PART
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_part_id'])
    && empty($errorMessage)
) {

    $partId = (int) $_POST['delete_part_id'];

    try {

        /*
        |--------------------------------------------------------------------------
        | GET PART PRICE BEFORE DELETE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                total_price
            FROM replaced_parts
            WHERE id = ?
            AND booking_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $partId,
            $bookingId
        ]);

        $part = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$part) {

            $errorMessage =
                'Unable to find the replaced part.';

        } else {

            $partTotal = (float) $part['total_price'];


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | DELETE REPLACED PART
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM replaced_parts
                WHERE id = ?
                AND booking_id = ?
            ");

            $stmt->execute([
                $partId,
                $bookingId
            ]);


            // booking.total_price remains the original service total.
            // The final total is calculated as service total + replaced parts total.


            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $successMessage =
                'Replaced part removed successfully.';
        }

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorMessage =
            'Database error while removing the part.';
    }
}


/*
|--------------------------------------------------------------------------
| ADD REPLACED PART
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_replaced_part'])
    && empty($errorMessage)
) {

    $partName = trim(
        $_POST['part_name'] ?? ''
    );

    $partNumber = trim(
        $_POST['part_number'] ?? ''
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 0
    );

    $unitPrice = (float) (
        $_POST['unit_price'] ?? 0
    );

    $notes = trim(
        $_POST['notes'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($partName === '') {

        $errorMessage =
            'Please enter the part name.';

    } elseif ($quantity <= 0) {

        $errorMessage =
            'Quantity must be at least 1.';

    } elseif ($unitPrice < 0) {

        $errorMessage =
            'Unit price cannot be negative.';
    }


    /*
    |--------------------------------------------------------------------------
    | ADD PART + UPDATE TOTAL
    |--------------------------------------------------------------------------
    */

    if (empty($errorMessage)) {

        /*
        |--------------------------------------------------------------------------
        | CALCULATE PART TOTAL
        |--------------------------------------------------------------------------
        */

        $totalPrice =
            $quantity * $unitPrice;


        try {

            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | INSERT REPLACED PART
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO replaced_parts (
                    booking_id,
                    added_by,
                    part_name,
                    part_number,
                    quantity,
                    unit_price,
                    total_price,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([

                $bookingId,

                $assistantId,

                $partName,

                $partNumber !== ''
                    ? $partNumber
                    : null,

                $quantity,

                $unitPrice,

                $totalPrice,

                $notes !== ''
                    ? $notes
                    : null
            ]);


            // Do not change bookings.total_price here.
            // It represents the service total; parts are calculated separately.


            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $successMessage =
                'Replaced part added successfully.';


        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errorMessage =
                'Unable to add the replaced part.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD REPLACED PARTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            rp.*,
            u.name AS added_by_name

        FROM replaced_parts rp

        LEFT JOIN users u
            ON rp.added_by = u.user_id

        WHERE rp.booking_id = ?

        ORDER BY rp.created_at DESC
    ");

    $stmt->execute([
        $bookingId
    ]);

    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $parts = [];

    if (empty($errorMessage)) {

        $errorMessage =
            'Unable to load replaced parts.';
    }
}

?>


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="panel-header">

    <div>

        <span class="section-label">
            REPLACED PARTS
        </span>

        <h2>
            Booking #<?= (int) $bookingId ?>
        </h2>

        <p>

            <?= htmlspecialchars(
                $booking['vehicle_model']
            ) ?>

            -

            <?= htmlspecialchars(
                $booking['license_plate']
            ) ?>

        </p>

    </div>

</div>


<!-- =====================================================
     SUCCESS MESSAGE
====================================================== -->

<?php if ($successMessage): ?>

    <div class="booking-success-message">

        <?= htmlspecialchars(
            $successMessage
        ) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ERROR MESSAGE
====================================================== -->

<?php if ($errorMessage): ?>

    <div class="booking-error-message">

        <?= htmlspecialchars(
            $errorMessage
        ) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ADD PART FORM
====================================================== -->

<?php if (
    $booking['status'] === 'service_ongoing'
): ?>

    <div class="booking-card">

        <h3>
            Add Replaced Part
        </h3>

        <form
            method="POST"
            action="?page=replaced-parts&booking_id=<?= (int) $bookingId ?>"
        >

            <div class="form-grid">


                <!-- PART NAME -->

                <div>

                    <label>
                        Part Name *
                    </label>

                    <input
                        type="text"
                        name="part_name"
                        required
                        maxlength="150"
                        placeholder="e.g. Brake Pad"
                    >

                </div>


                <!-- PART NUMBER -->

                <div>

                    <label>
                        Part Number
                    </label>

                    <input
                        type="text"
                        name="part_number"
                        maxlength="100"
                        placeholder="e.g. BP-1234"
                    >

                </div>


                <!-- QUANTITY -->

                <div>

                    <label>
                        Quantity *
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        required
                    >

                </div>


                <!-- UNIT PRICE -->

                <div>

                    <label>
                        Unit Price (Rs.) *
                    </label>

                    <input
                        type="number"
                        name="unit_price"
                        value="0.00"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


            </div>


            <!-- NOTES -->

            <div>

                <label>
                    Notes
                </label>

                <textarea
                    name="notes"
                    rows="3"
                    placeholder="Reason for replacement or additional information..."
                ></textarea>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                name="add_replaced_part"
                value="1"
            >
                + Add Replaced Part
            </button>

        </form>

    </div>

<?php endif; ?>


<!-- =====================================================
     PARTS LIST
====================================================== -->

<div class="booking-card">

    <div class="booking-card-header">

        <div>

            <h3>
                Replaced Parts
            </h3>

            <p>
                <?= count($parts) ?> part(s) recorded
            </p>

        </div>

    </div>


    <?php if (empty($parts)): ?>


        <!-- NO PARTS -->

        <div class="empty-message">

            <h3>
                No Replaced Parts
            </h3>

            <p>
                No replacement parts have been recorded
                for this appointment.
            </p>

        </div>


    <?php else: ?>


        <!-- PARTS -->

        <div class="replaced-parts-list">

            <?php foreach ($parts as $part): ?>

                <div class="replaced-part-item">


                    <!-- PART INFORMATION -->

                    <div>

                        <h4>

                            <?= htmlspecialchars(
                                $part['part_name']
                            ) ?>

                        </h4>


                        <?php if (
                            !empty($part['part_number'])
                        ): ?>

                            <p>

                                <strong>
                                    Part No:
                                </strong>

                                <?= htmlspecialchars(
                                    $part['part_number']
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <p>

                            <strong>
                                Quantity:
                            </strong>

                            <?= (int) $part['quantity'] ?>

                        </p>


                        <p>

                            <strong>
                                Unit Price:
                            </strong>

                            Rs.

                            <?= number_format(
                                (float) $part['unit_price'],
                                2
                            ) ?>

                        </p>


                        <?php if (
                            !empty($part['notes'])
                        ): ?>

                            <p>

                                <strong>
                                    Notes:
                                </strong>

                                <?= htmlspecialchars(
                                    $part['notes']
                                ) ?>

                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- PRICE + REMOVE -->

                    <div>

                        <strong>

                            Rs.

                            <?= number_format(
                                (float) $part['total_price'],
                                2
                            ) ?>

                        </strong>


                        <?php if (
                            $booking['status']
                            === 'service_ongoing'
                        ): ?>

                            <form
                                method="POST"
                                action="?page=replaced-parts&booking_id=<?= (int) $bookingId ?>"
                                onsubmit="
                                    return confirm(
                                        'Remove this replaced part?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="delete_part_id"
                                    value="<?= (int) $part['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="cancel-booking-btn"
                                >
                                    Remove
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>


                </div>

            <?php endforeach; ?>

        </div>


        <!-- =====================================================
             REPLACED PARTS TOTAL
        ====================================================== -->

        <?php

        $partsTotal = 0;

        foreach ($parts as $part) {

            $partsTotal +=
                (float) $part['total_price'];
        }

        ?>


        <div class="booking-payment-section">

            <div>

                <span>
                    Replaced Parts Total
                </span>

                <strong>

                    Rs.

                    <?= number_format(
                        $partsTotal,
                        2
                    ) ?>

                </strong>

            </div>


            <!-- UPDATED BOOKING TOTAL -->

            <div>

                <span>
                    Service Total
                </span>

                <strong>

                    Rs.

                    <?= number_format(
                        (float) $booking['total_price'],
                        2
                    ) ?>

                </strong>

            </div>

            <div>

                <span>
                    Grand Total
                </span>

                <strong>

                    Rs.

                    <?= number_format(
                        (float) $booking['total_price'] + $partsTotal,
                        2
                    ) ?>

                </strong>

            </div>

        </div>


    <?php endif; ?>

</div>
