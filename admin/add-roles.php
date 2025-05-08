<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    // If not logged in, redirect to login page
    header("Location: admin-loginpage.php");
    exit();
}

// Database connection parameters
$servername = "sql12.freesqldatabase.com"; // Change this to your database host
$username = "sql12777569";        // Change this to your database username
$password = "QlgHSeuU1n";            // Change this to your database password
$dbname = "sql12777569"; // Use your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_role') {
    // Collect data from POST request
    $roleName = htmlspecialchars($_POST['roleName']);
    $rate = htmlspecialchars($_POST['rate']);
    $duration = htmlspecialchars($_POST['duration']);
    $durationUnit = htmlspecialchars($_POST['durationUnit']);
    
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO roles (role_name, rate, duration, duration_unit) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $roleName, $rate, $duration, $durationUnit);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Role added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding role: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit; // Stop further execution of the script
}

// Close the database connection if script not terminated above
$conn->close();
?>
