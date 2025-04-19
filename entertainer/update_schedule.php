<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['entertainer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['scheduleId']) || !isset($data['date']) || !isset($data['startTime']) || !isset($data['endTime'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_booking_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Convert 12-hour format to 24-hour format if needed
function formatTime($time) {
    // If time is in 12-hour format (e.g., "8:00 PM"), convert to 24-hour format
    if (strpos($time, 'AM') !== false || strpos($time, 'PM') !== false) {
        return date('H:i:s', strtotime($time));
    }
    // If time is already in 24-hour format, just add seconds
    return date('H:i:s', strtotime($time));
}

// Sanitize and prepare the data
$scheduleId = intval($data['scheduleId']);
$date = $conn->real_escape_string($data['date']);
$startTime = formatTime($data['startTime']);
$endTime = formatTime($data['endTime']);

// Verify that the schedule belongs to the logged-in entertainer
$stmt = $conn->prepare("SELECT entertainer_id FROM sched_time WHERE sched_id = ? AND entertainer_id = ?");
$stmt->bind_param("ii", $scheduleId, $_SESSION['entertainer_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    $stmt->close();
    $conn->close();
    exit();
}

// Update the schedule
$updateStmt = $conn->prepare("UPDATE sched_time SET date = ?, start_time = ?, end_time = ? WHERE sched_id = ? AND entertainer_id = ?");
$updateStmt->bind_param("sssii", $date, $startTime, $endTime, $scheduleId, $_SESSION['entertainer_id']);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}

$updateStmt->close();
$conn->close();
?>