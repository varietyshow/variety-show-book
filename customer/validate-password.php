<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo 'invalid';
    exit();
}

// Database connection
$host = "sql12.freesqldatabase.com";
$dbname = "sql12777569";
$username = "sql12777569";
$password = "QlgHSeuU1n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo 'invalid';
    exit();
}

if (isset($_POST['current_password'])) {
    $currentPassword = $_POST['current_password'];
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM customer_account WHERE username = ?");
        $stmt->execute([$_SESSION['username']]);
        $user = $stmt->fetch();

        // Direct password comparison since we're not using password_hash
        if ($user && $currentPassword === $user['password']) {
            echo 'valid';
        } else {
            echo 'invalid';
        }
    } catch(PDOException $e) {
        echo 'invalid';
    }
} else {
    echo 'invalid';
} 
