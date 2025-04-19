<?php
session_start();
header('Content-Type: application/json');
error_reporting(0); // Disable error reporting for this file

function sendJsonResponse($success, $available, $error = '') {
    echo json_encode([
        'success' => $success,
        'available' => $available,
        'error' => $error
    ]);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_booking_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    sendJsonResponse(false, false, 'Connection failed');
}

if (!isset($_POST['date']) || !isset($_POST['start_time']) || !isset($_POST['end_time']) || !isset($_POST['entertainer_name']) || !isset($_POST['appointment_id'])) {
    sendJsonResponse(false, false, 'Missing required parameters');
}

$date = $_POST['date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$entertainer_names = explode(', ', $_POST['entertainer_name']); // Split multiple entertainer names
$appointment_id = intval($_POST['appointment_id']);

try {
    // Check availability for each entertainer
    foreach ($entertainer_names as $entertainer_name) {
        // First get the entertainer_id and title
        $id_sql = "SELECT entertainer_id, CONCAT(first_name, ' ', last_name, ' (', title, ')') as full_name_with_title 
                   FROM entertainer_account 
                   WHERE CONCAT(first_name, ' ', last_name) = ?";
        $id_stmt = $conn->prepare($id_sql);
        if (!$id_stmt) {
            throw new Exception("Failed to prepare entertainer query");
        }
        $id_stmt->bind_param("s", $entertainer_name);
        $id_stmt->execute();
        $id_result = $id_stmt->get_result();
        
        if ($id_result->num_rows === 0) {
            sendJsonResponse(false, false, "Entertainer '{$entertainer_name}' not found in the system");
        }
        
        $entertainer = $id_result->fetch_assoc();
        $entertainer_id = $entertainer['entertainer_id'];
        $entertainer_full_name = $entertainer['full_name_with_title'];
        
        // Check if the entertainer has set this date as Available
        $sched_sql = "SELECT status FROM sched_time WHERE entertainer_id = ? AND date = ?";
        $sched_stmt = $conn->prepare($sched_sql);
        if (!$sched_stmt) {
            throw new Exception("Failed to prepare schedule query");
        }
        $sched_stmt->bind_param("is", $entertainer_id, $date);
        $sched_stmt->execute();
        $sched_result = $sched_stmt->get_result();
        
        if ($sched_result->num_rows === 0) {
            sendJsonResponse(true, false, 
                $entertainer_full_name . ' has not set any schedule for ' . date('l, F j, Y', strtotime($date))
            );
        }
        
        $schedule = $sched_result->fetch_assoc();
        if ($schedule['status'] !== 'Available') {
            sendJsonResponse(true, false, 
                $entertainer_full_name . '\'s schedule is ' . $schedule['status'] . ' for ' . date('l, F j, Y', strtotime($date))
            );
        }

        // Check for existing approved appointments for this entertainer on this date
        $check_existing_sql = "SELECT br.*, CONCAT(ea.first_name, ' ', ea.last_name, ' (', ea.title, ')') as entertainer_full_name 
                              FROM booking_report br
                              JOIN entertainer_account ea ON br.entertainer_id = ea.entertainer_id
                              WHERE br.entertainer_id = ? 
                              AND br.date_schedule = ? 
                              AND br.status = 'Approved'
                              AND br.book_id != ?
                              AND ((br.time_start <= ? AND br.time_end > ?) 
                                   OR (br.time_start < ? AND br.time_end >= ?)
                                   OR (br.time_start >= ? AND br.time_end <= ?))";

        $check_stmt = $conn->prepare($check_existing_sql);
        if (!$check_stmt) {
            throw new Exception("Failed to prepare existing appointments query");
        }
        $check_stmt->bind_param("iississss", 
            $entertainer_id, 
            $date,
            $appointment_id,
            $end_time, $start_time,    // Case 1: Existing appointment overlaps start
            $end_time, $end_time,      // Case 2: Existing appointment overlaps end
            $start_time, $end_time     // Case 3: New appointment completely overlaps existing
        );
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();

        if ($existing_result->num_rows > 0) {
            $existing_appointment = $existing_result->fetch_assoc();
            sendJsonResponse(true, false, 
                $existing_appointment['entertainer_full_name'] . ' already has an approved booking on ' . date('l, F j, Y', strtotime($date)) . 
                ' from ' . date('h:i A', strtotime($existing_appointment['time_start'])) . 
                ' to ' . date('h:i A', strtotime($existing_appointment['time_end']))
            );
        }
        
        // Close statements for this entertainer
        $id_stmt->close();
        $sched_stmt->close();
        $check_stmt->close();
    }

    // If we get here, all entertainers are available
    sendJsonResponse(true, true, '');

} catch (Exception $e) {
    sendJsonResponse(false, false, 'An error occurred while checking availability. Please try again.');
} finally {
    if (isset($conn)) $conn->close();
}