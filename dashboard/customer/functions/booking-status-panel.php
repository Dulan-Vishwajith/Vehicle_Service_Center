<?php

$userId = $_SESSION['user_id'] ?? 0;
$bookings = [];


/* =====================================================
   GET BOOKINGS
===================================================== */

if ($userId > 0) {

    $sql = "
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.status,
            b.total_price
        FROM bookings b
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
        LIMIT 5
    ";


    try {

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$userId]);

        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        die("Database error: " . $e->getMessage());

    }

}


/* =====================================================
   STATUS CLASS
===================================================== */

/* =====================================================
   STATUS CLASS
===================================================== */

function getStatusClass($status)
{
    switch (strtolower(trim($status))) {

        case 'pending':
            return 'status-pending';

        case 'booked':
            return 'status-booked';

        case 'confirmed':
            return 'status-confirmed';

        case 'service':
        case 'in_progress':
        case 'in progress':
            return 'status-progress';

        case 'completed':
            return 'status-completed';

        default:
            return 'status-pending';
    }
}


/* =====================================================
   STATUS TEXT
===================================================== */

function getStatusText($status)
{
    return ucwords(
        str_replace('_', ' ', trim($status))
    );
}


/* =====================================================
   GET PROGRESS STEP
===================================================== */

function getStatusStep($status)
{
    switch (strtolower(trim($status))) {

        case 'pending':
        case 'booked':
            return 1;

        case 'confirmed':
            return 2;

        case 'service':
        case 'in_progress':
        case 'in progress':
            return 3;

        case 'completed':
            return 4;

        default:
            return 1;
    }
}
?>




<!-- =========================================
     BOOKING STATUS PANEL
========================================= -->

<div class="dashboard-panel booking-status-panel">


    <!-- PANEL HEADER -->

    <div class="panel-header">

        <h2>Booking Status</h2>


    </div>


    <?php if (!empty($bookings)): ?>


        <!-- =========================================
             BOOKING HEADER
        ========================================= -->

        <div class="booking-row booking-heading">

            <div class="booking-vehicle">
                Vehicle
            </div>

            <div class="booking-date">
                Date
            </div>

            <div class="booking-status">
                Status
            </div>

            <div class="booking-progress-column">
                Progress
            </div>

            <div class="booking-total">
                Total
            </div>

        </div>


        <!-- =========================================
             BOOKING ROWS
        ========================================= -->

        <?php foreach ($bookings as $booking): ?>

            <?php

                $currentStep = getStatusStep(
                    $booking['status']
                );

            ?>


            <div class="booking-row">


                <!-- ==============================
                     VEHICLE
                =============================== -->

                <div class="booking-vehicle">

                    <strong>

                        <?= htmlspecialchars(
                            $booking['vehicle_model']
                        ); ?>

                    </strong>

                    <small>

                        Booking #<?= htmlspecialchars(
                            $booking['id']
                        ); ?>

                        ·

                        <?= htmlspecialchars(
                            $booking['license_plate']
                        ); ?>

                    </small>

                </div>


                <!-- ==============================
                     DATE
                =============================== -->

                <div class="booking-date">

                    <?= date(
                        "d M Y",
                        strtotime(
                            $booking['booking_date']
                        )
                    ); ?>

                </div>


                <!-- ==============================
                     STATUS
                =============================== -->

                <div class="booking-status">

                    <span
                        class="status <?= getStatusClass(
                            $booking['status']
                        ); ?>"
                    >

                        <?= getStatusText(
                            $booking['status']
                        ); ?>

                    </span>

                </div>


                <!-- ==============================
                     PROGRESS
                =============================== -->

                <div class="booking-progress-column">

                    <div class="booking-status-progress">


                        <!-- BOOKED -->

                        <div class="booking-status-step <?= $currentStep >= 1 ? 'active' : ''; ?>">

                            <div class="booking-status-circle">

                                <?= $currentStep > 1 ? '✓' : '1'; ?>

                            </div>

                            <span>Booked</span>

                        </div>


                        <div class="booking-status-line <?= $currentStep >= 2 ? 'active' : ''; ?>"></div>


                        <!-- CONFIRMED -->

                        <div class="booking-status-step <?= $currentStep >= 2 ? 'active' : ''; ?>">

                            <div class="booking-status-circle">

                                <?= $currentStep > 2 ? '✓' : '2'; ?>

                            </div>

                            <span>Confirmed</span>

                        </div>


                        <div class="booking-status-line <?= $currentStep >= 3 ? 'active' : ''; ?>"></div>


                        <!-- SERVICE -->

                        <div class="booking-status-step <?= $currentStep >= 3 ? 'active' : ''; ?>">

                            <div class="booking-status-circle">

                                <?= $currentStep > 3 ? '✓' : '3'; ?>

                            </div>

                            <span>Service</span>

                        </div>


                        <div class="booking-status-line <?= $currentStep >= 4 ? 'active' : ''; ?>"></div>


                        <!-- COMPLETED -->

                        <div class="booking-status-step <?= $currentStep >= 4 ? 'active' : ''; ?>">

                            <div class="booking-status-circle">
                                4
                            </div>

                            <span>Completed</span>

                        </div>


                    </div>

                </div>

                <!-- ==============================
                     TOTAL
                =============================== -->

                <div class="booking-total">

                    <strong>

                        Rs. <?= number_format(
                            (float) $booking['total_price'],
                            2
                        ); ?>

                    </strong>

                </div>


            </div>


        <?php endforeach; ?>


    <?php else: ?>


        <!-- EMPTY STATE -->

        <div class="booking-empty">

            <span>📅</span>

            <p>
                No bookings available yet.
            </p>

            <a
                href="../booking/booking-form.php"
                class="btn btn-primary"
            >
                Make a Booking
            </a>

        </div>


    <?php endif; ?>


</div>