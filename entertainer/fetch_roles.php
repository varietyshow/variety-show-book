<?php
// fetch_roles.php

// Database credentials
$host = 'localhost';
$db   = 'db_booking_system';
$user = 'root';
$pass = '';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch roles from the database
    $stmt = $pdo->query("SELECT role_name FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Return roles as JSON
    echo json_encode($roles);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>