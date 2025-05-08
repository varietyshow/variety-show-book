<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
error_log("Schedule appointment script started");

session_start();
header('Content-Type: application/json');

try {
    // Log POST data
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));

    // Get POST data
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    // Validate required data
    if (!$start_date || !$end_date || !$start_time || !$end_time) {
        throw new Exception('Missing required fields');
    }

    // Check for entertainer_id
    if (!isset($_SESSION['entertainer_id'])) {
        throw new Exception('Entertainer ID not found in session');
    }
    $entertainer_id = $_SESSION['entertainer_id'];

    // Database connection
    $servername = "sql12.freesqldatabase.com";
    $username = "sql12777569";
    $password = "QlgHSeuU1n";
    $dbname = "sql12777569";

    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Convert dates to DateTime objects
        $startDateObj = new DateTime($start_date);
        $endDateObj = new DateTime($end_date);
        $currentDate = clone $startDateObj;

        $appointmentsScheduled = [];

        // Prepare insert statement
        $stmtInsert = $conn->prepare("INSERT INTO sched_time (entertainer_id, date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'Available')");
        
        if (!$stmtInsert) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        while ($currentDate <= $endDateObj) {
            $currentDateString = $currentDate->format('Y-m-d');
            
            // Check for existing appointments
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM sched_time WHERE entertainer_id = ? AND date = ? AND start_time = ?");
            if (!$checkStmt) {
                throw new Exception("Check prepare failed: " . $conn->error);
            }
            
            $checkStmt->bind_param("iss", $entertainer_id, $currentDateString, $start_time);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $count = $checkResult->fetch_row()[0];
            
            if ($count > 0) {
                throw new Exception("Schedule already exists for date: $currentDateString");
            }

            // Insert new schedule
            $stmtInsert->bind_param("isss", $entertainer_id, $currentDateString, $start_time, $end_time);
            
            if (!$stmtInsert->execute()) {
                throw new Exception("Insert failed: " . $stmtInsert->error);
            }

            $appointmentsScheduled[] = [
                'date' => $currentDateString,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];

            $currentDate->modify('+1 day');
            $checkStmt->close();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Schedule setup successfully',
            'appointments' => $appointmentsScheduled
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    } finally {
        if (isset($stmtInsert)) {
            $stmtInsert->close();
        }
        $conn->close();
    }

} catch (Exception $e) {
    error_log("Error in schedule appointment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
