<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12777569";
$password = "QlgHSeuU1n";
$dbname = "sql12777569";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

try {
    // Get all roles
    $sql = "SELECT role_id, role_name FROM roles ORDER BY role_name";
    $result = $conn->query($sql);
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = [
            'role_id' => $row['role_id'],
            'role_name' => $row['role_name']
        ];
    }
    
    echo json_encode(['success' => true, 'roles' => $roles]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
