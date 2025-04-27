<?php
session_start();
header('Content-Type: application/json');

$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_role') {
    $conn->begin_transaction();
    
    try {
        // Insert the role
        $roleName = htmlspecialchars($_POST['roleName']);
        $rate = floatval($_POST['rate']);
        $duration = intval($_POST['duration']);
        $durationUnit = htmlspecialchars($_POST['durationUnit']);
        
        $stmtRole = $conn->prepare("INSERT INTO roles (role_name, rate, duration, duration_unit) VALUES (?, ?, ?, ?)");
        $stmtRole->bind_param("sdss", $roleName, $rate, $duration, $durationUnit);
        $stmtRole->execute();
        
        $roleId = $conn->insert_id;
        
        // Insert packages if provided
        if (isset($_POST['packageNames']) && isset($_POST['packagePrices'])) {
            $stmtPackage = $conn->prepare("INSERT INTO role_packages (role_id, package_name, package_price) VALUES (?, ?, ?)");
            
            for ($i = 0; $i < count($_POST['packageNames']); $i++) {
                if (!empty($_POST['packageNames'][$i]) && isset($_POST['packagePrices'][$i])) {
                    $packageName = htmlspecialchars($_POST['packageNames'][$i]);
                    $packagePrice = floatval($_POST['packagePrices'][$i]);
                    $stmtPackage->bind_param("isd", $roleId, $packageName, $packagePrice);
                    $stmtPackage->execute();
                }
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Role added successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
    exit;
}

// If we reach here, it means the request was not properly formatted
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>
