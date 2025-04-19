<?php
// check_package.php
include("db_connect.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedRoles = $_POST['roles'];
        $numSelectedRoles = count($selectedRoles);
        
        // If only one role is selected, check for role packages
        if ($numSelectedRoles === 1) {
            $role = $selectedRoles[0];
            
            // Get role_id from roles table
            $roleQuery = "SELECT role_id FROM roles WHERE role_name = ?";
            $stmt = $conn->prepare($roleQuery);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $roleResult = $stmt->get_result();
            
            if ($roleRow = $roleResult->fetch_assoc()) {
                $roleId = $roleRow['role_id'];
                
                // Get role packages
                $packageQuery = "SELECT * FROM role_packages WHERE role_id = ?";
                $stmt = $conn->prepare($packageQuery);
                $stmt->bind_param("i", $roleId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $packages = [];
                while ($row = $result->fetch_assoc()) {
                    $packages[] = [
                        'package_name' => $row['package_name'],
                        'price' => $row['package_price'],
                        'duration' => $row['package_duration'],
                        'duration_unit' => $row['package_duration_unit'],
                        'roles' => [$role],
                        'savings' => 0,
                        'type' => 'role'
                    ];
                }
                
                echo json_encode(['packages' => $packages]);
            } else {
                echo json_encode(['packages' => []]);
            }
        } else {
            // For combo packages
            $roleList = implode("','", array_map(function($role) use ($conn) {
                return $conn->real_escape_string($role);
            }, $selectedRoles));

            // Updated query to use the correct table name combo_package_roles
            $query = "SELECT 
                        cp.combo_id,
                        cp.package_name,
                        cp.price,
                        GROUP_CONCAT(r.role_name) as roles
                    FROM combo_packages cp
                    JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
                    JOIN roles r ON cpr.role_id = r.role_id
                    WHERE r.role_name IN ('$roleList')
                    GROUP BY cp.combo_id
                    HAVING COUNT(DISTINCT r.role_name) = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $numSelectedRoles);
            $stmt->execute();
            $result = $stmt->get_result();

            $packages = [];
            while ($row = $result->fetch_assoc()) {
                $roles = explode(',', $row['roles']);
                $regularPrice = calculateRegularPrice($conn, $roles);
                $savings = $regularPrice - $row['price'];

                $packages[] = [
                    'package_name' => $row['package_name'],
                    'price' => $row['price'],
                    'roles' => $roles,
                    'savings' => $savings,
                    'type' => 'combo'
                ];
            }

            echo json_encode(['packages' => $packages]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
}

function calculateRegularPrice($conn, $roles) {
    $totalPrice = 0;
    foreach ($roles as $role) {
        $query = "SELECT rate FROM roles WHERE role_name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalPrice += $row['rate'];
        }
    }
    return $totalPrice;
}
?>