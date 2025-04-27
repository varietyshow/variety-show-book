<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start session and include database connection
session_start();
require_once 'db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['first_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['book_id'])) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit();
}

try {
    // Sanitize input
    $book_id = filter_var($_POST['book_id'], FILTER_SANITIZE_NUMBER_INT);
    
    if (!$book_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid book ID format']);
        exit();
    }

    // Create new PDO connection to ensure we have a fresh connection
    $host = 'sql12.freesqldatabase.com';
    $dbname = 'sql12775634';
    $username = 'sql12775634';
    $password = 'kPZFb8pXsU';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT reason FROM booking_report WHERE book_id = ? LIMIT 1");
    $stmt->execute([$book_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'reason' => $result['reason'] ?: 'No reason provided'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in get_reason.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
} catch (Exception $e) {
    error_log("General error in get_reason.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
}
