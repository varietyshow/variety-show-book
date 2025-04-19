<?php
session_start();
require_once '../db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['entertainer_id'])) {
    echo json_encode([]);
    exit;
}

$entertainer_id = $_SESSION['entertainer_id'];
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$current_month = "$year-$month";

// Fetch schedules for the specified month
$schedules = array();
$sql = "SELECT date, status FROM sched_time WHERE entertainer_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $entertainer_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $schedules[$row['date']] = $row['status'];
}

echo json_encode($schedules);
?>
