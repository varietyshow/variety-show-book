<?php
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['entertainer_ids'])) {
    echo json_encode(['error' => 'No entertainer IDs provided']);
    exit;
}

$entertainer_ids = explode(',', $_POST['entertainer_ids']);
$response = [];

foreach ($entertainer_ids as $id) {
    // Sanitize the ID
    $id = intval($id);
    
    // Get entertainer details
    $entertainer_query = "SELECT entertainer_id, title, first_name, last_name, roles 
                         FROM entertainer_account 
                         WHERE entertainer_id = ?";
    $stmt = $conn->prepare($entertainer_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $entertainer = [
            'entertainer_id' => $row['entertainer_id'],
            'full_name' => $row['title'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'],
            'roles' => []
        ];
        
        // Get roles with their details from roles table
        $roles = explode(',', $row['roles']);
        foreach ($roles as $role) {
            $role = trim($role);
            $role_query = "SELECT role_name, rate, duration, duration_unit FROM roles WHERE role_name = ?";
            $role_stmt = $conn->prepare($role_query);
            $role_stmt->bind_param("s", $role);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            
            if ($role_row = $role_result->fetch_assoc()) {
                $entertainer['roles'][] = [
                    'role_name' => $role_row['role_name'],
                    'rate' => $role_row['rate'],
                    'duration' => $role_row['duration'],
                    'duration_unit' => $role_row['duration_unit']
                ];
            }
        }
        
        $response[] = $entertainer;
    }
}

echo json_encode($response);
?>
