<?php
// fetch_roles.php

// Database credentials
$host = 'sql12.freesqldatabase.com';
$db   = 'sql12777569';
$user = 'sql12777569';
$pass = 'QlgHSeuU1n';

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
