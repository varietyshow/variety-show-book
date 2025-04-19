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

if (!isset($data['packageId'])) {
    echo json_encode(['success' => false, 'message' => 'Package ID is required']);
    exit();
}

$packageId = intval($data['packageId']);
$packageName = $data['packageName'];
$price = floatval($data['price']);
$roleEntertainers = $data['roleEntertainers'];
$duration = isset($data['duration']) ? intval($data['duration']) : null;
$durationUnit = isset($data['durationUnit']) ? $data['durationUnit'] : null;

// Start transaction
$conn->begin_transaction();

try {
    // Update package details
    $stmt = $conn->prepare("UPDATE combo_packages SET package_name = ?, price = ? WHERE combo_id = ?");
    $stmt->bind_param("sdi", $packageName, $price, $packageId);
    $stmt->execute();

    // Delete existing role-entertainer pairs
    $stmt = $conn->prepare("DELETE FROM combo_package_roles WHERE combo_id = ?");
    $stmt->bind_param("i", $packageId);
    $stmt->execute();

    // Insert new role-entertainer pairs
    $stmt = $conn->prepare("INSERT INTO combo_package_roles (combo_id, role_id, entertainer_id) VALUES (?, ?, ?)");
    foreach ($roleEntertainers as $pair) {
        $stmt->bind_param("iii", $packageId, $pair['roleId'], $pair['entertainerId']);
        $stmt->execute();
    }

    // Update duration if provided
    if ($duration !== null && $durationUnit !== null) {
        // Delete existing duration
        $stmt = $conn->prepare("DELETE FROM package_durations WHERE package_id = ?");
        $stmt->bind_param("i", $packageId);
        $stmt->execute();

        // Insert new duration
        $stmt = $conn->prepare("INSERT INTO package_durations (package_id, duration, duration_unit) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $packageId, $duration, $durationUnit);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Package updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
