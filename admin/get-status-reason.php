<?php
session_start();
if (!isset($_SESSION['first_name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12777569';
$username = 'sql12777569';
$password = 'QlgHSeuU1n';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_GET['book_id'])) {
        $stmt = $pdo->prepare("SELECT reason, status FROM booking_report WHERE book_id = ?");
        $stmt->execute([$_GET['book_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $message = $result['reason'] ? $result['reason'] : 'No reason provided';
            echo json_encode([
                'success' => true,
                'reason' => $message,
                'status' => $result['status']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Book ID not provided'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 
