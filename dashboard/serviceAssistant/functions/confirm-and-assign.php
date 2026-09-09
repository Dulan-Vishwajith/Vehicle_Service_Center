<?php


session_start();


require_once __DIR__
    . '/../../../config/database.php';


$assistantId =
    (int) ($_SESSION['user_id'] ?? 0);


$bookingId =
    (int) ($_POST['booking_id'] ?? 0);


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
        'Location: ../../dashboard.php?page=available&error=invalid_booking'
    );


    exit;


}


/*
|--------------------------------------------------------------------------
| CONFIRM SERVICE ASSISTANT ROLE
|--------------------------------------------------------------------------
*/

try {


    $roleStmt = $pdo->prepare("

        SELECT user_id

        FROM users

        WHERE user_id = ?

        AND role_id = 2

        LIMIT 1

    ");


    $roleStmt->execute([

        $assistantId

    ]);


    $isServiceAssistant =
        $roleStmt->fetchColumn();


    if (!$isServiceAssistant) {


        header(
            'Location: ../../dashboard.php?page=available&error=unauthorized'
        );


        exit;


    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM AND ASSIGN
    |--------------------------------------------------------------------------
    |
    | This ensures that:
    |
    | 1. Booking is pending/booked
    | 2. Booking is not already assigned
    | 3. Current assistant receives the booking
    |
    */


    $stmt = $pdo->prepare("

        UPDATE bookings

        SET

            status = 'confirmed',

            assigned_assistant_id = ?

        WHERE id = ?

        AND assigned_assistant_id IS NULL

        AND status IN (

            'pending',

            'booked'

        )

        AND payment_status IN (
    
            'partial',
    
            'paid'

        )

    ");


    $stmt->execute([

        $assistantId,

        $bookingId

    ]);


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */


    if ($stmt->rowCount() === 1) {


        header(

            'Location: ../../dashboard.php?page=appointments'
            . '&success=booking_confirmed'

        );


    } else {


        header(

            'Location: ../../dashboard.php?page=available'
            . '&error=booking_unavailable'

        );


    }


    exit;


} catch (PDOException $e) {


    header(

        'Location: ../../dashboard.php?page=available'
        . '&error=update'

    );


    exit;


}