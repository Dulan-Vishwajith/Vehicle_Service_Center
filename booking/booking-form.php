<?php

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/


if (!isset($_SESSION["user_id"])) {

    // Build redirect URL
    $redirectUrl = "../booking/booking.php";

    if ($serviceId > 0) {
        $redirectUrl .= "?service_id=" . $serviceId;
    }

    // Save redirect URL for after login
    $_SESSION["redirect_after_login"] = $redirectUrl;

    // Redirect to login
    header("Location: ../login/login-form.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| BOOKING ERROR / OLD DATA
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "";

if (isset($_SESSION["booking_message"])) {

    $message =
        $_SESSION["booking_message"];

    $messageType =
        $_SESSION["booking_message_type"]
        ?? "error";

    unset(
        $_SESSION["booking_message"],
        $_SESSION["booking_message_type"]
    );
}


$old =
    $_SESSION["booking_old"]
    ?? [];

unset($_SESSION["booking_old"]);


/*
|--------------------------------------------------------------------------
| OLD SERVICES
|--------------------------------------------------------------------------
*/

$oldServices =
    array_map(
        "intval",
        $old["services"] ?? []
    );


/*
|--------------------------------------------------------------------------
| GET SERVICES
|--------------------------------------------------------------------------
*/

$services = [];

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            service_name,
            category,
            description,
            price,
            duration,
            duration_minutes,
            icon
        FROM services
        WHERE status = 1
        ORDER BY category ASC, service_name ASC
    ");

    $stmt->execute();

    $services =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $message =
        "Unable to load services.";

    $messageType =
        "error";
}


/*
|--------------------------------------------------------------------------
| GET TIME SLOTS
|--------------------------------------------------------------------------
*/

$timeSlots = [];

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            slot_name,
            start_time,
            end_time,
            max_bookings
        FROM time_slots
        WHERE status = 1
        ORDER BY start_time ASC
    ");

    $stmt->execute();

    $timeSlots =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $message =
        "Unable to load time slots.";

    $messageType =
        "error";
}

?>


<main class="booking-page">


    <!-- =====================================================
         MESSAGE
         ===================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="container"
            id="bookingMessageContainer"
        >

            <div
                class="booking-message <?= htmlspecialchars($messageType) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         BOOKING CONTAINER
         ===================================================== -->

    <div class="container">

        <div class="booking-layout">


            <!-- =================================================
                 LEFT SIDE
                 ================================================= -->

            <form
                action="booking-submit.php"
                method="POST"
                id="bookingForm"
                class="booking-form-card"
            >


                <!-- =============================================
                     FORM HEADER
                     ============================================= -->

                <div class="booking-form-header">

                    <span class="booking-label">
                        SERVICE BOOKING
                    </span>

                    <h1>
                        Book Your Vehicle Service
                    </h1>

                    <p>
                        Select the services and appointment
                        time for your vehicle.
                    </p>

                </div>


                <!-- =============================================
                     01. SELECT SERVICES
                     ============================================= -->

                <section class="booking-section">


                    <div class="section-title">

                        <span>
                            01.
                        </span>

                        Select Services

                    </div>


                    <p class="section-description">

                        Select a service from the dropdown.
                        You can add multiple services.

                    </p>


                    <div class="form-group">


                        <label for="serviceSelector">

                            Services
                            <span class="required">*</span>

                        </label>


                        <!--
                        IMPORTANT:

                        This is NOT a multiple select.

                        Customer selects one service,
                        it gets added below,
                        then the dropdown resets.
                        -->


                        <select
                            id="serviceSelector"
                            class="form-control service-selector"
                        >

                            <option value="">
                                Select a service...
                            </option>


                            <?php foreach ($services as $service): ?>

                                <option
                                        value="<?= (int)$service["id"] ?>"
                                        data-name="<?= htmlspecialchars(
                                            $service["service_name"],
                                            ENT_QUOTES
                                        ) ?>"
                                        data-price="<?= htmlspecialchars(
                                            $service["price"],
                                            ENT_QUOTES
                                        ) ?>"
                                        data-duration="<?= (int)$service["duration_minutes"] ?>"
                                        data-duration-text="<?= htmlspecialchars(
                                            $service["duration"],
                                            ENT_QUOTES
                                        ) ?>"
                                        <?= (
                                            isset($serviceId) &&
                                            $serviceId == $service["id"]
                                        ) ? "selected" : "" ?>
                                    >

                                    <?= htmlspecialchars(
                                        $service["service_name"]
                                    ) ?>

                                    -
                                    Rs.
                                    <?= number_format(
                                        (float)$service["price"],
                                        2
                                    ) ?>

                                    -
                                    <?= htmlspecialchars(
                                        $service["duration"]
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                    </div>


                    <!-- =========================================
                         SELECTED SERVICES
                         ========================================= -->

                    <div
                        id="selectedServices"
                        class="selected-services"
                    >

                        <div class="no-services">

                            No services selected yet.

                        </div>

                    </div>


                    <!--
                    JavaScript creates:

                    <input
                        type="hidden"
                        name="services[]"
                        value="1"
                    >

                    for every selected service.
                    -->

                    <div
                        id="serviceInputs"
                    ></div>


                </section>


                <!-- =============================================
                     02. VEHICLE INFORMATION
                     ============================================= -->

                <section class="booking-section">


                    <div class="section-title">

                        <span>
                            02.
                        </span>

                        Vehicle Information

                    </div>


                    <div class="form-grid">


                        <!-- VEHICLE MODEL -->

                        <div class="form-group">

                            <label for="vehicleModel">

                                Vehicle Make & Model
                                <span class="required">*</span>

                            </label>


                            <input
                                type="text"
                                id="vehicleModel"
                                name="vehicleModel"
                                class="form-control"
                                placeholder="e.g. Toyota Aqua"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    $old["vehicleModel"] ?? ""
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- REGISTRATION -->

                        <div class="form-group">

                            <label for="licensePlate">

                                Registration Number
                                <span class="required">*</span>

                            </label>


                            <input
                                type="text"
                                id="licensePlate"
                                name="licensePlate"
                                class="form-control"
                                placeholder="e.g. ABC-1234"
                                maxlength="20"
                                value="<?= htmlspecialchars(
                                    $old["licensePlate"] ?? ""
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- VEHICLE YEAR -->

                        <div class="form-group">

                            <label for="vehicleYear">

                                Vehicle Year

                            </label>


                            <select
                                id="vehicleYear"
                                name="vehicleYear"
                                class="form-control"
                            >

                                <option value="">
                                    Select year
                                </option>


                                <?php

                                $currentYear =
                                    (int)date("Y");

                                for (
                                    $year = $currentYear;
                                    $year >= 2010;
                                    $year--
                                ):

                                ?>

                                    <option
                                        value="<?= $year ?>"
                                        <?= (
                                            ($old["vehicleYear"] ?? "")
                                            == $year
                                        )
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= $year ?>

                                    </option>

                                <?php endfor; ?>

                            </select>

                        </div>


                        <!-- VEHICLE TYPE -->

                        <div class="form-group">

                            <label for="vehicleType">

                                Vehicle Type

                            </label>


                            <select
                                id="vehicleType"
                                name="vehicleType"
                                class="form-control"
                            >

                                <option value="">
                                    Select vehicle type
                                </option>


                                <?php

                                $vehicleTypes = [
                                    "Car",
                                    "SUV",
                                    "Van",
                                    "Pickup",
                                    "Motorcycle"
                                ];

                                foreach (
                                    $vehicleTypes as $type
                                ):

                                ?>

                                    <option
                                        value="<?= htmlspecialchars($type) ?>"
                                        <?= (
                                            ($old["vehicleType"] ?? "")
                                            === $type
                                        )
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= htmlspecialchars($type) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                    </div>

                </section>


                <!-- =============================================
                     03. APPOINTMENT
                     ============================================= -->

                <section class="booking-section">


                    <div class="section-title">

                        <span>
                            03.
                        </span>

                        Appointment Details

                    </div>


                    <div class="form-grid">


                        <!-- DATE -->

                        <div class="form-group">

                            <label for="bookingDate">

                                Service Date
                                <span class="required">*</span>

                            </label>


                            <input
                                type="date"
                                id="bookingDate"
                                name="bookingDate"
                                class="form-control"
                                min="<?= date("Y-m-d") ?>"
                                value="<?= htmlspecialchars(
                                    $old["bookingDate"] ?? ""
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- TIME SLOT -->

                        <div class="form-group">

                            <label for="timeSlot">

                                Time Slot
                                <span class="required">*</span>

                            </label>


                            <select
                                id="timeSlot"
                                name="timeSlot"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select a time slot
                                </option>


                                <?php foreach (
                                    $timeSlots as $slot
                                ): ?>


                                    <?php

                                    $startTime =
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $slot["start_time"]
                                            )
                                        );

                                    $endTime =
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $slot["end_time"]
                                            )
                                        );

                                    ?>


                                    <option
                                        value="<?= (int)$slot["id"] ?>"
                                        <?= (
                                            ($old["timeSlot"] ?? "")
                                            == $slot["id"]
                                        )
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= htmlspecialchars(
                                            $slot["slot_name"]
                                        ) ?>

                                        -
                                        <?= $startTime ?>
                                        -
                                        <?= $endTime ?>

                                    </option>


                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- NOTES -->

                        <div class="form-group full-width">

                            <label for="notes">

                                Additional Notes

                            </label>


                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                maxlength="1000"
                                placeholder="Tell us about any specific vehicle problem or service requirement..."
                            ><?= htmlspecialchars(
                                $old["notes"] ?? ""
                            ) ?></textarea>

                        </div>


                    </div>

                </section>


                <!-- =============================================
                     TERMS
                     ============================================= -->

                <div class="booking-terms">

                    <label class="terms-label">

                        <input
                            type="checkbox"
                            name="terms"
                            value="1"
                            required
                        >

                        <span>

                            I confirm that the information provided
                            is correct and I agree to the booking
                            terms and conditions.

                        </span>

                    </label>

                </div>


                <!-- =============================================
                     BUTTONS
                     ============================================= -->

                <div class="booking-actions">


                    <a
                        href="../index.php"
                        class="back-link"
                    >

                        ← Back

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary booking-submit"
                    >

                        Continue to Payment →

                    </button>


                </div>


            </form>


            <!-- =================================================
                 RIGHT SIDE - SUMMARY
                 ================================================= -->

            <aside class="booking-summary">


                <!-- HEADER -->

                <div class="summary-header">

                    <span class="summary-label">

                        YOUR BOOKING

                    </span>


                    <h2>

                        Service Summary

                    </h2>

                </div>


                <!-- =============================================
                     SELECTED SERVICES
                     ============================================= -->

                <div
                    id="selectedServicesSummary"
                    class="summary-services"
                >

                    <p class="empty-summary">

                        Select one or more services.

                    </p>

                </div>


                <!-- =============================================
                     DETAILS
                     ============================================= -->

                <div class="summary-details">


                    <div class="summary-row">

                        <span>
                            Estimated Time
                        </span>

                        <strong id="totalDuration">
                            0 Minutes
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Service Price
                        </span>

                        <strong id="servicePrice">
                            Rs. 0.00
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Booking Deposit
                        </span>

                        <strong>
                            Rs. 2,500.00
                        </strong>

                    </div>


                </div>


                <!-- =============================================
                     TOTAL
                     ============================================= -->

                <div class="summary-total">


                    <div class="total-row">

                        <span>
                            Service Total
                        </span>

                        <strong id="serviceTotal">
                            Rs. 0.00
                        </strong>

                    </div>


                    <div class="pay-row">

                        <span>
                            Pay Now
                        </span>

                        <strong>
                            Rs. 2,500.00
                        </strong>

                    </div>


                    <p class="remaining-text">

                        The remaining amount will be payable
                        at the VEYRO service centre.

                    </p>


                </div>


                <!-- =============================================
                     SECURE BOOKING
                     ============================================= -->

                <div class="secure-booking">

                    <div class="secure-icon">
                        🔒
                    </div>


                    <div>

                        <strong>
                            Secure Booking
                        </strong>

                        <p>
                            Your information is securely protected.
                        </p>

                    </div>

                </div>


            </aside>


        </div>

    </div>

</main>


<!-- =========================================================
     OLD SELECTED SERVICES
     ========================================================= -->

<script>

window.oldSelectedServices =
    <?= json_encode(
        $oldServices,
        JSON_UNESCAPED_UNICODE
    ) ?>;

</script>



</body>

</html>