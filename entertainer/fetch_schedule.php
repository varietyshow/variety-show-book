<?php
session_start();
header('Content-Type: application/json');

// Database connection details
$host = 'sql12.freesqldatabase.com';
$db = 'sql12777569';
$user = 'sql12777569';
$pass = 'QlgHSeuU1n';

// Create a new PDO instance
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Retrieve entertainer_id from session
$entertainer_id = $_SESSION['entertainer_id'] ?? null;
$status = $_GET['status'] ?? '';

if (!$entertainer_id) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Prepare and execute the SQL query with optional status filter
$sql = "SELECT date, start_time, end_time, price, status FROM sched_time WHERE entertainer_id = :entertainer_id";
$params = ['entertainer_id' => $entertainer_id];

if ($status) {
    $sql .= " AND status = :status";
    $params['status'] = $status;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert time format
foreach ($schedule as &$row) {
    $row['start_time'] = date("h:i A", strtotime($row['start_time'])); // Convert to 12-hour format
    $row['end_time'] = date("h:i A", strtotime($row['end_time'])); // Convert to 12-hour format
}

echo json_encode($schedule);
?>
