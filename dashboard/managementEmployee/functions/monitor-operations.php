<?php

/*
|--------------------------------------------------------------------------
| MONITOR OPERATIONS
|--------------------------------------------------------------------------
*/

$ongoing = [];
$completed = [];
$assistantActivity = [];
$operationsError = '';

try {

    /*
    |--------------------------------------------------------------------------
    | ONGOING BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT
            b.id,
            u.name AS customer_name,
            b.vehicle_model,
            b.license_plate,
            GROUP_CONCAT(
                s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS service_names,
            a.name AS assistant_name,
            b.booking_date,
            ts.start_time,
            ts.end_time,
            b.status
        FROM bookings b

        INNER JOIN users u
            ON u.user_id = b.user_id

        LEFT JOIN users a
            ON a.user_id = b.assigned_assistant_id

        LEFT JOIN time_slots ts
            ON ts.id = b.time_slot_id

        LEFT JOIN booking_services bs
            ON bs.booking_id = b.id

        LEFT JOIN services s
            ON s.id = bs.service_id

        WHERE b.status IN (
            'pending',
            'booked',
            'confirmed',
            'service',
            'vehicle_arrived',
            'service_ongoing',
            'service_done',
            'vehicle_handover'
        )

        GROUP BY
            b.id,
            u.name,
            b.vehicle_model,
            b.license_plate,
            a.name,
            b.booking_date,
            ts.start_time,
            ts.end_time,
            b.status

        ORDER BY
            b.booking_date ASC,
            ts.start_time ASC

        LIMIT 15
    ");

    $ongoing = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | COMPLETED BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT
            b.id,
            u.name AS customer_name,
            b.vehicle_model,
            b.license_plate,
            GROUP_CONCAT(
                s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS service_names,
            a.name AS assistant_name,
            b.updated_at AS completed_date,
            b.total_price
        FROM bookings b

        INNER JOIN users u
            ON u.user_id = b.user_id

        LEFT JOIN users a
            ON a.user_id = b.assigned_assistant_id

        LEFT JOIN booking_services bs
            ON bs.booking_id = b.id

        LEFT JOIN services s
            ON s.id = bs.service_id

        WHERE b.status = 'completed'

        GROUP BY
            b.id,
            u.name,
            b.vehicle_model,
            b.license_plate,
            a.name,
            b.updated_at,
            b.total_price

        ORDER BY
            b.updated_at DESC

        LIMIT 10
    ");

    $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | ACTIVE SERVICE ASSISTANTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT
            u.user_id,
            u.name,

            SUM(
                CASE
                    WHEN b.status IN (
                        'pending',
                        'booked',
                        'confirmed',
                        'vehicle_arrived'
                    )
                    THEN 1
                    ELSE 0
                END
            ) AS current_assignments,

            SUM(
                CASE
                    WHEN b.status IN (
                        'service',
                        'service_ongoing'
                    )
                    THEN 1
                    ELSE 0
                END
            ) AS in_progress,

            SUM(
                CASE
                    WHEN b.status = 'completed'
                    AND DATE(b.updated_at) = CURDATE()
                    THEN 1
                    ELSE 0
                END
            ) AS completed_today

        FROM users u

        LEFT JOIN bookings b
            ON b.assigned_assistant_id = u.user_id

        WHERE u.role_id = 2

        GROUP BY
            u.user_id,
            u.name

        ORDER BY
            u.name ASC
    ");

    $assistantActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $operationsError = "Unable to load operations data: " . $e->getMessage();
}


/*
|--------------------------------------------------------------------------
| STATUS BADGE HELPER
|--------------------------------------------------------------------------
*/

function operationsStatusClass($status)
{
    switch (strtolower($status)) {

        case 'pending':
            return 'status-pending';

        case 'booked':
        case 'confirmed':
            return 'status-confirmed';

        case 'vehicle_arrived':
            return 'status-arrived';

        case 'service':
        case 'service_ongoing':
            return 'status-progress';

        case 'service_done':
            return 'status-service-done';

        case 'vehicle_handover':
            return 'status-handover';

        case 'completed':
            return 'status-completed';

        case 'cancelled':
            return 'status-cancelled';

        default:
            return 'status-pending';
    }
}

?>


<div class="panel-header">
    <h2>Monitor Operations</h2>
</div>


<?php if (!empty($operationsError)): ?>

    <div class="ops-error-message">
        <?= htmlspecialchars($operationsError) ?>
    </div>

<?php endif; ?>

<!-- ===================================================
     ONGOING BOOKINGS
=================================================== -->

<div class="ops-section">

    <h3 class="ops-section-title">Ongoing Bookings</h3>

    <?php if (empty($ongoing)): ?>

        <div class="empty-message">
            <p>No ongoing bookings right now.</p>
        </div>

    <?php else: ?>

        <div class="ops-table monitor-table">

            <div class="ops-table-heading">
                <span class="ops-col-id">Booking</span>
                <span class="ops-col-customer">Customer</span>
                <span class="ops-col-vehicle">Vehicle</span>
                <span class="ops-col-service">Service</span>
                <span class="ops-col-assistant">Assistant</span>
                <span class="ops-col-date">Start Date</span>
                <span class="ops-col-status">Status</span>
                <span class="ops-col-action"></span>
            </div>

            <?php foreach ($ongoing as $row): ?>

                <div class="ops-table-row">

                    <span class="ops-col-id">#<?= (int) $row['id'] ?></span>

                    <span class="ops-col-customer"><?= htmlspecialchars($row['customer_name']) ?></span>

                    <span class="ops-col-vehicle">
                        <?= htmlspecialchars($row['vehicle_model']) ?>
                        <small><?= htmlspecialchars($row['license_plate']) ?></small>
                    </span>

                    <span class="ops-col-service"><?= htmlspecialchars($row['service_names'] ?? '—') ?></span>

                    <span class="ops-col-assistant"><?= htmlspecialchars($row['assistant_name'] ?? 'Unassigned') ?></span>

                    <span class="ops-col-date">
                        <?= htmlspecialchars(date('d M Y', strtotime($row['booking_date']))) ?>
                        <?php if ($row['start_time']): ?>
                            <small><?= htmlspecialchars(date('h:i A', strtotime($row['start_time']))) ?></small>
                        <?php endif; ?>
                    </span>

                    <span class="ops-col-status">
                        <span class="status <?= operationsStatusClass($row['status']) ?>">
                            <?= htmlspecialchars(ucfirst($row['status'])) ?>
                        </span>
                    </span>

                    <span class="ops-col-action">
                        <a href="?page=operation-details&booking_id=<?= (int) $row['id'] ?>" class="ops-view-link">
                            View
                        </a>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>


<!-- ===================================================
     COMPLETED BOOKINGS
=================================================== -->

<div class="ops-section">

    <h3 class="ops-section-title">Completed Bookings</h3>

    <?php if (empty($completed)): ?>

        <div class="empty-message">
            <p>No completed bookings yet.</p>
        </div>

    <?php else: ?>

        <div class="ops-table monitor-table">

            <div class="ops-table-heading">
                <span class="ops-col-id">Booking</span>
                <span class="ops-col-customer">Customer</span>
                <span class="ops-col-vehicle">Vehicle</span>
                <span class="ops-col-service">Service</span>
                <span class="ops-col-assistant">Assistant</span>
                <span class="ops-col-date">Completed</span>
                <span class="ops-col-total">Total</span>
                <span class="ops-col-action"></span>
            </div>

            <?php foreach ($completed as $row): ?>

                <div class="ops-table-row">

                    <span class="ops-col-id">#<?= (int) $row['id'] ?></span>

                    <span class="ops-col-customer"><?= htmlspecialchars($row['customer_name']) ?></span>

                    <span class="ops-col-vehicle">
                        <?= htmlspecialchars($row['vehicle_model']) ?>
                        <small><?= htmlspecialchars($row['license_plate']) ?></small>
                    </span>

                    <span class="ops-col-service"><?= htmlspecialchars($row['service_names'] ?? '—') ?></span>

                    <span class="ops-col-assistant"><?= htmlspecialchars($row['assistant_name'] ?? '—') ?></span>

                    <span class="ops-col-date"><?= htmlspecialchars(date('d M Y', strtotime($row['completed_date']))) ?></span>

                    <span class="ops-col-total">Rs. <?= number_format((float) $row['total_price'], 2) ?></span>

                    <span class="ops-col-action">
                        <a href="?page=operation-details&booking_id=<?= (int) $row['id'] ?>" class="ops-view-link">
                            View
                        </a>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>


<!-- ===================================================
     ACTIVE SERVICE ASSISTANTS
=================================================== -->

<div class="ops-section">

    <h3 class="ops-section-title">Active Service Assistants</h3>

    <?php if (empty($assistantActivity)): ?>

        <div class="empty-message">
            <p>No active service assistants found.</p>
        </div>

    <?php else: ?>

        <div class="ops-table ops-table-compact">

            <div class="ops-table-heading">
                <span class="ops-col-customer">Assistant</span>
                <span class="ops-col-status">Current Assignments</span>
                <span class="ops-col-status">In Progress</span>
                <span class="ops-col-status">Completed Today</span>
            </div>

            <?php foreach ($assistantActivity as $row): ?>

                <div class="ops-table-row">

                    <span class="ops-col-customer"><?= htmlspecialchars($row['name']) ?></span>

                    <span class="ops-col-status"><?= (int) $row['current_assignments'] ?></span>

                    <span class="ops-col-status"><?= (int) $row['in_progress'] ?></span>

                    <span class="ops-col-status"><?= (int) $row['completed_today'] ?></span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>