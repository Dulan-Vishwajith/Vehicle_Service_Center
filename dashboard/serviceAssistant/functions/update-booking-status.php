<?php

session_start();

require_once __DIR__ . '/../../../config/database.php';


$assistantId = (int) ($_SESSION['user_id'] ?? 0);

$bookingId = (int) ($_POST['booking_id'] ?? 0);

$newStatus = strtolower(
    trim($_POST['status'] ?? '')
);


/*
|--------------------------------------------------------------------------
| VALIDATE REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || $assistantId <= 0
    || $bookingId <= 0
) {

    header(
        'Location: ../../dashboard.php?page=appointments'
        . '&error=invalid_booking'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| STATUS FLOW
|--------------------------------------------------------------------------
|
| confirmed
|     ↓
| service
|     ↓
| vehicle_arrived
|     ↓
| service_ongoing
|     ↓
| service_done
|     ↓
| vehicle_handover
|     ↓
| completed
|
*/

$statusFlow = [

    'service' => 'confirmed',

    'vehicle_arrived' => 'service',

    'service_ongoing' => 'vehicle_arrived',

    'service_done' => 'service_ongoing',

    'vehicle_handover' => 'service_done',

    'completed' => 'vehicle_handover'

];


try {


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    if (!isset($statusFlow[$newStatus])) {

        header(
            'Location: ../../dashboard.php?page=appointments'
            . '&error=invalid_status'
        );

        exit;

    }


    $requiredCurrentStatus =
        $statusFlow[$newStatus];


    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING STATUS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("

        UPDATE bookings

        SET status = ?

        WHERE id = ?

        AND assigned_assistant_id = ?

        AND status = ?

    ");


    $stmt->execute([

        $newStatus,

        $bookingId,

        $assistantId,

        $requiredCurrentStatus

    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK RESULT
    |--------------------------------------------------------------------------
    */

    if ($stmt->rowCount() === 1) {

        header(
            'Location: ../../dashboard.php?page=appointments'
            . '&success=status_updated'
        );

    } else {

        header(
            'Location: ../../dashboard.php?page=appointments'
            . '&error=update'
        );

    }


    exit;


} catch (PDOException $e) {


    header(
        'Location: ../../dashboard.php?page=appointments'
        . '&error=update'
    );


    exit;

}