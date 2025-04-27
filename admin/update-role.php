<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['roleId']) || !isset($data['roleName']) || !isset($data['rate']) || !isset($data['duration']) || !isset($data['durationUnit'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$roleId = intval($data['roleId']);
$roleName = htmlspecialchars($data['roleName']);
$rate = floatval($data['rate']);
$duration = intval($data['duration']);
$durationUnit = htmlspecialchars($data['durationUnit']);

try {
    // Update the role
    $stmt = $conn->prepare("UPDATE roles SET role_name = ?, rate = ?, duration = ?, duration_unit = ? WHERE role_id = ?");
    $stmt->bind_param("sdisi", $roleName, $rate, $duration, $durationUnit, $roleId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating role: ' . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
