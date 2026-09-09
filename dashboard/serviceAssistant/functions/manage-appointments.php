<?php

$appointments = [];

try {

    $stmt = $pdo->prepare("

        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.total_price,
            b.status,

            u.name AS customer_name,

            ts.slot_name,
            ts.start_time,
            ts.end_time,

            GROUP_CONCAT(
                DISTINCT s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS services

        FROM bookings b

        INNER JOIN users u
            ON b.user_id = u.user_id

        LEFT JOIN time_slots ts
            ON b.time_slot_id = ts.id

        LEFT JOIN booking_services bs
            ON b.id = bs.booking_id

        LEFT JOIN services s
            ON bs.service_id = s.id

        WHERE b.assigned_assistant_id = ?

        GROUP BY
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.total_price,
            b.status,
            u.name,
            ts.slot_name,
            ts.start_time,
            ts.end_time

        ORDER BY
            b.booking_date ASC,
            ts.start_time ASC

    ");


    $stmt->execute([$assistantId]);


    $appointments =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $appointments = [];

}

?>


<div class="panel-header">

    <div>

        <span class="section-label">
            APPOINTMENTS
        </span>

        <h2>
            My Appointments
        </h2>

    </div>

</div>


<?php if (empty($appointments)): ?>


    <div class="empty-message">

        <h3>
            No Appointments Found
        </h3>

        <p>
            You have not confirmed any bookings yet.
        </p>

    </div>


<?php else: ?>


    <div class="my-bookings-list">


        <?php foreach ($appointments as $appointment): ?>


            <?php

            $status = strtolower(
                trim($appointment['status'] ?? '')
            );


            /*
            |--------------------------------------------------------------------------
            | STATUS ACTIONS
            |--------------------------------------------------------------------------
            */

            $actions = [

                'confirmed' => [

                    'next_status' => 'service',

                    'button' => 'Start Service',

                    'message' =>
                        'Are you sure you want to start this service?'

                ],


                'service' => [

                    'next_status' => 'vehicle_arrived',

                    'button' => 'Vehicle Arrived',

                    'message' =>
                        'Confirm that the vehicle has arrived?'

                ],


                'vehicle_arrived' => [

                    'next_status' => 'service_ongoing',

                    'button' => 'Start Service Work',

                    'message' =>
                        'Are you sure you want to start the service work?'

                ],


                'service_ongoing' => [

                    'next_status' => 'service_done',

                    'button' => 'Service Done',

                    'message' =>
                        'Are you sure the service is finished?'

                ],


                'service_done' => [

                    'next_status' => 'vehicle_handover',

                    'button' => 'Hand Over Vehicle',

                    'message' =>
                        'Are you sure you want to hand over the vehicle?'

                ],


                'vehicle_handover' => [

                    'next_status' => 'completed',

                    'button' => 'Complete Appointment',

                    'message' =>
                        'Are you sure the vehicle handover is complete?'

                ]

            ];

            ?>


            <div class="booking-card">


                <div class="booking-card-header">


                    <div>

                        <h3>

                            Booking #<?= (int) $appointment['id'] ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                $appointment['customer_name']
                            ) ?>

                        </p>

                    </div>


                    <span class="status">

                        <?= htmlspecialchars(

                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $status
                                )
                            )

                        ) ?>

                    </span>


                </div>


                <p>

                    <strong>
                        Vehicle:
                    </strong>

                    <?= htmlspecialchars(
                        $appointment['vehicle_model']
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $appointment['license_plate']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Service:
                    </strong>

                    <?= htmlspecialchars(

                        $appointment['services']
                        ?: 'Not available'

                    ) ?>

                </p>


                <p>

                    <strong>
                        Date & Time:
                    </strong>

                    <?= htmlspecialchars(
                        $appointment['booking_date']
                    ) ?>

                    |

                    <?= htmlspecialchars(
                        $appointment['start_time'] ?: ''
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $appointment['end_time'] ?: ''
                    ) ?>

                </p>


                <div class="booking-status-actions">


                    <!-- VIEW DETAILS -->

                    <a
                        href="?page=details&id=<?= (int) $appointment['id'] ?>"
                    >

                        View Details

                    </a>


                    <!-- STATUS ACTION -->

                    <?php if (isset($actions[$status])): ?>


                        <form
                            method="POST"
                            action="./serviceAssistant/functions/update-booking-status.php"
                            style="display:inline;"
                            onsubmit="return confirmBookingAction(this);"
                        >


                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= (int) $appointment['id'] ?>"
                            >


                            <input
                                type="hidden"
                                name="status"
                                value="<?= htmlspecialchars(
                                    $actions[$status]['next_status']
                                ) ?>"
                            >


                            <input
                                type="hidden"
                                name="confirmation_message"
                                value="<?= htmlspecialchars(
                                    $actions[$status]['message']
                                ) ?>"
                            >


                            <button type="submit">

                                <?= htmlspecialchars(
                                    $actions[$status]['button']
                                ) ?>

                            </button>


                        </form>


                    <?php elseif ($status === 'completed'): ?>


                        <span class="appointment-completed">

                            ✓ Completed

                        </span>


                    <?php endif; ?>


                </div>


            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>


<script>

function confirmBookingAction(form) {

    const message = form.querySelector(
        '[name="confirmation_message"]'
    ).value;


    return confirm(message);

}

</script>