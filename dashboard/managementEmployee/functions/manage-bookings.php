<?php

/*
|--------------------------------------------------------------------------
| MANAGEMENT — BOOKING CONFIRMATION & ASSIGNMENT
|--------------------------------------------------------------------------
| Flow:
| payment accepted -> pending -> management confirms -> booked
|                              -> management assigns assistant -> confirmed
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$managementId = (int) ($_SESSION['user_id'] ?? 0);

if ($managementId <= 0 || (int) ($_SESSION['role_id'] ?? 0) !== 3) {
    echo '<div class="booking-error-message">You are not authorized to manage bookings.</div>';
    return;
}

if (empty($_SESSION['management_booking_csrf'])) {
    $_SESSION['management_booking_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken   = $_SESSION['management_booking_csrf'];
$message     = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = $_POST['csrf_token']     ?? '';
    $action      = $_POST['booking_action'] ?? '';
    $bookingId   = (int) ($_POST['booking_id'] ?? 0);

    if (!hash_equals($csrfToken, $postedToken) || $bookingId <= 0) {
        $message     = 'Invalid booking request.';
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            /* ------------------------------------------------------------
             * CONFIRM BOOKING
             * pending + accepted payment -> booked
             * ------------------------------------------------------------ */
            if ($action === 'confirm_booking') {

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET status = 'booked'
                    WHERE id = ?
                      AND status = 'pending'
                      AND assigned_assistant_id IS NULL
                      AND payment_status IN ('partial', 'paid')
                ");

                $stmt->execute([$bookingId]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception('This booking is no longer awaiting confirmation or its payment is not accepted.');
                }

                $message     = 'Booking confirmed. It is now ready for service assistant assignment.';
                $messageType = 'success';
            }

            /* ------------------------------------------------------------
             * ASSIGN SERVICE ASSISTANT
             * booked + unassigned -> confirmed + assigned
             * ------------------------------------------------------------ */
            elseif ($action === 'assign_assistant') {

                $assistantId = (int) ($_POST['assistant_id'] ?? 0);

                if ($assistantId <= 0) {
                    throw new Exception('Please select a service assistant.');
                }

                $assistantStmt = $pdo->prepare("
                    SELECT user_id, name
                    FROM users
                    WHERE user_id = ?
                      AND role_id = 2
                    LIMIT 1
                ");
                $assistantStmt->execute([$assistantId]);
                $assistant = $assistantStmt->fetch(PDO::FETCH_ASSOC);

                if (!$assistant) {
                    throw new Exception('Selected service assistant is not valid.');
                }

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET
                        assigned_assistant_id = ?,
                        status = 'confirmed'
                    WHERE id = ?
                      AND status = 'booked'
                      AND assigned_assistant_id IS NULL
                      AND payment_status IN ('partial', 'paid')
                ");

                $stmt->execute([$assistantId, $bookingId]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception('This booking has already been assigned or is no longer ready for assignment.');
                }

                $message     = 'Service assistant assigned successfully. The booking is now confirmed.';
                $messageType = 'success';
            }
            else {
                throw new Exception('Invalid booking action.');
            }

            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message     = $e->getMessage();
            $messageType = 'error';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD ASSISTANTS & BOOKINGS
|--------------------------------------------------------------------------
*/

$assistants        = [];
$pendingBookings   = [];
$confirmedBookings = [];

try {

    $stmt = $pdo->query("
        SELECT user_id, name
        FROM users
        WHERE role_id = 2
        ORDER BY name ASC
    ");
    $assistants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Payment must already be accepted before management can confirm. */
    $stmt = $pdo->query("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.vehicle_year,
            b.vehicle_type,
            b.booking_date,
            b.total_price,
            b.deposit_amount,
            b.payment_status,
            u.name  AS customer_name,
            u.phone AS customer_phone,
            ts.slot_name,
            ts.start_time,
            ts.end_time,
            GROUP_CONCAT(
                DISTINCT s.service_name
                ORDER BY s.service_name
                SEPARATOR ', '
            ) AS services
        FROM bookings b
        INNER JOIN users u       ON u.user_id = b.user_id
        LEFT JOIN time_slots ts  ON ts.id = b.time_slot_id
        LEFT JOIN booking_services bs ON bs.booking_id = b.id
        LEFT JOIN services s     ON s.id = bs.service_id
        WHERE b.status = 'pending'
          AND b.assigned_assistant_id IS NULL
          AND b.payment_status IN ('partial', 'paid')
        GROUP BY
            b.id, b.vehicle_model, b.license_plate, b.vehicle_year,
            b.vehicle_type, b.booking_date, b.total_price, b.deposit_amount,
            b.payment_status, u.name, u.phone, ts.slot_name, ts.start_time, ts.end_time
        ORDER BY b.booking_date ASC, ts.start_time ASC, b.id ASC
    ");
    $pendingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Confirmed by management but still waiting for assistant assignment. */
    $stmt = $pdo->query("
        SELECT
            b.id,
            b.vehicle_model,
            b.license_plate,
            b.booking_date,
            b.total_price,
            b.payment_status,
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
        INNER JOIN users u       ON u.user_id = b.user_id
        LEFT JOIN time_slots ts  ON ts.id = b.time_slot_id
        LEFT JOIN booking_services bs ON bs.booking_id = b.id
        LEFT JOIN services s     ON s.id = bs.service_id
        WHERE b.status = 'booked'
          AND b.assigned_assistant_id IS NULL
          AND b.payment_status IN ('partial', 'paid')
        GROUP BY
            b.id, b.vehicle_model, b.license_plate, b.booking_date,
            b.total_price, b.payment_status, u.name, ts.slot_name,
            ts.start_time, ts.end_time
        ORDER BY b.booking_date ASC, ts.start_time ASC, b.id ASC
    ");
    $confirmedBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message     = 'Unable to load booking management data.';
    $messageType = 'error';
}

?>

<div class="panel-header booking-management-header">
    <div>
        <span class="section-label">BOOKING CONTROL</span>
        <h2>Confirm &amp; Assign Bookings</h2>
        <p class="booking-management-subtitle">
            Payment accepted bookings are confirmed here before a service assistant is assigned.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="booking-flow-guide">
    <div class="booking-flow-item is-done">
        <span>1</span>
        <div>
            <strong>Payment Accepted</strong>
            <small>Deposit/payment verified</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item is-active">
        <span>2</span>
        <div>
            <strong>Confirm Booking</strong>
            <small>Management approves the appointment</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item">
        <span>3</span>
        <div>
            <strong>Assign Assistant</strong>
            <small>Select the responsible assistant</small>
        </div>
    </div>

    <div class="booking-flow-arrow">→</div>

    <div class="booking-flow-item">
        <span>4</span>
        <div>
            <strong>Confirmed</strong>
            <small>Assistant can begin the service flow</small>
        </div>
    </div>
</div>

<section class="booking-management-section">
    <div class="booking-management-section-header">
        <div>
            <span class="section-label">STEP 2</span>
            <h3>Bookings Awaiting Confirmation</h3>
        </div>
        <span class="booking-count-badge"><?= count($pendingBookings) ?></span>
    </div>

    <?php if (empty($pendingBookings)): ?>

        <div class="booking-management-empty">
            <div class="booking-management-empty-icon">✓</div>
            <h4>No bookings awaiting confirmation</h4>
            <p>Bookings appear here after their payment has been accepted.</p>
        </div>

    <?php else: ?>

        <div class="booking-management-grid">
            <?php foreach ($pendingBookings as $booking): ?>
                <article class="booking-management-card">

                    <div class="booking-management-card-top">
                        <div>
                            <span class="booking-number">BOOKING #<?= (int) $booking['id'] ?></span>
                            <h4><?= htmlspecialchars($booking['customer_name']) ?></h4>
                        </div>
                        <span class="status status-pending">Pending Confirmation</span>
                    </div>

                    <div class="booking-management-details">
                        <div>
                            <span>Vehicle</span>
                            <strong><?= htmlspecialchars($booking['vehicle_model']) ?></strong>
                            <small><?= htmlspecialchars($booking['license_plate']) ?></small>
                        </div>
                        <div>
                            <span>Service</span>
                            <strong><?= htmlspecialchars($booking['services'] ?: 'Not available') ?></strong>
                        </div>
                        <div>
                            <span>Date &amp; Time</span>
                            <strong><?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></strong>
                            <small><?= htmlspecialchars($booking['slot_name'] ?: 'Time not available') ?></small>
                        </div>
                        <div>
                            <span>Payment</span>
                            <strong>Rs. <?= number_format((float) $booking['total_price'], 2) ?></strong>
                            <small><?= htmlspecialchars($booking['payment_status'] === 'paid' ? 'Paid' : 'Deposit accepted') ?></small>
                        </div>
                    </div>

                    <div class="booking-management-actions">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <input type="hidden" name="booking_action" value="confirm_booking">
                            <button
                                type="submit"
                                class="booking-management-primary"
                                onclick="return confirm('Confirm this booking?');"
                            >
                                Confirm Booking
                            </button>
                        </form>
                        <a
                            class="booking-management-secondary"
                            href="?page=operation-details&amp;booking_id=<?= (int) $booking['id'] ?>"
                        >
                            View Details
                        </a>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</section>

<section class="booking-management-section booking-assignment-section">
    <div class="booking-management-section-header">
        <div>
            <span class="section-label">STEP 3</span>
            <h3>Confirmed — Awaiting Assistant Assignment</h3>
        </div>
        <span class="booking-count-badge"><?= count($confirmedBookings) ?></span>
    </div>

    <?php if (empty($confirmedBookings)): ?>

        <div class="booking-management-empty compact">
            <div class="booking-management-empty-icon">👷</div>
            <h4>No bookings awaiting assignment</h4>
            <p>Confirm a paid booking above and it will appear here.</p>
        </div>

    <?php else: ?>

        <div class="booking-management-grid">
            <?php foreach ($confirmedBookings as $booking): ?>
                <article class="booking-management-card assignment-card">

                    <div class="booking-management-card-top">
                        <div>
                            <span class="booking-number">BOOKING #<?= (int) $booking['id'] ?></span>
                            <h4><?= htmlspecialchars($booking['customer_name']) ?></h4>
                        </div>
                        <span class="status status-confirmed">Booking Confirmed</span>
                    </div>

                    <div class="booking-management-details">
                        <div>
                            <span>Vehicle</span>
                            <strong><?= htmlspecialchars($booking['vehicle_model']) ?></strong>
                            <small><?= htmlspecialchars($booking['license_plate']) ?></small>
                        </div>
                        <div>
                            <span>Service</span>
                            <strong><?= htmlspecialchars($booking['services'] ?: 'Not available') ?></strong>
                        </div>
                        <div>
                            <span>Date &amp; Time</span>
                            <strong><?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></strong>
                            <small><?= htmlspecialchars($booking['slot_name'] ?: 'Time not available') ?></small>
                        </div>
                    </div>

                    <form method="post" class="booking-assignment-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                        <input type="hidden" name="booking_action" value="assign_assistant">

                        <label for="assistant_<?= (int) $booking['id'] ?>">Service Assistant</label>

                        <div class="booking-assignment-controls">
                            <select id="assistant_<?= (int) $booking['id'] ?>" name="assistant_id" required>
                                <option value="">Select assistant</option>
                                <?php foreach ($assistants as $assistant): ?>
                                    <option value="<?= (int) $assistant['user_id'] ?>">
                                        <?= htmlspecialchars($assistant['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                type="submit"
                                class="booking-management-primary"
                                onclick="return confirm('Assign this service assistant and move the booking to Confirmed?');"
                            >
                                Assign &amp; Confirm
                            </button>
                        </div>
                    </form>

                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</section>