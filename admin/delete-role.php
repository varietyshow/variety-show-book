<?php
header('Content-Type: application/json');

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
    $roleId = intval($_POST['role_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete associated packages
        $stmtPackages = $conn->prepare("DELETE FROM role_packages WHERE role_id = ?");
        $stmtPackages->bind_param("i", $roleId);
        $stmtPackages->execute();
        
        // Then delete the role
        $stmtRole = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
        $stmtRole->bind_param("i", $roleId);
        $stmtRole->execute();
        
        // Check if the role was actually deleted
        if ($stmtRole->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
        } else {
            throw new Exception('Role not found or already deleted');
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
