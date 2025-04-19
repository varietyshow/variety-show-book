<?php
// Enable error logging to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Prevent errors from being displayed in the output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Ensure clean output buffer
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    require_once 'db_connect.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    error_log("Received data: " . json_encode($data));

    // Validate input
    if (!isset($data['entertainers']) || !is_array($data['entertainers']) || 
        !isset($data['date']) || !isset($data['start_time']) || !isset($data['end_time'])) {
        throw new Exception('Missing required fields: ' . json_encode($data));
    }

    $entertainers = $data['entertainers'];
    $booking_date = $data['date'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];

    $results = [];

    foreach ($entertainers as $entertainer_id) {
        // Get entertainer name
        $name_sql = "SELECT CONCAT(first_name, ' ', last_name) as name FROM entertainer_account WHERE entertainer_id = ?";
        $name_stmt = $conn->prepare($name_sql);
        
        if (!$name_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $name_stmt->bind_param("i", $entertainer_id);
        
        if (!$name_stmt->execute()) {
            throw new Exception("Execute failed: " . $name_stmt->error);
        }

        $result = $name_stmt->get_result();
        $entertainer = $result->fetch_assoc();

        if (!$entertainer) {
            throw new Exception("Entertainer not found: ID " . $entertainer_id);
        }

        // First check if the date is in the entertainer's schedule and is available
        $sched_sql = "SELECT status FROM sched_time 
                     WHERE entertainer_id = ? 
                     AND date = ?";

        $sched_stmt = $conn->prepare($sched_sql);
        if (!$sched_stmt) {
            throw new Exception("Prepare failed for schedule check: " . $conn->error);
        }

        $sched_stmt->bind_param("is", $entertainer_id, $booking_date);
        
        if (!$sched_stmt->execute()) {
            throw new Exception("Execute failed for schedule check: " . $sched_stmt->error);
        }

        $sched_result = $sched_stmt->get_result();
        $schedule = $sched_result->fetch_assoc();

        // If no schedule found or status is not 'Available', entertainer is not available
        if (!$schedule || $schedule['status'] !== 'Available') {
            $results[] = [
                'entertainer_id' => $entertainer_id,
                'name' => $entertainer['name'],
                'available' => false,
                'reason' => !$schedule ? 'Date not in schedule' : 'Date marked as ' . $schedule['status']
            ];
            continue;
        }

        // If the date is available in schedule, check for any existing approved bookings
        $booking_sql = "SELECT time_start, time_end 
                       FROM booking_report 
                       WHERE entertainer_id = ?
                       AND date_schedule = ?
                       AND status = 'Approved'
                       AND (
                           (time_start <= ? AND time_end > ?) OR
                           (time_start < ? AND time_end >= ?) OR
                           (? <= time_start AND ? >= time_end)
                       )";

        error_log("Checking bookings for entertainer " . $entertainer_id . " on " . $booking_date . " between " . $start_time . " and " . $end_time);

        $booking_stmt = $conn->prepare($booking_sql);
        if (!$booking_stmt) {
            throw new Exception("Prepare failed for booking check: " . $conn->error);
        }

        $booking_stmt->bind_param("isssssss", 
            $entertainer_id, 
            $booking_date,
            $end_time, $start_time,      // Case 1: New booking ends during existing
            $end_time, $end_time,        // Case 2: New booking starts during existing
            $start_time, $end_time       // Case 3: New booking completely contains existing
        );

        if (!$booking_stmt->execute()) {
            throw new Exception("Execute failed for booking check: " . $booking_stmt->error);
        }

        $booking_result = $booking_stmt->get_result();
        $conflict = $booking_result->fetch_assoc();

        $results[] = [
            'entertainer_id' => $entertainer_id,
            'name' => $entertainer['name'],
            'available' => !$conflict,
            'conflict' => $conflict ? [
                'start_time' => $conflict['time_start'],
                'end_time' => $conflict['time_end']
            ] : null
        ];

        error_log("Result for entertainer " . $entertainer_id . ": " . json_encode($results[count($results) - 1]));
    }

    echo json_encode($results);
    exit;

} catch (Exception $e) {
    error_log("Error in check_availability.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
    exit;
}
