<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    // Database configuration
    $host = "localhost";
    $dbname = "db_booking_system";
    $username = "root";
    $password = "";

    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['payment_proof']) || !isset($_POST['booking_id'])) {
        throw new Exception('Missing required fields');
    }

    $file = $_FILES['payment_proof'];
    $bookingId = $_POST['booking_id'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../images/payment_proofs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payment_' . $bookingId . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save file');
    }

    // Update booking_report table
    $relativePath = 'images/payment_proofs/' . $filename;
    $stmt = $conn->prepare("UPDATE booking_report SET payment_image = ?, status = 'Pending', remarks = 'Pending' WHERE book_id = ?");
    if (!$stmt) {
        // Delete uploaded file if prepare fails
        unlink($targetPath);
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("si", $relativePath, $bookingId);
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database update fails
        unlink($targetPath);
        throw new Exception('Failed to update booking record: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Payment proof uploaded successfully',
        'file_path' => $relativePath
    ]);

} catch (Exception $e) {
    error_log("Payment Proof Upload Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
