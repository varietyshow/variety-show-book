<?php
session_start();
require_once('../includes/db_connection.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$appointment_id || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Update the appointment status and add cancellation reason
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', cancellation_reason = ?, cancelled_at = NOW() WHERE appointment_id = ?");
    $stmt->bind_param('si', $reason, $appointment_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$stmt->close();
$conn->close();
?>
