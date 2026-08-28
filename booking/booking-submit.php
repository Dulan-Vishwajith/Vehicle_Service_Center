<?php
session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Only accept POST requests
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: booking_form.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get form values
|--------------------------------------------------------------------------
*/
$fullName      = trim($_POST["fullName"] ?? "");
$phone         = trim($_POST["phone"] ?? "");
$email         = trim($_POST["email"] ?? "");
$vehicleModel  = trim($_POST["vehicleModel"] ?? "");
$licensePlate  = trim($_POST["licensePlate"] ?? "");
$vehicleYear   = trim($_POST["vehicleYear"] ?? "");
$vehicleType   = trim($_POST["vehicleType"] ?? "");
$bookingDate   = trim($_POST["bookingDate"] ?? "");
$timeSlot      = trim($_POST["timeSlot"] ?? "");
$notes         = trim($_POST["notes"] ?? "");
$terms         = $_POST["terms"] ?? "";

/*
|--------------------------------------------------------------------------
| Keep form values if validation fails
|--------------------------------------------------------------------------
*/
$_SESSION["booking_old"] = [
    "fullName"     => $fullName,
    "phone"        => $phone,
    "email"        => $email,
    "vehicleModel" => $vehicleModel,
    "licensePlate" => $licensePlate,
    "vehicleYear"  => $vehicleYear,
    "vehicleType"  => $vehicleType,
    "bookingDate"  => $bookingDate,
    "timeSlot"     => $timeSlot,
    "notes"        => $notes
];

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/
$errors = [];

if ($fullName === "") {
    $errors[] = "Full name is required.";
} elseif (strlen($fullName) > 100) {
    $errors[] = "Full name is too long.";
}

if ($phone === "") {
    $errors[] = "Contact number is required.";
} elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors[] = "Please enter a valid contact number.";
}

if ($email === "") {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($vehicleModel === "") {
    $errors[] = "Vehicle make and model is required.";
}

if ($licensePlate === "") {
    $errors[] = "Registration number is required.";
}

$allowedVehicleTypes = ["", "Car", "SUV", "Van", "Pickup", "Motorcycle"];

if (!in_array($vehicleType, $allowedVehicleTypes, true)) {
    $errors[] = "Invalid vehicle type.";
}

if ($vehicleYear !== "") {
    $year = (int)$vehicleYear;

    if ($year < 2010 || $year > (int)date("Y")) {
        $errors[] = "Invalid vehicle year.";
    }
}

if ($bookingDate === "") {
    $errors[] = "Service date is required.";
} else {
    $date = DateTime::createFromFormat("Y-m-d", $bookingDate);

    if (!$date || $date->format("Y-m-d") !== $bookingDate) {
        $errors[] = "Invalid service date.";
    } elseif ($bookingDate < date("Y-m-d")) {
        $errors[] = "Service date cannot be in the past.";
    }
}

$allowedTimeSlots = [
    "08:00-10:00",
    "10:00-12:00",
    "13:00-15:00",
    "15:00-17:00"
];

if (!in_array($timeSlot, $allowedTimeSlots, true)) {
    $errors[] = "Please select a valid time slot.";
}

if ($terms !== "1") {
    $errors[] = "You must agree to the booking terms.";
}

if (strlen($notes) > 1000) {
    $errors[] = "Additional notes are too long.";
}

/*
|--------------------------------------------------------------------------
| Return validation errors
|--------------------------------------------------------------------------
*/
if (!empty($errors)) {
    $_SESSION["booking_message"] = implode(" ", $errors);
    $_SESSION["booking_message_type"] = "error";

    header("Location: booking_form.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Booking values
|--------------------------------------------------------------------------
*/
$serviceName = "Standard Care";
$servicePrice = 11500.00;
$depositAmount = 2500.00;

$userId = $_SESSION["user_id"] ?? null;

/*
|--------------------------------------------------------------------------
| Insert booking
|--------------------------------------------------------------------------
|
| Expected table:
|
| bookings
| - id
| - user_id
| - full_name
| - phone
| - email
| - vehicle_model
| - license_plate
| - vehicle_year
| - vehicle_type
| - service_name
| - booking_date
| - time_slot
| - notes
| - service_price
| - deposit_amount
| - status
| - created_at
|
*/
try {
    /*
     * Change this query only if your bookings table
     * uses different column names.
     */
    $sql = "INSERT INTO bookings
            (
                user_id,
                full_name,
                phone,
                email,
                vehicle_model,
                license_plate,
                vehicle_year,
                vehicle_type,
                service_name,
                booking_date,
                time_slot,
                notes,
                service_price,
                deposit_amount,
                status
            )
            VALUES
            (
                :user_id,
                :full_name,
                :phone,
                :email,
                :vehicle_model,
                :license_plate,
                :vehicle_year,
                :vehicle_type,
                :service_name,
                :booking_date,
                :time_slot,
                :notes,
                :service_price,
                :deposit_amount,
                'Pending Payment'
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id"        => $userId,
        ":full_name"      => $fullName,
        ":phone"          => $phone,
        ":email"          => $email,
        ":vehicle_model"  => $vehicleModel,
        ":license_plate"  => strtoupper($licensePlate),
        ":vehicle_year"   => $vehicleYear !== "" ? (int)$vehicleYear : null,
        ":vehicle_type"   => $vehicleType !== "" ? $vehicleType : null,
        ":service_name"   => $serviceName,
        ":booking_date"   => $bookingDate,
        ":time_slot"      => $timeSlot,
        ":notes"          => $notes !== "" ? $notes : null,
        ":service_price"  => $servicePrice,
        ":deposit_amount" => $depositAmount
    ]);

    $bookingId = $pdo->lastInsertId();

    unset($_SESSION["booking_old"]);

    /*
     * Send the user to your payment page.
     * Change payment.php if your payment page has another name.
     */
    header("Location: payment.php?booking_id=" . urlencode($bookingId));
    exit;

} catch (PDOException $e) {

    error_log("VEYRO booking error: " . $e->getMessage());

    $_SESSION["booking_message"] =
        "Unable to save your booking right now. Please try again.";

    $_SESSION["booking_message_type"] = "error";

    header("Location: booking_form.php");
    exit;
}
