<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_booking_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['packageName']) || !isset($data['packagePrice']) || !isset($data['pairs']) || empty($data['pairs'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$packageName = htmlspecialchars($data['packageName']);
$packagePrice = floatval($data['packagePrice']);
$pairs = $data['pairs'];
$durations = isset($data['durations']) ? $data['durations'] : [];

// Start transaction
$conn->begin_transaction();

try {
    // Insert the new package
    $stmt = $conn->prepare("INSERT INTO combo_packages (package_name, price) VALUES (?, ?)");
    $stmt->bind_param("sd", $packageName, $packagePrice);
    $stmt->execute();
    
    $packageId = $conn->insert_id;
    
    // Insert package-role-entertainer relationships
    $roleStmt = $conn->prepare("INSERT INTO combo_package_roles (combo_id, role_id, entertainer_id) VALUES (?, ?, ?)");
    
    foreach ($pairs as $pair) {
        $roleId = intval($pair['roleId']);
        $entertainerId = intval($pair['entertainerId']);
        
        $roleStmt->bind_param("iii", $packageId, $roleId, $entertainerId);
        $roleStmt->execute();
    }

    // Insert package durations if any
    if (!empty($durations)) {
        $durationStmt = $conn->prepare("INSERT INTO package_durations (package_id, duration, duration_unit) VALUES (?, ?, ?)");
        
        foreach ($durations as $duration) {
            $durationValue = intval($duration['duration']);
            $durationUnit = htmlspecialchars($duration['durationUnit']);
            
            $durationStmt->bind_param("iis", $packageId, $durationValue, $durationUnit);
            $durationStmt->execute();
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Package and all details added successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
