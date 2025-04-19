<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$selectedRoles = $input['roles'] ?? [];

if (empty($selectedRoles)) {
    echo json_encode(['error' => 'No roles selected']);
    exit;
}

// Convert selected roles to integers
$selectedRoles = array_map('intval', $selectedRoles);

// Get role names
$roleQuery = "SELECT role_id, role_name FROM roles WHERE role_id IN (" . implode(',', $selectedRoles) . ")";
$roleResult = $conn->query($roleQuery);
$roleNames = [];
while ($row = $roleResult->fetch_assoc()) {
    $roleNames[$row['role_id']] = $row['role_name'];
}

// Query to get packages with their roles
$query = "SELECT cp.*, GROUP_CONCAT(cpr.role_id) as role_combination,
          GROUP_CONCAT(r.role_name) as role_names
          FROM combo_packages cp
          INNER JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
          INNER JOIN roles r ON cpr.role_id = r.role_id
          GROUP BY cp.combo_id";

$result = $conn->query($query);

// Fetch packages
$packages = [];
$debug = ['selected_roles' => $selectedRoles];

while ($row = $result->fetch_assoc()) {
    $packageRoles = array_map('intval', explode(',', $row['role_combination']));
    $packageRoleNames = explode(',', $row['role_names']);
    
    $debug['packages'][] = [
        'id' => $row['combo_id'],
        'roles' => $packageRoles
    ];
    
    // Check if the package has exactly the same roles as selected
    $selectedRolesCount = count($selectedRoles);
    $packageRolesCount = count($packageRoles);
    
    // Sort both arrays to compare them properly
    sort($selectedRoles);
    sort($packageRoles);
    
    if ($selectedRolesCount === $packageRolesCount && $selectedRoles === $packageRoles) {
        $packages[] = [
            'id' => $row['combo_id'],
            'name' => $row['package_name'],
            'price' => $row['price'],
            'roles' => $packageRoles,
            'role_names' => $packageRoleNames // Include role names in the response
        ];
    }
}

// Return packages and debug information
echo json_encode([
    'packages' => $packages,
    'debug' => $debug
]);

$conn->close();
?>
