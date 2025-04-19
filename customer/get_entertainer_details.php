<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No entertainer ID provided']);
    exit;
}

$entertainerId = $_GET['id'];

// Prepare the query to get entertainer details
$stmt = $conn->prepare("
    SELECT e.*, GROUP_CONCAT(u.filename) as uploads
    FROM entertainer_account e
    LEFT JOIN uploads u ON e.entertainer_id = u.entertainer_id
    WHERE e.entertainer_id = ?
    GROUP BY e.entertainer_id
");

$stmt->bind_param("i", $entertainerId);
$stmt->execute();
$result = $stmt->get_result();
$entertainer = $result->fetch_assoc();

if (!$entertainer) {
    echo json_encode(['error' => 'Entertainer not found']);
    exit;
}

// Format the response
$response = [
    'name' => $entertainer['first_name'] . ' ' . $entertainer['last_name'],
    'description' => $entertainer['description'],
    'uploads' => []
];

// Process uploads
if ($entertainer['uploads']) {
    $files = explode(',', $entertainer['uploads']);
    foreach ($files as $file) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $type = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'video';
        
        $response['uploads'][] = [
            'type' => $type,
            'path' => '../uploads/' . $file
        ];
    }
}

echo json_encode($response);
?> 