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

    $_SESSION["redirect_after_login"] =
        "../booking/booking.php";

    header("Location: ../login/login-form.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| ERROR REDIRECT
|--------------------------------------------------------------------------
*/

function bookingError($message, $old = [])
{
    $_SESSION["booking_message"] = $message;

    $_SESSION["booking_message_type"] =
        "error";

    $_SESSION["booking_old"] =
        $old;

    header("Location: booking.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: booking-form.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| LOGGED-IN USER
|--------------------------------------------------------------------------
*/

$userId =
    (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$services =
    $_POST["services"] ?? [];

$services =
    array_map(
        "intval",
        (array) $services
    );

$services =
    array_values(
        array_unique(
            array_filter(
                $services,
                function ($id) {
                    return $id > 0;
                }
            )
        )
    );


$vehicleModel =
    trim(
        $_POST["vehicleModel"] ?? ""
    );


$licensePlate =
    trim(
        $_POST["licensePlate"] ?? ""
    );


$vehicleYear =
    $_POST["vehicleYear"] ?? null;


if ($vehicleYear === "") {
    $vehicleYear = null;
}


$vehicleType =
    trim(
        $_POST["vehicleType"] ?? ""
    );


$bookingDate =
    $_POST["bookingDate"] ?? "";


$timeSlot =
    (int) (
        $_POST["timeSlot"] ?? 0
    );


$notes =
    trim(
        $_POST["notes"] ?? ""
    );


$terms =
    $_POST["terms"] ?? "";


/*
|--------------------------------------------------------------------------
| OLD VALUES
|--------------------------------------------------------------------------
*/

$old = [

    "services" =>
        $services,

    "vehicleModel" =>
        $vehicleModel,

    "licensePlate" =>
        $licensePlate,

    "vehicleYear" =>
        $vehicleYear,

    "vehicleType" =>
        $vehicleType,

    "bookingDate" =>
        $bookingDate,

    "timeSlot" =>
        $timeSlot,

    "notes" =>
        $notes
];


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (empty($services)) {

    bookingError(
        "Please select at least one service.",
        $old
    );
}


if ($vehicleModel === "") {

    bookingError(
        "Please enter your vehicle make and model.",
        $old
    );
}


if ($licensePlate === "") {

    bookingError(
        "Please enter your registration number.",
        $old
    );
}


if ($bookingDate === "") {

    bookingError(
        "Please select a service date.",
        $old
    );
}


if ($bookingDate < date("Y-m-d")) {

    bookingError(
        "Please select a valid service date.",
        $old
    );
}


if ($timeSlot <= 0) {

    bookingError(
        "Please select a time slot.",
        $old
    );
}


if ($terms !== "1") {

    bookingError(
        "Please agree to the booking terms.",
        $old
    );
}


/*
|--------------------------------------------------------------------------
| GET TIME SLOT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        slot_name,
        start_time,
        end_time,
        max_bookings
    FROM time_slots
    WHERE id = ?
      AND status = 1
    LIMIT 1
");

$stmt->execute([
    $timeSlot
]);

$slot =
    $stmt->fetch();


if (!$slot) {

    bookingError(
        "The selected time slot is not available.",
        $old
    );
}


/*
|--------------------------------------------------------------------------
| CHECK SLOT CAPACITY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS booking_count
    FROM bookings
    WHERE booking_date = ?
      AND time_slot_id = ?
      AND status IN ('pending', 'confirmed')
");

$stmt->execute([
    $bookingDate,
    $timeSlot
]);

$bookingCount =
    (int) $stmt->fetchColumn();


if (
    $bookingCount
    >= (int) $slot["max_bookings"]
) {

    bookingError(
        "Sorry, this time slot is fully booked. Please select another time.",
        $old
    );
}


/*
|--------------------------------------------------------------------------
| GET SERVICES FROM DATABASE
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Never trust prices from JavaScript.
| We get the real prices from MySQL.
|
*/

$placeholders =
    implode(
        ",",
        array_fill(
            0,
            count($services),
            "?"
        )
    );


$sql = "
    SELECT
        id,
        service_name,
        price,
        duration_minutes
    FROM services
    WHERE id IN ($placeholders)
      AND status = 1
";


$stmt =
    $pdo->prepare($sql);


$stmt->execute(
    $services
);


$selectedServices =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| MAKE SURE ALL SERVICES ARE VALID
|--------------------------------------------------------------------------
*/

if (
    count($selectedServices)
    !== count($services)
) {

    bookingError(
        "One or more selected services are not available.",
        $old
    );
}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTALS
|--------------------------------------------------------------------------
*/

$totalPrice = 0;

$totalDuration = 0;


foreach (
    $selectedServices as $service
) {

    $totalPrice +=
        (float) $service["price"];

    $totalDuration +=
        (int) $service["duration_minutes"];

}


/*
|--------------------------------------------------------------------------
| DEPOSIT
|--------------------------------------------------------------------------
*/

$depositAmount =
    2500.00;


/*
|--------------------------------------------------------------------------
| CREATE BOOKING
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | INSERT BOOKING
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO bookings
        (
            user_id,
            vehicle_model,
            license_plate,
            vehicle_year,
            vehicle_type,
            booking_date,
            time_slot_id,
            notes,
            total_price,
            total_duration_minutes,
            deposit_amount,
            status,
            payment_status
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            'pending',
            'unpaid'
        )
    ");


    $stmt->execute([

        $userId,

        $vehicleModel,

        $licensePlate,

        $vehicleYear,

        $vehicleType,

        $bookingDate,

        $timeSlot,

        $notes,

        $totalPrice,

        $totalDuration,

        $depositAmount

    ]);


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING ID
    |--------------------------------------------------------------------------
    */

    $bookingId =
        $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | INSERT SERVICES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO booking_services
        (
            booking_id,
            service_id,
            service_price,
            service_duration_minutes
        )
        VALUES
        (?, ?, ?, ?)
    ");


    foreach (
        $selectedServices as $service
    ) {

        $stmt->execute([

            $bookingId,

            $service["id"],

            $service["price"],

            $service["duration_minutes"]

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | SAVE BOOKING ID
    |--------------------------------------------------------------------------
    */

    $_SESSION["booking_id"] =
        $bookingId;


    unset(
        $_SESSION["booking_old"]
    );


    /*
    |--------------------------------------------------------------------------
    | GO TO PAYMENT
    |--------------------------------------------------------------------------
    */

    header(
        "Location: payment.php"
    );

    exit();


} catch (PDOException $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW ERROR
    |--------------------------------------------------------------------------
    */

    bookingError(
        "Unable to create your booking. Please try again.",
        $old
    );
}