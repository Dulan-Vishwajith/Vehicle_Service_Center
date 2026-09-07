<?php


session_start();


require_once __DIR__
    . '/../../../config/database.php';


$assistantId =
    (int) ($_SESSION['user_id'] ?? 0);


$bookingId =
    (int) ($_POST['booking_id'] ?? 0);


$newStatus =
    strtolower(
        trim(
            $_POST['status'] ?? ''
        )
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


try {


    /*
    |--------------------------------------------------------------------------
    | START SERVICE
    |--------------------------------------------------------------------------
    */


    if ($newStatus === 'service') {


        $stmt = $pdo->prepare("

            UPDATE bookings

            SET status = 'service'

            WHERE id = ?

            AND assigned_assistant_id = ?

            AND status = 'confirmed'

        ");


    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETE SERVICE
    |--------------------------------------------------------------------------
    */


    elseif ($newStatus === 'completed') {


        $stmt = $pdo->prepare("

            UPDATE bookings

            SET status = 'completed'

            WHERE id = ?

            AND assigned_assistant_id = ?

            AND status = 'service'

        ");


    }


    /*
    |--------------------------------------------------------------------------
    | INVALID STATUS
    |--------------------------------------------------------------------------
    */


    else {


        header(

            'Location: ../../dashboard.php?page=details&id='
            . $bookingId
            . '&error=invalid_booking'

        );


        exit;


    }


    /*
    |--------------------------------------------------------------------------
    | EXECUTE UPDATE
    |--------------------------------------------------------------------------
    */


    $stmt->execute([

        $bookingId,

        $assistantId

    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK RESULT
    |--------------------------------------------------------------------------
    */


    if ($stmt->rowCount() === 1) {


        header(

            'Location: ../../dashboard.php?page=details&id='
            . $bookingId

        );


    } else {


        header(

            'Location: ../../dashboard.php?page=details&id='
            . $bookingId
            . '&error=update'

        );


    }


    exit;


} catch (PDOException $e) {


    header(

        'Location: ../../dashboard.php?page=details&id='
        . $bookingId
        . '&error=update'

    );


    exit;


}