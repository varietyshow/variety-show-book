<?php
$servername = getenv("sql12.freesqldatabase.com");
$username = getenv("sql12774230");  // Default XAMPP username
$password = getenv("ytPEFx33BF");      // Default XAMPP password
$dbname = getenv("sql12774230");

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 
