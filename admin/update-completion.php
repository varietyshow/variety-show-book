<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['book_id']) || !isset($_POST['completion_status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

// Validate completion_status value
$completion_status = $_POST['completion_status'];
if (!in_array($completion_status, ['Pending', 'Complete'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid completion status']);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'db_booking_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update the remarks column instead of completion_status
    $stmt = $pdo->prepare("UPDATE booking_report SET remarks = ? WHERE book_id = ? AND status = 'Approved'");
    $stmt->execute([$completion_status, $_POST['book_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Could not update status. Please ensure the booking is approved.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} 