<?php
session_start();

// Database connection
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12775634';
$username = 'sql12775634';
$password = 'kPZFb8pXsU';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the entertainer's name from the session
    $entertainer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    
    // Fetch approved appointments for this entertainer
    $stmt = $pdo->prepare("SELECT date_schedule FROM booking_report WHERE entertainer_name LIKE ? AND status = 'Approved'");
    $stmt->execute(['%' . $entertainer_name . '%']);
    
    $bookedDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Return the dates as JSON
    header('Content-Type: application/json');
    echo json_encode(['bookedDates' => $bookedDates]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?> 
