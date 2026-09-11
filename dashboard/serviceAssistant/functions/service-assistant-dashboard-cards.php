<?php

$todayAppointments = 0;
$assignedAppointments = 0;
$availableBookings = 0;
$completedAppointments = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE assigned_assistant_id = ? AND booking_date = CURDATE() AND status IN ('confirmed', 'service')");
    $stmt->execute([$assistantId]);
    $todayAppointments = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE assigned_assistant_id = ? AND status IN ('confirmed', 'service')");
    $stmt->execute([$assistantId]);
    $assignedAppointments = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE assigned_assistant_id IS NULL AND status = 'booked'");
    $availableBookings = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE assigned_assistant_id = ? AND status = 'completed'");
    $stmt->execute([$assistantId]);
    $completedAppointments = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $todayAppointments = $assignedAppointments = $availableBookings = $completedAppointments = 0;
}

$dashboardCards = [
    ['icon' => '📅', 'label' => "Today's Schedule", 'value' => $todayAppointments],
    ['icon' => '📋', 'label' => 'My Appointments', 'value' => $assignedAppointments],
    ['icon' => '⏳', 'label' => 'Awaiting Assignment', 'value' => $availableBookings],
    ['icon' => '✓', 'label' => 'Completed', 'value' => $completedAppointments],
];

include __DIR__ . '/../../includes/dashboard-cards.php';
?>
