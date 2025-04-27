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

if (!isset($data['comboId'])) {
    echo json_encode(['success' => false, 'message' => 'Package ID is required']);
    exit();
}

$comboId = intval($data['comboId']);

// Start transaction
$conn->begin_transaction();

try {
    // Delete from package_durations first (child table)
    $stmt = $conn->prepare("DELETE FROM package_durations WHERE package_id = ?");
    $stmt->bind_param("i", $comboId);
    $stmt->execute();

    // Delete from combo_package_roles (child table)
    $stmt = $conn->prepare("DELETE FROM combo_package_roles WHERE combo_id = ?");
    $stmt->bind_param("i", $comboId);
    $stmt->execute();

    // Finally delete from combo_packages (parent table)
    $stmt = $conn->prepare("DELETE FROM combo_packages WHERE combo_id = ?");
    $stmt->bind_param("i", $comboId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
