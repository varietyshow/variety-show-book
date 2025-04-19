<?php
$host = "localhost";
$dbname = "db_booking_system";
$username = "root";
$password = "";

// Create mysqli connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
