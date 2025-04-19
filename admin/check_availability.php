<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

session_start();
include("db_connect.php");

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['date']) || !isset($_POST['time_start']) || !isset($_POST['time_end']) || !isset($_POST['entertainers'])) {
        throw new Exception('Missing required parameters');
    }

    $date = $_POST['date'];
    $timeStart = $_POST['time_start'];
    $timeEnd = $_POST['time_end'];
    $entertainers = $_POST['entertainers'];

    // Validate inputs
    if (empty($date) || empty($timeStart) || empty($timeEnd) || empty($entertainers)) {
        throw new Exception('Empty required parameters');
    }

    $results = array();
    
    foreach ($entertainers as $entertainer_id) {
        // Get entertainer name
        $nameQuery = "SELECT CONCAT(first_name, ' ', last_name) as full_name 
                      FROM entertainer_account 
                      WHERE entertainer_id = ?";
        $nameStmt = $conn->prepare($nameQuery);
        $nameStmt->bind_param("i", $entertainer_id);
        $nameStmt->execute();
        $nameResult = $nameStmt->get_result();
        $entertainer = $nameResult->fetch_assoc();
        
        if (!$entertainer) {
            throw new Exception('Entertainer not found: ' . $entertainer_id);
        }
        
        $entertainerName = $entertainer['full_name'];
        
        // Check for existing bookings
        $query = "SELECT * 
                  FROM booking_report 
                  WHERE entertainer_name LIKE ? 
                  AND date_schedule = ? 
                  AND (
                      (? BETWEEN time_start AND time_end) OR
                      (? BETWEEN time_start AND time_end)
                  )
                  AND status = 'Approved'";
        
        $stmt = $conn->prepare($query);
        $searchPattern = '%' . $entertainerName . '%';
        $stmt->bind_param("ssss", 
            $searchPattern,
            $date,
            $timeStart,
            $timeEnd
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results[] = array(
            'id' => $entertainer_id,
            'name' => $entertainerName,
            'available' => $result->num_rows === 0
        );
    }
    
    // Clear any output buffers before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode(array('success' => true, 'data' => $results));

} catch (Exception $e) {
    // Clear any output buffers before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    error_log('Error in check_availability.php: ' . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>