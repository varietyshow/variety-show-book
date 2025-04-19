<?php
include 'db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$roleId = isset($data['roleId']) ? intval($data['roleId']) : 0;

if ($roleId <= 0) {
    echo json_encode(['error' => 'Invalid role ID']);
    exit;
}

try {
    // Get role packages for the selected role
    $stmt = $conn->prepare("
        SELECT 
            rp.package_id,
            rp.package_name,
            rp.package_price,
            rp.package_duration,
            rp.package_duration_unit,
            r.role_name
        FROM role_packages rp
        JOIN roles r ON r.role_id = rp.role_id
        WHERE rp.role_id = ?
    ");
    
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id' => $row['package_id'],
            'name' => $row['package_name'],
            'price' => $row['package_price'],
            'duration' => $row['package_duration'],
            'duration_unit' => $row['package_duration_unit'],
            'role_name' => $row['role_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'packages' => $packages
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Failed to fetch role packages',
        'debug' => $e->getMessage()
    ]);
}
?>
