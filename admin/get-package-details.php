<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Get combo_id from GET parameter
if (!isset($_GET['combo_id'])) {
    echo json_encode(['success' => false, 'message' => 'Package ID is required']);
    exit();
}

$comboId = intval($_GET['combo_id']);

try {
    // Get package details
    $stmt = $conn->prepare("
        SELECT 
            cp.combo_id,
            cp.package_name,
            cp.price,
            GROUP_CONCAT(DISTINCT CONCAT_WS(':', cpr.role_id, cpr.entertainer_id) SEPARATOR ',') as pairs,
            pd.duration,
            pd.duration_unit
        FROM combo_packages cp
        LEFT JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
        LEFT JOIN package_durations pd ON cp.combo_id = pd.package_id
        WHERE cp.combo_id = ?
        GROUP BY cp.combo_id
    ");
    
    $stmt->bind_param("i", $comboId);
    $stmt->execute();
    $result = $stmt->get_result();
    $package = $result->fetch_assoc();

    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit();
    }

    // Format the response
    $response = [
        'package_name' => $package['package_name'],
        'price' => $package['price'],
        'duration' => $package['duration'],
        'duration_unit' => $package['duration_unit'],
        'pairs' => []
    ];

    // Process role-entertainer pairs
    if ($package['pairs']) {
        $pairs = explode(',', $package['pairs']);
        foreach ($pairs as $pair) {
            list($roleId, $entertainerId) = explode(':', $pair);
            $response['pairs'][] = [
                'role_id' => $roleId,
                'entertainer_id' => $entertainerId
            ];
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
