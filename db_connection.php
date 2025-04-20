<?php
$servername = "sql.freedb.tech";
$username = "freedb_varietyshow";  // Default XAMPP username
$password = "D!H7nuCsrenD8WM";      // Default XAMPP password
$dbname = "freedb_db_booking_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 
