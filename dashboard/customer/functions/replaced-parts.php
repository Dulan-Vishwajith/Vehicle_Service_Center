<?php

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$userId = (int) ($_SESSION['user_id'] ?? 0);

$parts = [];

$booking = null;


if (
    $bookingId > 0
    && $userId > 0
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | GET CUSTOMER'S BOOKING
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.vehicle_model,
                b.license_plate,
                b.booking_date,
                b.status

            FROM bookings b

            WHERE b.id = ?
            AND b.user_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $bookingId,
            $userId
        ]);

        $booking =
            $stmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | GET REPLACED PARTS
        |--------------------------------------------------------------------------
        */

        if ($booking) {

            $stmt = $pdo->prepare("
                SELECT
                    part_name,
                    part_number,
                    quantity,
                    unit_price,
                    total_price,
                    notes,
                    created_at

                FROM replaced_parts

                WHERE booking_id = ?

                ORDER BY created_at DESC
            ");

            $stmt->execute([
                $bookingId
            ]);

            $parts =
                $stmt->fetchAll(PDO::FETCH_ASSOC);

        }

    } catch (PDOException $e) {

        $booking = null;
        $parts = [];

    }

}


if (!$booking) {

    echo '

        <div class="empty-message">

            <h3>
                Appointment Not Found
            </h3>

            <p>
                This appointment does not exist
                or does not belong to you.
            </p>

        </div>

    ';

    return;
}


/*
|--------------------------------------------------------------------------
| TOTAL REPLACED PARTS COST
|--------------------------------------------------------------------------
*/

$partsTotal = 0;

foreach ($parts as $part) {

    $partsTotal +=
        (float) $part['total_price'];

}

?>


<div class="panel-header">

    <div>

        <span class="section-label">
            SERVICE DETAILS
        </span>

        <h2>
            Replaced Parts
        </h2>

        <p>

            Booking #<?= (int) $bookingId ?>

            ·

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


<div class="booking-card">

    <div class="booking-card-header">

        <div>

            <h3>
                🔧 Parts Replaced During Service
            </h3>

            <p>
                Parts recorded by the service assistant
            </p>

        </div>

    </div>


    <?php if (empty($parts)): ?>

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


        <div class="replaced-parts-list">

            <?php foreach ($parts as $part): ?>

                <div class="replaced-part-item">

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
                                    Part Number:
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


                        <small>

                            Added:
                            <?= date(
                                'd M Y, g:i A',
                                strtotime(
                                    $part['created_at']
                                )
                            ) ?>

                        </small>

                    </div>


                    <div>

                        <strong>

                            Rs.
                            <?= number_format(
                                (float) $part['total_price'],
                                2
                            ) ?>

                        </strong>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <div class="booking-payment-section">

            <div>

                <span>
                    Total Replaced Parts
                </span>

                <strong>

                    Rs.
                    <?= number_format(
                        $partsTotal,
                        2
                    ) ?>

                </strong>

            </div>

        </div>

    <?php endif; ?>

</div>