<?php
session_start();

// Database connection
$servername = "sql12.freesqldatabase.com"; // Your database server name
$username = "sql12775634";        // Your database username
$password = "kPZFb8pXsU";            // Your database password
$dbname = "sql12775634"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the POST data
$data = json_decode(file_get_contents("php://input"), true);
$scheduleId = $data['scheduleId'];

// Prepare the SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM sched_time WHERE sched_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();

    // Check if any row has been affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No schedule found with the provided ID.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
