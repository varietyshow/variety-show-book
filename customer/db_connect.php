<?php
$host = "sql.freedb.tech";
$dbname = "freedb_db_booking_system";
$username = "freedb_varietyshow";
$password = "D!H7nuCsrenD8WM";

// Create mysqli connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
