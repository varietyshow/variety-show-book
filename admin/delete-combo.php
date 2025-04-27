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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['combo_id'])) {
    $comboId = intval($_POST['combo_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete the role associations
        $stmtRoles = $conn->prepare("DELETE FROM combo_package_roles WHERE combo_id = ?");
        $stmtRoles->bind_param("i", $comboId);
        $stmtRoles->execute();
        
        // Then delete the combo package
        $stmtCombo = $conn->prepare("DELETE FROM combo_packages WHERE combo_id = ?");
        $stmtCombo->bind_param("i", $comboId);
        $stmtCombo->execute();
        
        // Check if the combo package was actually deleted
        if ($stmtCombo->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Combo package deleted successfully']);
        } else {
            throw new Exception('Combo package not found or already deleted');
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
