<?php

/*
|--------------------------------------------------------------------------
| VEYRO NOTIFICATION SYSTEM
|--------------------------------------------------------------------------
|
| Common notification functions for:
|
| 1 = Customer
| 2 = Service Assistant
| 3 = Management
|
| This file is responsible for:
|
| - Creating notifications
| - Creating notifications for a role
| - Getting user notifications
| - Getting unread notification count
| - Displaying notifications
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Dashboard pages normally already have $pdo.
| This fallback allows this file to also be used directly from
| notification/action files.
|
*/

if (!isset($pdo)) {

    require_once __DIR__ . '/../../config/database.php';

}


/*
|--------------------------------------------------------------------------
| CREATE SINGLE USER NOTIFICATION
|--------------------------------------------------------------------------
|
| Creates a notification for one specific user.
|
| Example:
|
| createNotification(
|     $pdo,
|     $customerId,
|     25,
|     'booking_status',
|     'Booking Status Updated',
|     'Your vehicle service has started.'
| );
|
*/

if (!function_exists('createNotification')) {

    function createNotification(
        PDO $pdo,
        int $userId,
        ?int $bookingId,
        string $type,
        string $title,
        string $message
    ): bool {

        if ($userId <= 0) {
            return false;
        }


        try {

            $stmt = $pdo->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    booking_id,
                    type,
                    title,
                    message
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            return $stmt->execute([
                $userId,
                $bookingId,
                $type,
                $title,
                $message
            ]);

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Notification failure should NOT break the main system.
            |--------------------------------------------------------------------------
            |
            | For example, if a notification insert fails, the booking status
            | should still be allowed to complete.
            |
            */

            return false;

        }

    }

}


/*
|--------------------------------------------------------------------------
| CREATE NOTIFICATION FOR ALL USERS WITH A SPECIFIC ROLE
|--------------------------------------------------------------------------
|
| This is useful for management notifications.
|
| Example:
|
| notifyRole(
|     $pdo,
|     3,
|     25,
|     'payment_pending',
|     'Payment Requires Verification',
|     'A new payment is waiting for verification.'
| );
|
*/

if (!function_exists('notifyRole')) {

    function notifyRole(
        PDO $pdo,
        int $roleId,
        ?int $bookingId,
        string $type,
        string $title,
        string $message
    ): int {

        if ($roleId <= 0) {
            return 0;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Get all active users belonging to this role
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE role_id = ?
            ");

            $stmt->execute([
                $roleId
            ]);


            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);


            $createdCount = 0;


            /*
            |--------------------------------------------------------------------------
            | Create notification for every user
            |--------------------------------------------------------------------------
            */

            foreach ($users as $userId) {

                if (
                    createNotification(
                        $pdo,
                        (int) $userId,
                        $bookingId,
                        $type,
                        $title,
                        $message
                    )
                ) {

                    $createdCount++;

                }

            }


            return $createdCount;

        } catch (PDOException $e) {

            return 0;

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET USER NOTIFICATIONS
|--------------------------------------------------------------------------
|
| Gets the latest notifications for the currently logged-in user.
|
*/

if (!function_exists('getUserNotifications')) {

    function getUserNotifications(
        PDO $pdo,
        int $userId,
        int $limit = 10
    ): array {

        if ($userId <= 0) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent invalid LIMIT values
        |--------------------------------------------------------------------------
        */

        $limit = max(1, min($limit, 50));


        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    booking_id,
                    type,
                    title,
                    message,
                    is_read,
                    created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT $limit
            ");


            $stmt->execute([
                $userId
            ]);


            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            return [];

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET UNREAD NOTIFICATION COUNT
|--------------------------------------------------------------------------
|
| Used for:
|
| Notification bell
| Notification badge
| Dashboard notification count
|
*/

if (!function_exists('getUnreadNotificationCount')) {

    function getUnreadNotificationCount(
        PDO $pdo,
        int $userId
    ): int {

        if ($userId <= 0) {
            return 0;
        }


        try {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM notifications
                WHERE user_id = ?
                AND is_read = 0
            ");


            $stmt->execute([
                $userId
            ]);


            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {

            return 0;

        }

    }

}


/*
|--------------------------------------------------------------------------
| FORMAT NOTIFICATION TIME
|--------------------------------------------------------------------------
|
| Converts database timestamp into a readable format.
|
*/

if (!function_exists('formatNotificationTime')) {

    function formatNotificationTime(
        string $createdAt
    ): string {

        if (empty($createdAt)) {
            return '';
        }


        $timestamp = strtotime($createdAt);


        if (!$timestamp) {
            return '';
        }


        return date(
            'd M Y, g:i A',
            $timestamp
        );

    }

}



/*
|--------------------------------------------------------------------------
| MARK NOTIFICATION AS READ
|--------------------------------------------------------------------------
|
| Marks one notification as read for the specified user.
|
*/

if (!function_exists('markNotificationAsRead')) {

    function markNotificationAsRead(
        PDO $pdo,
        int $notificationId,
        int $userId
    ): bool {

        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        try {

            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = ?
                  AND user_id = ?
                LIMIT 1
            ");

            return $stmt->execute([
                $notificationId,
                $userId
            ]);

        } catch (PDOException $e) {

            return false;
        }
    }

}

/*
|--------------------------------------------------------------------------
| GET NOTIFICATION TYPE ICON
|--------------------------------------------------------------------------
|
| The icon is selected based on notification type.
|
*/

if (!function_exists('getNotificationIcon')) {

    function getNotificationIcon(
        string $type
    ): string {

        switch ($type) {

            /*
            | Customer
            */

            case 'booking_status':
                return '📋';

            case 'payment':
                return '💳';

            case 'service':
                return '🔧';

            case 'replaced_parts':
                return '🔩';


            /*
            | Service Assistant
            */

            case 'appointment':
                return '📅';

            case 'appointment_assigned':
                return '👨‍🔧';

            case 'schedule':
                return '🗓️';


            /*
            | Management
            */

            case 'payment_pending':
                return '💰';

            case 'booking_pending':
                return '📋';

            case 'booking_assigned':
                return '👨‍🔧';


            /*
            | Default
            */

            default:
                return '🔔';

        }

    }

}


/*
|--------------------------------------------------------------------------
| DISPLAY NOTIFICATIONS
|--------------------------------------------------------------------------
|
| Common display function.
|
| All three dashboards will use this same function.
|
*/

if (!function_exists('renderNotifications')) {

    function renderNotifications(
        PDO $pdo,
        int $userId,
        int $limit = 10
    ): void {





    /*
    |--------------------------------------------------------------------------
    | PROCESS MARK AS READ REQUEST
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['mark_notification_read'])
        && isset($_POST['notification_id'])
    ) {

        $notificationId =
            (int) $_POST['notification_id'];

        markNotificationAsRead(
            $pdo,
            $notificationId,
            $userId
        );

    }




        $notifications =
            getUserNotifications(
                $pdo,
                $userId,
                $limit
            );


        $unreadCount =
            getUnreadNotificationCount(
                $pdo,
                $userId
            );

        ?>

        <section class="dashboard-panel notifications-panel">

            <div class="panel-header">

                <div>

                    <span class="section-label">
                        NOTIFICATIONS
                    </span>

                    <h2>
                        Notifications
                    </h2>

                </div>


                <?php if ($unreadCount > 0): ?>

                    <span class="notification-count">

                        <?= $unreadCount ?>

                    </span>

                <?php endif; ?>

            </div>


            <?php if (empty($notifications)): ?>

                <div class="empty-message">

                    <h3>
                        No Notifications
                    </h3>

                    <p>
                        You don't have any notifications yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="notifications-list">

                    <?php foreach ($notifications as $notification): ?>

                        <div
                            class="notification-item
                            <?= ((int) $notification['is_read'] === 0)
                                ? 'notification-unread'
                                : 'notification-read'
                            ?>"
                        >

                            <div class="notification-icon">

                                <?= htmlspecialchars(
                                    getNotificationIcon(
                                        $notification['type']
                                    )
                                ) ?>

                            </div>


                            <div class="notification-content">

                                <div class="notification-title-row">

                                    <h3>

                                        <?= htmlspecialchars(
                                            $notification['title']
                                        ) ?>

                                    </h3>


                                    <?php if (
                                        (int) $notification['is_read'] === 0
                                    ): ?>

                                        <span class="notification-new">
                                            NEW
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <p>

                                    <?= htmlspecialchars(
                                        $notification['message']
                                    ) ?>

                                </p>


                                <small>

                                    <?= htmlspecialchars(
                                        formatNotificationTime(
                                            $notification['created_at']
                                        )
                                    ) ?>

                                </small>


                                <?php if (
                                    !empty($notification['booking_id'])
                                ): ?>

                                    <div class="notification-booking">

                                        Booking #
                                        <?= (int) $notification['booking_id'] ?>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    (int) $notification['is_read'] === 0
                                ): ?>

                                    <form
                                        method="POST"
                                        action=""
                                        class="notification-read-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="mark_notification_read"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="notification_id"
                                            value="<?= (int) $notification['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="notification-read-button"
                                        >
                                            Mark as Read
                                        </button>

                                    </form>

                                <?php endif; ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <?php

    }

}

?>