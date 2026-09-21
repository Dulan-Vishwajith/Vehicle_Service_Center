<?php

session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../includes/notifications.php';


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
| ready_to_handover
|     ↓
| completed
|
*/

$statusFlow = [

    'service' => 'confirmed',

    'vehicle_arrived' => 'service',

    'service_ongoing' => 'vehicle_arrived',

    'service_done' => 'service_ongoing',

    'ready_to_handover' => 'service_done'

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
    | GET CUSTOMER
    |--------------------------------------------------------------------------
    |
    | We need the customer user_id so the notification is sent
    | only to the customer who owns this booking.
    |
    */

    $customerStmt = $pdo->prepare("
        SELECT user_id
        FROM bookings
        WHERE id = ?
        AND assigned_assistant_id = ?
        LIMIT 1
    ");

    $customerStmt->execute([

        $bookingId,

        $assistantId

    ]);

    $customerId = (int) $customerStmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | GET MANAGEMENT EMPLOYEES
    |--------------------------------------------------------------------------
    | Management employees have role_id = 3.
    | They should receive a notification when a vehicle is
    | ready for handover and the remaining payment needs confirmation.
    */

    $managementIds = [];

    if ($newStatus === 'ready_to_handover') {

        $managementStmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE role_id = 3
        ");

        $managementStmt->execute();

        $managementIds = $managementStmt->fetchAll(PDO::FETCH_COLUMN);
    }


    if ($customerId <= 0) {

        header(
            'Location: ../../dashboard.php?page=appointments'
            . '&error=booking_not_found'
        );

        exit;

    }



    /*
    |--------------------------------------------------------------------------
    | CUSTOMER NOTIFICATION CONTENT
    |--------------------------------------------------------------------------
    */

    $notificationTitle = '';
    $notificationMessage = '';
    $notificationType = 'booking_status';


    switch ($newStatus) {

        case 'service':

            $notificationTitle =
                'Service Started';

            $notificationMessage =
                'The service for your Booking #'
                . $bookingId
                . ' has now started.';

            $notificationType =
                'service';

            break;


        case 'vehicle_arrived':

            $notificationTitle =
                'Vehicle Arrived';

            $notificationMessage =
                'Your vehicle for Booking #'
                . $bookingId
                . ' has been marked as arrived at VEYRO Service Center.';

            $notificationType =
                'booking_status';

            break;


        case 'service_ongoing':

            $notificationTitle =
                'Service In Progress';

            $notificationMessage =
                'The service work for your Booking #'
                . $bookingId
                . ' is currently in progress.';

            $notificationType =
                'service';

            break;


        case 'service_done':

            $notificationTitle =
                'Service Completed';

            $notificationMessage =
                'The service work for your Booking #'
                . $bookingId
                . ' has been completed. Your vehicle is being prepared for handover.';

            $notificationType =
                'service';

            break;


        case 'ready_to_handover':

            $notificationTitle =
                'Vehicle Ready for Handover';

            $notificationMessage =
                'Your vehicle for Booking #'
                . $bookingId
                . ' is ready for handover.';

            $notificationType =
                'booking_status';

            break;


        case 'completed':

            $notificationTitle =
                'Booking Completed';

            $notificationMessage =
                'Your Booking #'
                . $bookingId
                . ' has been completed successfully. Thank you for choosing VEYRO.';

            $notificationType =
                'booking_status';

            break;

    }





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

        /*
        |--------------------------------------------------------------------------
        | CREATE CUSTOMER NOTIFICATION
        |--------------------------------------------------------------------------
        |
        | The notification is created only after the booking status
        | was successfully changed.
        |
        */

        if (
            $customerId > 0
            && !empty($notificationTitle)
            && !empty($notificationMessage)
        ) {

            createNotification(
                $pdo,
                $customerId,
                $bookingId,
                $notificationType,
                $notificationTitle,
                $notificationMessage
            );

        }


        /*
        |--------------------------------------------------------------------------
        | MANAGEMENT EMPLOYEE NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (
            $newStatus === 'ready_to_handover'
            && !empty($managementIds)
        ) {

            foreach ($managementIds as $managementId) {

                createNotification(
                    $pdo,
                    (int) $managementId,
                    $bookingId,
                    'payment',
                    'Vehicle Ready for Handover',
                    'Booking #'
                        . $bookingId
                        . ' is ready for handover. Please confirm the remaining payment.'
                );

            }

        }



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