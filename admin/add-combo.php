<?php
session_start();

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get form data
        $packageName = htmlspecialchars($_POST['packageName']);
        $comboPrice = floatval($_POST['comboPrice']);
        $roles = $_POST['roles']; // Array of role IDs

        // Insert into combo_packages table
        $stmt = $conn->prepare("INSERT INTO combo_packages (package_name, price, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("sd", $packageName, $comboPrice);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting combo package: " . $stmt->error);
        }

        $comboId = $conn->insert_id;

        // Insert role associations
        $stmtRoles = $conn->prepare("INSERT INTO combo_package_roles (combo_id, role_id) VALUES (?, ?)");
        
        foreach ($roles as $roleId) {
            if (!empty($roleId)) {
                $stmtRoles->bind_param("ii", $comboId, $roleId);
                if (!$stmtRoles->execute()) {
                    throw new Exception("Error inserting role association: " . $stmtRoles->error);
                }
            }
        }

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Combo package added successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    // Close statements and connection
    if (isset($stmt)) $stmt->close();
    if (isset($stmtRoles)) $stmtRoles->close();
    $conn->close();
    exit;
}

// If not POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
