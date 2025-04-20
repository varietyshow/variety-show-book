<?php
$host = getenv("sql.freedb.tech");
$dbname = getenv("freedb_db_booking_system");
$username = getenv("freedb_varietyshow");
$password = getenv("D!H7nuCsrenD8WM");

// Create mysqli connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
