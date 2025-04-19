<?php
session_start();
require_once '../db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['entertainer_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in'
    ]);
    exit;
}

$entertainer_id = $_SESSION['entertainer_id'];
$schedule_type = $_POST['scheduleType'] ?? '';
$today = new DateTime(date('Y-m-d')); // Get today's date without time

try {
    // Start transaction
    $conn->begin_transaction();

    if ($schedule_type === 'bulk') {
        // Handle bulk schedule
        $start_date = $_POST['startDate'] ?? '';
        $end_date = $_POST['endDate'] ?? '';

        if (empty($start_date) || empty($end_date)) {
            throw new Exception('Start date and end date are required for bulk scheduling');
        }

        // Validate dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        // Check if dates are in the past
        if ($start < $today) {
            throw new Exception('Cannot schedule dates in the past');
        }

        if ($start > $end) {
            throw new Exception('End date must be after start date');
        }

        // Insert bulk schedule
        $stmt = $conn->prepare("INSERT INTO sched_time (entertainer_id, date, status) VALUES (?, ?, 'Available') 
                              ON DUPLICATE KEY UPDATE status = 'Available'");
        
        $current = clone $start;
        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $stmt->bind_param("is", $entertainer_id, $date);
            $stmt->execute();
            $current->modify('+1 day');
        }

    } elseif ($schedule_type === 'custom') {
        // Handle custom schedule
        $weekdays = isset($_POST['weekdays']) ? $_POST['weekdays'] : [];
        $schedule_month = $_POST['scheduleMonth'] ?? '';
        
        if (empty($weekdays)) {
            throw new Exception('At least one weekday must be selected for custom scheduling');
        }
        
        if (empty($schedule_month)) {
            throw new Exception('Month must be selected for custom scheduling');
        }

        // Parse the month and year
        list($year, $month) = explode('-', $schedule_month);
        
        // Create date range for the selected month
        $start = new DateTime("$year-$month-01");
        $end = clone $start;
        $end->modify('last day of this month');

        // Check if the entire month is in the past
        if ($end < $today) {
            throw new Exception('Cannot schedule dates in the past');
        }

        // If month starts in the past, adjust start date to today
        if ($start < $today) {
            $start = clone $today;
        }

        // Prepare the statement once outside the loop
        $stmt = $conn->prepare("INSERT INTO sched_time (entertainer_id, date, status) VALUES (?, ?, 'Available')
                              ON DUPLICATE KEY UPDATE status = 'Available'");
        
        $current = clone $start;
        $dates_processed = 0;
        
        while ($current <= $end) {
            $weekday = $current->format('w'); // 0 (Sunday) to 6 (Saturday)
            if (in_array($weekday, $weekdays)) {
                $date = $current->format('Y-m-d');
                $stmt->bind_param("is", $entertainer_id, $date);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert date: $date. Error: " . $stmt->error);
                }
                $dates_processed++;
            }
            $current->modify('+1 day');
        }
        
        if ($dates_processed === 0) {
            throw new Exception('No dates were processed. Please check your selection.');
        }
    } else {
        throw new Exception('Invalid schedule type');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Schedule saved successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log the error
    error_log("Schedule save error: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?>
