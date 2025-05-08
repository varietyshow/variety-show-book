<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12777569";
$password = "QlgHSeuU1n";
$dbname = "sql12777569";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // Get and validate input
    if (!isset($_POST['roleId'], $_POST['roleName'], $_POST['rate'], $_POST['duration'], $_POST['durationUnit'])) {
        throw new Exception("Missing required fields");
    }

    $roleId = intval($_POST['roleId']);
    $roleName = $conn->real_escape_string($_POST['roleName']);
    $rate = floatval($_POST['rate']);
    $duration = intval($_POST['duration']);
    $durationUnit = $conn->real_escape_string($_POST['durationUnit']);

    // Update role
    $stmt = $conn->prepare("UPDATE roles SET role_name = ?, rate = ?, duration = ?, duration_unit = ? WHERE role_id = ?");
    $stmt->bind_param("sdisi", $roleName, $rate, $duration, $durationUnit, $roleId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating role: " . $stmt->error);
    }

    // Delete existing packages for this role
    $stmt = $conn->prepare("DELETE FROM role_packages WHERE role_id = ?");
    $stmt->bind_param("i", $roleId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting existing packages: " . $stmt->error);
    }

    // Insert new packages
    if (isset($_POST['packageNames']) && isset($_POST['packagePrices']) && 
        isset($_POST['packageDurations']) && isset($_POST['packageDurationUnits'])) {
        
        $stmt = $conn->prepare("INSERT INTO role_packages (role_id, package_name, package_price, package_duration, package_duration_unit) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($_POST['packageNames']); $i++) {
            if (!empty($_POST['packageNames'][$i]) && isset($_POST['packagePrices'][$i])) {
                $packageName = $conn->real_escape_string($_POST['packageNames'][$i]);
                $packagePrice = floatval($_POST['packagePrices'][$i]);
                $packageDuration = intval($_POST['packageDurations'][$i]);
                $packageDurationUnit = $conn->real_escape_string($_POST['packageDurationUnits'][$i]);
                
                $stmt->bind_param("isdis", $roleId, $packageName, $packagePrice, $packageDuration, $packageDurationUnit);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting package: " . $stmt->error);
                }
            }
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Role updated successfully']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
