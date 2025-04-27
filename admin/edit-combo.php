<?php
session_start();
$servername = "sql12.freesqldatabase.com"; // Change this to your database host
$username = "sql12775634";        // Change this to your database username
$password = "kPZFb8pXsU";            // Change this to your database password
$dbname = "sql12775634"; // Use your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle edit combo package request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_combo') {
    $packageId = intval($_POST['packageId']);
    $packageName = htmlspecialchars($_POST['packageName']);
    $comboPrice = htmlspecialchars($_POST['comboPrice']);
    $roles = $_POST['roles']; // This should be an array of role IDs

    // Update the combo package
    $stmt = $conn->prepare("UPDATE combo_packages SET package_name = ?, price = ? WHERE combo_id = ?");
    $stmt->bind_param("sdi", $packageName, $comboPrice, $packageId);
    
    if ($stmt->execute()) {
        // Update roles in combo_package_roles table
        // First, delete existing roles
        $conn->query("DELETE FROM combo_package_roles WHERE combo_id = $packageId");

        // Insert new roles
        foreach ($roles as $roleId) {
            $stmtRole = $conn->prepare("INSERT INTO combo_package_roles (combo_id, role_id) VALUES (?, ?)");
            $stmtRole->bind_param("ii", $packageId, $roleId);
            $stmtRole->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Combo package updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating combo package: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>
