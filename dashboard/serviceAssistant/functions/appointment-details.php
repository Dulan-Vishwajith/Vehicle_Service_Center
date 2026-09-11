<?php


$bookingId =
    (int) ($_GET['id'] ?? 0);


$booking = null;


if ($bookingId > 0) {


    try {


        $stmt = $pdo->prepare("

            SELECT

                b.*,

                u.name AS customer_name,

                u.email,

                u.phone,

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

            WHERE b.id = ?

            AND b.assigned_assistant_id = ?

            GROUP BY

                b.id,
                u.name,
                u.email,
                u.phone,
                ts.slot_name,
                ts.start_time,
                ts.end_time

        ");


        $stmt->execute([

            $bookingId,
            $assistantId

        ]);


        $booking =
            $stmt->fetch(PDO::FETCH_ASSOC);


    } catch (PDOException $e) {


        $booking = null;


    }


}


if (!$booking) {


    echo '

        <div class="empty-message">

            <h3>Booking Not Found</h3>

            <p>

                The booking is unavailable
                or belongs to another assistant.

            </p>

        </div>

    ';


    return;


}


$isMyBooking =
    (int) $booking['assigned_assistant_id']
    === $assistantId;

?>


<div class="panel-header">

    <div>

        <span class="section-label">
            BOOKING DETAILS
        </span>

        <h2>

            Booking #
            <?= (int) $booking['id'] ?>

        </h2>

    </div>

</div>


<div class="booking-card">


    <!-- CUSTOMER DETAILS -->

    <h3>
        Customer Details
    </h3>


    <p>

        <strong>Name:</strong>

        <?= htmlspecialchars(
            $booking['customer_name']
        ) ?>

    </p>


    <p>

        <strong>Email:</strong>

        <?= htmlspecialchars(
            $booking['email']
        ) ?>

    </p>


    <p>

        <strong>Phone:</strong>

        <?= htmlspecialchars(
            $booking['phone']
        ) ?>

    </p>


    <hr>


    <!-- VEHICLE DETAILS -->

    <h3>
        Vehicle Details
    </h3>


    <p>

        <strong>Vehicle:</strong>

        <?= htmlspecialchars(
            $booking['vehicle_model']
        ) ?>

    </p>


    <p>

        <strong>License Plate:</strong>

        <?= htmlspecialchars(
            $booking['license_plate']
        ) ?>

    </p>


    <hr>


    <!-- BOOKING DETAILS -->

    <h3>
        Booking Details
    </h3>


    <p>

        <strong>Services:</strong>

        <?= htmlspecialchars(
            $booking['services']
            ?: 'Not available'
        ) ?>

    </p>


    <p>

        <strong>Date:</strong>

        <?= htmlspecialchars(
            $booking['booking_date']
        ) ?>

    </p>


    <p>

        <strong>Time:</strong>

        <?= htmlspecialchars(
            $booking['start_time'] ?: ''
        ) ?>

        -

        <?= htmlspecialchars(
            $booking['end_time'] ?: ''
        ) ?>

    </p>


    <p>

        <strong>Total Price:</strong>

        Rs.

        <?= number_format(
            (float) $booking['total_price'],
            2
        ) ?>

    </p>


    <p>

        <strong>Status:</strong>

        <?= htmlspecialchars(
            ucfirst($booking['status'])
        ) ?>

    </p>


    <?php if (
        $isMyBooking
        && $booking['status'] === 'service_ongoing'
    ): ?>

        <div
            class="booking-card"
            style="margin-top:20px;"
        >

            <h3>
                🔧 Replaced Parts
            </h3>

            <p>
                Add and manage parts replaced during this service.
            </p>

            <a
                href="?page=replaced-parts&booking_id=<?= (int) $booking['id'] ?>"
                class="btn btn-primary"
            >
                Manage Replaced Parts
            </a>

        </div>

    <?php endif; ?>





    <!-- ACTIONS -->

    <div class="booking-status-actions">

        <?php if (
            $isMyBooking
            && $booking['status'] === 'confirmed'
        ): ?>

            <form
                method="POST"
                action="./serviceAssistant/functions/update-booking-status.php"
            >
                <input
                    type="hidden"
                    name="booking_id"
                    value="<?= (int) $booking['id'] ?>"
                >
                <input
                    type="hidden"
                    name="status"
                    value="service"
                >
                <button type="submit">
                    Start Service
                </button>
            </form>

        <?php elseif (
            $isMyBooking
            && $booking['status'] === 'service'
        ): ?>

            <form
                method="POST"
                action="./serviceAssistant/functions/update-booking-status.php"
            >
                <input
                    type="hidden"
                    name="booking_id"
                    value="<?= (int) $booking['id'] ?>"
                >
                <input
                    type="hidden"
                    name="status"
                    value="vehicle_arrived"
                >
                <button type="submit">
                    Vehicle Arrived
                </button>
            </form>

        <?php endif; ?>

        <a href="?page=appointments">
            Back to My Appointments
        </a>

    </div>

</div>