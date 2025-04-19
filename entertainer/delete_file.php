<?php
session_start();

if (!isset($_SESSION['first_name']) || !isset($_POST['upload_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'db_booking_system';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the file information
$upload_id = $_POST['upload_id'];
$sql = "SELECT filename FROM uploads WHERE upload_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $upload_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if ($file) {
    // Delete the file from the uploads directory
    $file_path = "../uploads/" . $file['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete the database record
    $delete_sql = "DELETE FROM uploads WHERE upload_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $upload_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete database record']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File not found']);
}

$conn->close();
?> 