<?php
// Ensure no errors are output to the response
error_reporting(0);
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_booking_system";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    $roleId = isset($_GET['roleId']) ? intval($_GET['roleId']) : 0;

    if ($roleId <= 0) {
        throw new Exception('Invalid role ID');
    }

    // Get the role name first
    $roleQuery = "SELECT role_name FROM roles WHERE role_id = ?";
    $roleStmt = $conn->prepare($roleQuery);
    if (!$roleStmt) {
        throw new Exception('Failed to prepare role statement: ' . $conn->error);
    }

    $roleStmt->bind_param("i", $roleId);
    if (!$roleStmt->execute()) {
        throw new Exception('Failed to execute role query: ' . $roleStmt->error);
    }

    $roleResult = $roleStmt->get_result();
    $roleRow = $roleResult->fetch_assoc();
    
    if (!$roleRow) {
        throw new Exception('Role not found');
    }

    $roleName = $roleRow['role_name'];

    // Get entertainers that have this role
    $query = "SELECT entertainer_id, first_name, last_name, title 
              FROM entertainer_account 
              WHERE FIND_IN_SET(?, roles) > 0 
              AND status = 'Active'
              ORDER BY first_name, last_name";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("s", $roleName);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $entertainers = [];

    while ($row = $result->fetch_assoc()) {
        $entertainers[] = [
            'id' => $row['entertainer_id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['title'] . ')'
        ];
    }

    echo json_encode([
        'success' => true, 
        'entertainers' => $entertainers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
