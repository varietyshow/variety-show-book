<?php
// delete-payment-method.php

// Database connection details
$servername = "sql12.freesqldatabase.com"; // Your database server
$username = "sql12775634"; // Your database username
$password = "kPZFb8pXsU"; // Your database password
$dbname = "sql12775634"; // Your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$billingId = $data['id'];

// Prepare and bind
$stmt = $conn->prepare("DELETE FROM billing WHERE billing_id = ?");
$stmt->bind_param("i", $billingId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
