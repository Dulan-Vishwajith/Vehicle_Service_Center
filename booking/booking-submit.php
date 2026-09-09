<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    $_SESSION['redirect_after_login'] = '../booking/booking.php';

    header('Location: ../login/login-form.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once '../config/database.php';


/*
|--------------------------------------------------------------------------
| ERROR HELPER
|--------------------------------------------------------------------------
*/

function bookingError($message, $old = [])
{
    $_SESSION['booking_message'] = $message;
    $_SESSION['booking_message_type'] = 'error';
    $_SESSION['booking_old'] = $old;

    header('Location: booking.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: booking.php');
    exit;
}


$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$services = $_POST['services'] ?? [];

$services = array_map('intval', (array) $services);

$services = array_values(
    array_unique(
        array_filter(
            $services,
            function ($id) {
                return $id > 0;
            }
        )
    )
);


$vehicleModel = trim($_POST['vehicleModel'] ?? '');

$licensePlate = trim($_POST['licensePlate'] ?? '');

$vehicleYear = $_POST['vehicleYear'] ?? null;

if ($vehicleYear === '') {
    $vehicleYear = null;
}

$vehicleType = trim($_POST['vehicleType'] ?? '');

$bookingDate = $_POST['bookingDate'] ?? '';

$timeSlot = (int) ($_POST['timeSlot'] ?? 0);

$notes = trim($_POST['notes'] ?? '');

$terms = $_POST['terms'] ?? '';


/*
|--------------------------------------------------------------------------
| OLD VALUES
|--------------------------------------------------------------------------
*/

$old = [

    'services' => $services,

    'vehicleModel' => $vehicleModel,

    'licensePlate' => $licensePlate,

    'vehicleYear' => $vehicleYear,

    'vehicleType' => $vehicleType,

    'bookingDate' => $bookingDate,

    'timeSlot' => $timeSlot,

    'notes' => $notes

];


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (empty($services)) {

    bookingError(
        'Please select at least one service.',
        $old
    );
}


if ($vehicleModel === '') {

    bookingError(
        'Please enter your vehicle make and model.',
        $old
    );
}


if (strlen($vehicleModel) > 100) {

    bookingError(
        'Vehicle model is too long.',
        $old
    );
}


if ($licensePlate === '') {

    bookingError(
        'Please enter your registration number.',
        $old
    );
}


if (strlen($licensePlate) > 20) {

    bookingError(
        'Registration number is too long.',
        $old
    );
}


if ($bookingDate === '') {

    bookingError(
        'Please select a service date.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| DATE VALIDATION
|--------------------------------------------------------------------------
*/

$dateObject = DateTime::createFromFormat(
    'Y-m-d',
    $bookingDate
);

if (
    !$dateObject
    || $dateObject->format('Y-m-d') !== $bookingDate
) {

    bookingError(
        'Please select a valid service date.',
        $old
    );
}


if ($bookingDate < date('Y-m-d')) {

    bookingError(
        'Please select a valid service date.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| TERMS
|--------------------------------------------------------------------------
*/

if ($terms !== '1') {

    bookingError(
        'Please agree to the booking terms.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| VEHICLE YEAR
|--------------------------------------------------------------------------
*/

if ($vehicleYear !== null) {

    $year = (int) $vehicleYear;

    $currentYear = (int) date('Y');

    if (
        $year < 1900
        || $year > $currentYear + 1
    ) {

        bookingError(
            'Please enter a valid vehicle year.',
            $old
        );
    }

    $vehicleYear = $year;
}


/*
|--------------------------------------------------------------------------
| TIME SLOT
|--------------------------------------------------------------------------
*/

if ($timeSlot <= 0) {

    bookingError(
        'Please select a time slot.',
        $old
    );
}


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

$stmt->execute([$timeSlot]);

$slot = $stmt->fetch();


if (!$slot) {

    bookingError(
        'The selected time slot is not available.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| CHECK CURRENT SLOT CAPACITY
|--------------------------------------------------------------------------
|
| Only already-created bookings are counted here.
| The final payment step checks capacity again.
|
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE booking_date = ?
      AND time_slot_id = ?
      AND status IN (
          'pending',
          'booked',
          'confirmed'
      )
");

$stmt->execute([
    $bookingDate,
    $timeSlot
]);

$bookingCount = (int) $stmt->fetchColumn();


if ($bookingCount >= (int) $slot['max_bookings']) {

    bookingError(
        'Sorry, this time slot is fully booked. Please select another time.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| GET REAL SERVICE PRICES
|--------------------------------------------------------------------------
*/

$placeholders = implode(
    ',',
    array_fill(
        0,
        count($services),
        '?'
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


$stmt = $pdo->prepare($sql);

$stmt->execute($services);

$selectedServices = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| ENSURE ALL SERVICES ARE VALID
|--------------------------------------------------------------------------
*/

if (
    count($selectedServices)
    !== count($services)
) {

    bookingError(
        'One or more selected services are not available.',
        $old
    );
}


/*
|--------------------------------------------------------------------------
| CALCULATE TRUSTED TOTALS
|--------------------------------------------------------------------------
*/

$totalPrice = 0.00;

$totalDuration = 0;


foreach ($selectedServices as $service) {

    $totalPrice += (float) $service['price'];

    $totalDuration += (int) $service['duration_minutes'];
}


/*
|--------------------------------------------------------------------------
| DEPOSIT
|--------------------------------------------------------------------------
|
| Existing project rule:
| Fixed booking deposit = Rs. 2,500
|
*/

$depositAmount = 2500.00;


/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| Do NOT create a booking here.
|
| Store validated booking information temporarily.
|
*/

$_SESSION['pending_booking'] = [

    'request_token' =>
        bin2hex(random_bytes(32)),

    'user_id' =>
        $userId,

    'vehicle_model' =>
        $vehicleModel,

    'license_plate' =>
        $licensePlate,

    'vehicle_year' =>
        $vehicleYear,

    'vehicle_type' =>
        $vehicleType,

    'booking_date' =>
        $bookingDate,

    'time_slot_id' =>
        $timeSlot,

    'time_slot_name' =>
        $slot['slot_name'],

    'start_time' =>
        $slot['start_time'],

    'end_time' =>
        $slot['end_time'],

    'notes' =>
        $notes,

    'selected_service_ids' =>
        array_map(
            'intval',
            $services
        ),

    'selected_services' =>
        $selectedServices,

    'total_price' =>
        $totalPrice,

    'total_duration_minutes' =>
        $totalDuration,

    'deposit_amount' =>
        $depositAmount,

    'created_at' =>
        time()

];


/*
|--------------------------------------------------------------------------
| CLEAR OLD BOOKING ERRORS
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['booking_old'],
    $_SESSION['booking_message'],
    $_SESSION['booking_message_type']
);


/*
|--------------------------------------------------------------------------
| GO TO PAYMENT
|--------------------------------------------------------------------------
*/

header('Location: payment.php');
exit;