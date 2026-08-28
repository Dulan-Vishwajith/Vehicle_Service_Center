<?php

/*
|--------------------------------------------------------------------------
| START SESSION SAFELY
|--------------------------------------------------------------------------
| header.php may already have started the session.
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
| If the user is not logged in, remember the booking page
| and redirect them to the login page.
*/

if (!isset($_SESSION["user_id"])) {

    $_SESSION["redirect_after_login"] = "../booking/booking.php";

    header("Location: ../login/login-form.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| BOOKING MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "";

if (isset($_SESSION["booking_message"])) {

    $message = $_SESSION["booking_message"];

    $messageType = $_SESSION["booking_message_type"] ?? "error";

    unset(
        $_SESSION["booking_message"],
        $_SESSION["booking_message_type"]
    );
}


/*
|--------------------------------------------------------------------------
| OLD FORM VALUES
|--------------------------------------------------------------------------
*/

$old = $_SESSION["booking_old"] ?? [];

?>

<main class="booking-section">

    <!-- =========================================================
         BOOKING MESSAGE
         ========================================================= -->

    <?php if ($message !== ""): ?>

        <div class="container">

            <div class="booking-message <?= htmlspecialchars($messageType) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         BOOKING FORM
         ========================================================= -->

    <form
        action="booking-submit.php"
        method="POST"
        class="container booking-layout"
    >

        <!-- =====================================================
             LEFT SIDE
             ===================================================== -->

        <div class="booking-form-card">


            <!-- =================================================
                 FORM HEADER
                 ================================================= -->

            <div class="form-header">

                <span class="section-tag">
                    SERVICE BOOKING
                </span>

                <h2>
                    Tell Us About Your Vehicle
                </h2>

                <p>
                    Please provide accurate information so our
                    VEYRO service team can prepare for your visit.
                </p>

            </div>


            <!-- =================================================
                 01. CUSTOMER INFORMATION
                 ================================================= -->

            <div class="form-section">

                <h3>
                    01. Customer Information
                </h3>


                <div class="form-grid">


                    <!-- Full Name -->

                    <div class="form-group">

                        <label for="fullName">
                            Full Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="fullName"
                            name="fullName"
                            placeholder="Enter your full name"
                            maxlength="100"
                            value="<?= htmlspecialchars($old["fullName"] ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- Phone -->

                    <div class="form-group">

                        <label for="phone">
                            Contact Number <span>*</span>
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="e.g. 077 123 4567"
                            maxlength="20"
                            value="<?= htmlspecialchars($old["phone"] ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- Email -->

                    <div class="form-group full-width">

                        <label for="email">
                            Email Address <span>*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="example@email.com"
                            maxlength="150"
                            value="<?= htmlspecialchars($old["email"] ?? "") ?>"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                 02. VEHICLE INFORMATION
                 ================================================= -->

            <div class="form-section">

                <h3>
                    02. Vehicle Information
                </h3>


                <div class="form-grid">


                    <!-- Vehicle Model -->

                    <div class="form-group">

                        <label for="vehicleModel">
                            Vehicle Make & Model <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="vehicleModel"
                            name="vehicleModel"
                            placeholder="e.g. Toyota Aqua"
                            maxlength="100"
                            value="<?= htmlspecialchars($old["vehicleModel"] ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- Registration Number -->

                    <div class="form-group">

                        <label for="licensePlate">
                            Registration Number <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="licensePlate"
                            name="licensePlate"
                            placeholder="e.g. ABC-1234"
                            maxlength="20"
                            value="<?= htmlspecialchars($old["licensePlate"] ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- Vehicle Year -->

                    <div class="form-group">

                        <label for="vehicleYear">
                            Vehicle Year
                        </label>

                        <select
                            id="vehicleYear"
                            name="vehicleYear"
                        >

                            <option value="">
                                Select year
                            </option>

                            <?php

                            $currentYear = (int) date("Y");

                            for (
                                $year = $currentYear;
                                $year >= 2010;
                                $year--
                            ):

                            ?>

                                <option
                                    value="<?= $year ?>"
                                    <?= (($old["vehicleYear"] ?? "") == $year)
                                        ? "selected"
                                        : "" ?>
                                >

                                    <?= $year ?>

                                </option>

                            <?php endfor; ?>

                        </select>

                    </div>


                    <!-- Vehicle Type -->

                    <div class="form-group">

                        <label for="vehicleType">
                            Vehicle Type
                        </label>

                        <select
                            id="vehicleType"
                            name="vehicleType"
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

                            foreach ($vehicleTypes as $type):

                            ?>

                                <option
                                    value="<?= htmlspecialchars($type) ?>"
                                    <?= (($old["vehicleType"] ?? "") === $type)
                                        ? "selected"
                                        : "" ?>
                                >

                                    <?= htmlspecialchars($type) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 03. APPOINTMENT DETAILS
                 ================================================= -->

            <div class="form-section">

                <h3>
                    03. Appointment Details
                </h3>


                <div class="form-grid">


                    <!-- Service Date -->

                    <div class="form-group">

                        <label for="bookingDate">
                            Preferred Service Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="bookingDate"
                            name="bookingDate"
                            min="<?= date("Y-m-d") ?>"
                            value="<?= htmlspecialchars($old["bookingDate"] ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- Time Slot -->

                    <div class="form-group">

                        <label for="timeSlot">
                            Preferred Time <span>*</span>
                        </label>

                        <select
                            id="timeSlot"
                            name="timeSlot"
                            required
                        >

                            <option value="">
                                Select a time
                            </option>

                            <?php

                            $timeSlots = [

                                "08:00-10:00"
                                    => "08:00 AM - 10:00 AM",

                                "10:00-12:00"
                                    => "10:00 AM - 12:00 PM",

                                "13:00-15:00"
                                    => "01:00 PM - 03:00 PM",

                                "15:00-17:00"
                                    => "03:00 PM - 05:00 PM"

                            ];

                            foreach ($timeSlots as $value => $label):

                            ?>

                                <option
                                    value="<?= htmlspecialchars($value) ?>"
                                    <?= (($old["timeSlot"] ?? "") === $value)
                                        ? "selected"
                                        : "" ?>
                                >

                                    <?= htmlspecialchars($label) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Notes -->

                    <div class="form-group full-width">

                        <label for="notes">
                            Additional Notes
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            maxlength="1000"
                            placeholder="Tell us about any specific vehicle problem or service requirement..."
                        ><?= htmlspecialchars($old["notes"] ?? "") ?></textarea>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TERMS AND CONDITIONS
                 ================================================= -->

            <div class="terms">

                <label class="checkbox-container">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        value="1"
                        required
                    >

                    <span class="terms-text">

                        I confirm that the information provided is
                        correct and I agree to the VEYRO booking
                        terms and conditions.

                    </span>

                </label>

            </div>


            <!-- =================================================
                 FORM BUTTONS
                 ================================================= -->

            <div class="form-actions">

                <a
                    href="services.php"
                    class="back-button"
                >
                    ← Back to Services
                </a>


                <button
                    type="submit"
                    class="continue-button"
                >

                    Continue to Payment

                    <span>
                        →
                    </span>

                </button>

            </div>


        </div>


        <!-- =====================================================
             RIGHT SIDE - SERVICE SUMMARY
             ===================================================== -->

        <aside class="summary-card">


            <div class="summary-header">

                <span class="small-label">
                    YOUR BOOKING
                </span>

                <h2>
                    Service Summary
                </h2>

            </div>


            <div class="selected-service">

                <div class="service-icon">
                    🔧
                </div>


                <div>

                    <span class="service-category">
                        MOST POPULAR
                    </span>

                    <h3>
                        Standard Care
                    </h3>

                    <p>
                        Complete maintenance and inspection
                        for your vehicle.
                    </p>

                </div>

            </div>


            <div class="summary-details">


                <div class="summary-row">

                    <span>
                        Estimated Time
                    </span>

                    <strong>
                        2 - 3 Hours
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Service Price
                    </span>

                    <strong>
                        Rs. 11,500
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Booking Deposit
                    </span>

                    <strong>
                        Rs. 2,500
                    </strong>

                </div>


            </div>


            <div class="payment-summary">


                <div class="payment-row">

                    <span>
                        Service Total
                    </span>

                    <strong>
                        Rs. 11,500
                    </strong>

                </div>


                <div class="payment-row deposit">

                    <span>
                        Pay Now
                    </span>

                    <strong>
                        Rs. 2,500
                    </strong>

                </div>


                <p>
                    The remaining amount will be payable
                    at the VEYRO service centre.
                </p>


            </div>


            <div class="secure-box">

                <span class="secure-icon">
                    🔒
                </span>


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


    </form>

</main>