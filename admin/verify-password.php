<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database configuration
    $host = 'sql12.freesqldatabase.com';
    $dbname = 'sql12777569';
    $username = 'sql12777569';
    $password = 'QlgHSeuU1n';

    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $current_password = $_POST['current_password'];
    $first_name = $_SESSION['first_name'];

    // Get the user's actual password
    $stmt = $conn->prepare("SELECT password FROM admin_account WHERE first_name = ?");
    $stmt->bind_param("s", $first_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if password matches
    $isValid = ($user && $current_password === $user['password']);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['valid' => $isValid]);
    exit();
}
?> 
