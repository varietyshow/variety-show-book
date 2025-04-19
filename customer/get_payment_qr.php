<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
header('Content-Type: application/json'); // Set JSON content type

try {
    // Database configuration
    $host = "localhost";
    $dbname = "db_booking_system";
    $username = "root";
    $password = "";

    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get payment method from POST request
    $input = json_decode(file_get_contents('php://input'), true);
    $payment_method = isset($input['payment_method']) ? strtolower($input['payment_method']) : '';
    
    // Map PayMaya to maya if needed
    if ($payment_method === 'paymaya') {
        $payment_method = 'maya';
    }
    
    if (empty($payment_method)) {
        throw new Exception("Payment method is required");
    }

    // Get QR code from billing table
    $stmt = $conn->prepare("SELECT qr_code, mobile_number, name FROM billing WHERE payment_method = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("s", $payment_method);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Fix the path to point to the correct location
        $qr_code = $row['qr_code'];
        if (strpos($qr_code, '../') === 0) {
            // Keep the '../' since we need to go up from customer directory
            $qr_code = '../' . substr($qr_code, 3);
        }
        
        echo json_encode([
            'success' => true,
            'qr_code' => $qr_code,
            'account_name' => $row['name'],
            'account_number' => $row['mobile_number']
        ]);
    } else {
        throw new Exception("No payment details found for " . htmlspecialchars($payment_method));
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Payment QR Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
