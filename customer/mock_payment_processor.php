<?php
// Mock Payment Processor - Completely standalone with no dependencies on Magpie SDK

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Include only the database connection
    require_once 'db_connect.php';
    
    // Validate required POST data
    $required = ['payment_method', 'amount', 'booking_id', 'first_name', 'last_name', 'email', 'contact_number'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing));
    }

    // Get POST data
    $payment_method = $_POST['payment_method'];
    $amount = intval($_POST['amount']); 
    $booking_id = $_POST['booking_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];

    // Log the request data for debugging
    error_log("Payment request received: method={$payment_method}, amount={$amount}, booking_id={$booking_id}");
    
    // Create a unique reference and charge ID for this payment
    $payment_reference = 'PAY-' . uniqid();
    $charge_id = 'ch_' . uniqid();
    
    // Build the checkout URL to the mock payment gateway
    $checkout_url = "mock_payment_gateway.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}";
    
    // Log the checkout URL for debugging
    error_log("Generated checkout URL: {$checkout_url}");
    
    // Insert a record into the payment_transactions table if it exists
    try {
        $stmt = $conn->prepare("INSERT INTO payment_transactions (booking_reference, charge_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $status = 'pending';
        $stmt->bind_param("isids", $booking_id, $charge_id, $amount, $payment_method, $status);
        $stmt->execute();
    } catch (Exception $e) {
        // Table might not exist, just log the error and continue
        error_log("Could not insert payment transaction: " . $e->getMessage());
    }
    
    // Clear any output buffer before sending JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return success response with checkout URL
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_url,
        'payment_reference' => $payment_reference,
        'charge_id' => $charge_id
    ]);
    
} catch (Exception $e) {
    // Clear any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    
    // Return JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
