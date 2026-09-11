<?php
/**
 * This file expects the following variables to already be defined by the
 * script that includes it:
 *
 *   $pdo          -> a connected PDO instance
 *   $assistantId  -> the logged-in service assistant's ID
 *
 * No functionality below has been changed from the original — only
 * formatting, and a guard clause so this doesn't fatal-error if included
 * without those variables set.
 */

if (!isset($pdo) || !isset($assistantId)) {
    // Preserve original behavior intent: without these, there is nothing
    // to query. Fail safely instead of a raw PHP fatal error.
    $appointments = [];
    $search = '';
    $statusFilter = '';
    $dateFilter = '';
    $sort = 'date_asc';
} else {

    $appointments = [];

    /* --------------------------------------------------------------------
     | SEARCH & FILTER VALUES
     |-------------------------------------------------------------------- */
    $search       = trim($_GET['search'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');
    $dateFilter   = trim($_GET['date'] ?? '');
    $sort         = $_GET['sort'] ?? 'date_asc';

    /* --------------------------------------------------------------------
     | BUILD QUERY
     |-------------------------------------------------------------------- */
    try {
        $sql = "
            SELECT
                b.id,
                b.vehicle_model,
                b.license_plate,
                b.booking_date,
                b.total_price,
                b.status,
                u.name AS customer_name,
                ts.slot_name,
                ts.start_time,
                ts.end_time,
                GROUP_CONCAT(
                    DISTINCT s.service_name
                    ORDER BY s.service_name
                    SEPARATOR ', '
                ) AS services
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.user_id
            LEFT JOIN time_slots ts ON b.time_slot_id = ts.id
            LEFT JOIN booking_services bs ON b.id = bs.booking_id
            LEFT JOIN services s ON bs.service_id = s.id
            WHERE b.assigned_assistant_id = ?
        ";
        $params = [$assistantId];

        /* ----------------------------------------------------------------
         | SEARCH
         |---------------------------------------------------------------- */
        if ($search !== '') {
            $sql .= "
                AND (
                    CAST(b.id AS CHAR) LIKE ?
                    OR u.name LIKE ?
                    OR b.vehicle_model LIKE ?
                    OR b.license_plate LIKE ?
                    OR s.service_name LIKE ?
                )
            ";
            $searchValue = '%' . $search . '%';
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
        }

        /* ----------------------------------------------------------------
         | STATUS FILTER
         |---------------------------------------------------------------- */
        $allowedStatuses = [
            'confirmed',
            'service',
            'vehicle_arrived',
            'service_ongoing',
            'service_done',
            'vehicle_handover',
            'completed'
        ];

        if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
            $sql .= " AND b.status = ? ";
            $params[] = $statusFilter;
        }

        /* ----------------------------------------------------------------
         | DATE FILTER
         |---------------------------------------------------------------- */
        if ($dateFilter !== '') {
            $sql .= " AND b.booking_date = ? ";
            $params[] = $dateFilter;
        }

        /* ----------------------------------------------------------------
         | GROUP
         |---------------------------------------------------------------- */
        $sql .= "
            GROUP BY
                b.id,
                b.vehicle_model,
                b.license_plate,
                b.booking_date,
                b.total_price,
                b.status,
                u.name,
                ts.slot_name,
                ts.start_time,
                ts.end_time
        ";

        /* ----------------------------------------------------------------
         | SORT
         |---------------------------------------------------------------- */
        if ($sort === 'date_desc') {
            $sql .= " ORDER BY b.booking_date DESC, ts.start_time DESC ";
        } elseif ($sort === 'name_asc') {
            $sql .= " ORDER BY u.name ASC, b.booking_date ASC ";
        } elseif ($sort === 'name_desc') {
            $sql .= " ORDER BY u.name DESC, b.booking_date ASC ";
        } else {
            $sql .= " ORDER BY b.booking_date ASC, ts.start_time ASC ";
        }

        /* ----------------------------------------------------------------
         | EXECUTE
         |---------------------------------------------------------------- */
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $appointments = [];
    }
}

/* --------------------------------------------------------------------
 | STATUS ACTIONS
 |-------------------------------------------------------------------- */
$actions = [
    'confirmed' => [
        'next_status' => 'service',
        'button' => 'Start Service',
        'message' => 'Are you sure you want to start this service?'
    ],
    'service' => [
        'next_status' => 'vehicle_arrived',
        'button' => 'Vehicle Arrived',
        'message' => 'Confirm that the vehicle has arrived?'
    ],
    'vehicle_arrived' => [
        'next_status' => 'service_ongoing',
        'button' => 'Start Service Work',
        'message' => 'Are you sure you want to start the service work?'
    ],
    'service_ongoing' => [
        'next_status' => 'service_done',
        'button' => 'Service Done',
        'message' => 'Are you sure the service is finished?'
    ],
    'service_done' => [
        'next_status' => 'vehicle_handover',
        'button' => 'Hand Over Vehicle',
        'message' => 'Are you sure you want to hand over the vehicle?'
    ],
    'vehicle_handover' => [
        'next_status' => 'completed',
        'button' => 'Complete Appointment',
        'message' => 'Are you sure the vehicle handover is complete?'
    ]
];

// Recreate $allowedStatuses if it wasn't set (guard-clause branch above)
if (!isset($allowedStatuses)) {
    $allowedStatuses = [
        'confirmed',
        'service',
        'vehicle_arrived',
        'service_ongoing',
        'service_done',
        'vehicle_handover',
        'completed'
    ];
}
?>

<!-- =========================================================
     HEADER
========================================================= -->
<div class="panel-header">
    <div>
        <span class="section-label">APPOINTMENTS</span>
        <h2>My Appointments</h2>
    </div>
</div>

<!-- =========================================================
     SEARCH & FILTER PANEL
========================================================= -->
<form method="GET" class="appointments-filter-panel">

    <!-- Keep dashboard page -->
    <input type="hidden" name="page" value="appointments">

    <!-- SEARCH -->
    <div class="appointment-filter-group search-group">
        <label for="appointment-search">Search Appointments</label>
        <input
            type="text"
            id="appointment-search"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Booking ID, customer, vehicle, plate or service..."
        >
    </div>

    <!-- STATUS -->
    <div class="appointment-filter-group">
        <label for="appointment-status">Status</label>
        <select id="appointment-status" name="status">
            <option value="">All Statuses</option>
            <?php foreach ($allowedStatuses as $status): ?>
                <option
                    value="<?= htmlspecialchars($status) ?>"
                    <?= $statusFilter === $status ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- DATE -->
    <div class="appointment-filter-group">
        <label for="appointment-date">Date</label>
        <input
            type="date"
            id="appointment-date"
            name="date"
            value="<?= htmlspecialchars($dateFilter) ?>"
        >
    </div>

    <!-- SORT -->
    <div class="appointment-filter-group">
        <label for="appointment-sort">Sort By</label>
        <select id="appointment-sort" name="sort">
            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Customer A-Z</option>
            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Customer Z-A</option>
        </select>
    </div>

    <!-- BUTTONS -->
    <div class="appointment-filter-actions">
        <button type="submit" class="appointment-filter-btn">🔎 Search</button>
        <a href="?page=appointments" class="appointment-clear-btn">Clear</a>
    </div>

</form>

<!-- =========================================================
     RESULT INFORMATION
========================================================= -->
<div class="appointments-result-bar">
    <div>
        <?php if ($search !== ''): ?>
            <strong>Search:</strong> “<?= htmlspecialchars($search) ?>”
        <?php endif; ?>

        <?php if ($statusFilter !== ''): ?>
            <span class="result-filter-item">
                Status: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $statusFilter))) ?>
            </span>
        <?php endif; ?>

        <?php if ($dateFilter !== ''): ?>
            <span class="result-filter-item">
                Date: <?= htmlspecialchars($dateFilter) ?>
            </span>
        <?php endif; ?>
    </div>

    <strong>
        <?= count($appointments) ?> appointment<?= count($appointments) === 1 ? '' : 's' ?>
    </strong>
</div>

<!-- =========================================================
     APPOINTMENTS
========================================================= -->
<?php if (empty($appointments)): ?>

    <div class="empty-message">
        <h3>No Appointments Found</h3>

        <?php if ($search !== '' || $statusFilter !== '' || $dateFilter !== ''): ?>
            <p>No appointments match your search or filters.</p>
            <div style="margin-top:16px;">
                <a href="?page=appointments" class="appointment-clear-btn">Clear Filters</a>
            </div>
        <?php else: ?>
            <p>You have not confirmed any bookings yet.</p>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="my-bookings-list">
        <?php foreach ($appointments as $appointment): ?>

            <?php $status = strtolower(trim($appointment['status'] ?? '')); ?>

            <div class="booking-card">

                <!-- CARD HEADER -->
                <div class="booking-card-header">
                    <div>
                        <h3>Booking #<?= (int) $appointment['id'] ?></h3>
                        <p><?= htmlspecialchars($appointment['customer_name']) ?></p>
                    </div>
                    <span class="status">
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                    </span>
                </div>

                <!-- VEHICLE -->
                <p>
                    <strong>Vehicle:</strong>
                    <?= htmlspecialchars($appointment['vehicle_model']) ?> -
                    <?= htmlspecialchars($appointment['license_plate']) ?>
                </p>

                <!-- SERVICES -->
                <p>
                    <strong>Service:</strong>
                    <?= htmlspecialchars($appointment['services'] ?: 'Not available') ?>
                </p>

                <!-- DATE -->
                <p>
                    <strong>Date & Time:</strong>
                    <?= htmlspecialchars($appointment['booking_date']) ?> |
                    <?= htmlspecialchars($appointment['start_time'] ?: '') ?> -
                    <?= htmlspecialchars($appointment['end_time'] ?: '') ?>
                </p>

                <!-- ACTIONS -->
                <div class="booking-status-actions">

                    <!-- VIEW DETAILS -->
                    <a href="?page=details&id=<?= (int) $appointment['id'] ?>">View Details</a>

                    <!-- REPLACED PARTS -->
                    <?php if ($status === 'service_ongoing'): ?>
                        <a href="?page=replaced-parts&booking_id=<?= (int) $appointment['id'] ?>">🔧 Replaced Parts</a>
                    <?php endif; ?>

                    <!-- STATUS ACTION -->
                    <?php if (isset($actions[$status])): ?>

                        <form
                            method="POST"
                            action="./serviceAssistant/functions/update-booking-status.php"
                            style="display:inline;"
                            onsubmit="return confirmBookingAction(this);"
                        >
                            <input type="hidden" name="booking_id" value="<?= (int) $appointment['id'] ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($actions[$status]['next_status']) ?>">
                            <input type="hidden" name="confirmation_message" value="<?= htmlspecialchars($actions[$status]['message']) ?>">
                            <button type="submit"><?= htmlspecialchars($actions[$status]['button']) ?></button>
                        </form>

                    <?php elseif ($status === 'completed'): ?>

                        <span class="appointment-completed">✓ Completed</span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>