<?php
// Simple Payment Processing Script (Mock Implementation)

// Ensure no HTML errors are output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

try {
    header('Content-Type: application/json');

    // Validate required POST data
    $required = ['payment_method', 'amount', 'booking_id', 'first_name', 'last_name', 'email', 'contact_number'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $payment_method = $_POST['payment_method'];
    $amount = intval($_POST['amount']); // amount in centavos
    $booking_id = $_POST['booking_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];

    // Log the request data for debugging
    error_log("Payment request: " . json_encode($_POST));

    // For demonstration purposes, create a mock checkout URL
    // In a real implementation, you would integrate with the specific payment gateway's API
    
    // Create a unique reference for this payment
    $payment_reference = 'PAY-' . uniqid();
    $mock_charge_id = 'ch_' . uniqid();
    
    // Build the checkout URL based on the payment method
    switch ($payment_method) {
        case 'gcash':
            $checkout_url = "https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?ref={$payment_reference}&method=gcash&booking_id={$booking_id}&charge_id={$mock_charge_id}";
            break;
        case 'paymaya':
            $checkout_url = "https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?ref={$payment_reference}&method=paymaya&booking_id={$booking_id}&charge_id={$mock_charge_id}";
            break;
        case 'paypal':
            $checkout_url = "https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?ref={$payment_reference}&method=paypal&booking_id={$booking_id}&charge_id={$mock_charge_id}";
            break;
        default:
            throw new Exception("Unsupported payment method: {$payment_method}");
    }
    
    // Log the checkout URL for debugging
    error_log("Generated checkout URL: {$checkout_url}");
    
    // Return success response with checkout URL
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_url,
        'payment_reference' => $payment_reference,
        'charge_id' => $mock_charge_id
    ]);
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return JSON error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment processing error: ' . $e->getMessage()]);
}

// End output buffering and discard any unexpected output
ob_end_flush();
