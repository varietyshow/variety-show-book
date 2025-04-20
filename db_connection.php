<?php
$servername = getenv("sql.freedb.tech");
$username = getenv("freedb_varietyshow");  // Default XAMPP username
$password = getenv("D!H7nuCsrenD8WM");      // Default XAMPP password
$dbname = getenv("freedb_db_booking_system");

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 
