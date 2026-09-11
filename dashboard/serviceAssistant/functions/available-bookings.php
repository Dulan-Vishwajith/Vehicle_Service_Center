<?php

$bookings = [];

try {

    $stmt = $pdo->query("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.vehicle_year,
            b.vehicle_type,
            b.booking_date,
            b.total_price,
            b.total_duration_minutes,
            b.status,

            u.name AS customer_name,
            u.phone AS customer_phone,

            GROUP_CONCAT(
                DISTINCT s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS services

        FROM bookings b

        INNER JOIN users u
            ON b.user_id = u.user_id

        LEFT JOIN booking_services bs
            ON b.id = bs.booking_id

        LEFT JOIN services s
            ON bs.service_id = s.id

        WHERE
            b.assigned_assistant_id IS NULL

            AND b.status IN (
                'pending',
                'booked'
            )

            AND b.payment_status IN (
                'partial',
                'paid'
            )

        GROUP BY
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.vehicle_year,
            b.vehicle_type,
            b.booking_date,
            b.total_price,
            b.total_duration_minutes,
            b.status,
            u.name,
            u.phone

        ORDER BY
            b.booking_date ASC,
            b.id ASC
    ");

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $bookings = [];

}

?>


<div class="panel-header">

    <div>

        <span class="section-label">
            BOOKINGS
        </span>

        <h2>
            Assigned Booking Queue
        </h2>

    </div>

</div>


<?php if (empty($bookings)): ?>


    <div class="empty-message">

        <h3>
            No Available Bookings
        </h3>

        <p>
            Bookings are confirmed and assigned by management.
            Assigned appointments will appear in My Appointments.
        </p>

    </div>


<?php else: ?>


    <div class="my-bookings-list">


        <?php foreach ($bookings as $booking): ?>


            <div class="booking-card">


                <!-- Booking Header -->

                <div class="booking-card-header">

                    <div>

                        <h3>
                            Booking #<?= (int) $booking['id'] ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $booking['customer_name']
                            ) ?>
                        </p>

                    </div>


                    <span class="status">

                        <?= htmlspecialchars(
                            ucfirst($booking['status'])
                        ) ?>

                    </span>

                </div>


                <!-- Vehicle -->

                <p>

                    <strong>
                        Vehicle:
                    </strong>

                    <?= htmlspecialchars(
                        $booking['vehicle_model']
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $booking['license_plate']
                    ) ?>

                </p>


                <!-- Services -->

                <p>

                    <strong>
                        Services:
                    </strong>

                    <?= htmlspecialchars(
                        $booking['services']
                        ?: 'Not available'
                    ) ?>

                </p>


                <!-- Booking Date -->

                <p>

                    <strong>
                        Date:
                    </strong>

                    <?= htmlspecialchars(
                        $booking['booking_date']
                    ) ?>

                </p>


                <!-- Total Price -->

                <p>

                    <strong>
                        Total:
                    </strong>

                    Rs.

                    <?= number_format(
                        (float) $booking['total_price'],
                        2
                    ) ?>

                </p>


                <!-- Actions -->

                <div class="booking-status-actions">


                    <!-- View Details -->

                    <a
                        href="?page=details&id=<?= (int) $booking['id'] ?>"
                    >
                        View Details
                    </a>


                    <div class="booking-assignment-waiting">
                        <span class="booking-assignment-waiting-icon">✓</span>
                        <div>
                            <strong>Waiting for management assignment</strong>
                            <small>This booking is confirmed and will be assigned by the management team.</small>
                        </div>
                    </div>

                </div>


            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>