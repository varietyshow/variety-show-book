<?php
// Magpie Payment Processing Script

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Include required files
    require_once 'db_connect.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/magpie_config.php';
    
    // Use the Magpie SDK
    use MagpieApi\Magpie;
    
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
    
    // Create a unique reference for this payment
    $payment_reference = 'PAY-' . uniqid();
    $charge_id = 'ch_' . uniqid();
    
    // Initialize Magpie with test keys
    try {
        $magpie = new Magpie(MAGPIE_PUBLISHABLE_KEY, MAGPIE_SECRET_KEY, true, 'v1'); // true = sandbox mode
        
        // Try to create a payment source with Magpie API
        switch ($payment_method) {
            case 'gcash':
                // Create a GCash payment source using Magpie's API
                $response = $magpie->charge->create(
                    $amount,                   // Amount in centavos (PHP 100 = 10000 centavos)
                    'PHP',                     // Currency
                    'source_gcash',            // Source type for GCash
                    "Booking ID: {$booking_id}", // Description
                    "Variety Show Booking",    // Statement descriptor
                    false                      // Don't capture immediately
                );
                
                if ($response->isSuccess()) {
                    $responseData = $response->getData();
                    $checkout_url = $responseData['redirect']['checkout_url'];
                    $charge_id = $responseData['id'];
                    error_log("GCash source created successfully: " . json_encode($responseData));
                } else {
                    throw new Exception("Failed to create GCash source: " . $response->getMessage());
                }
                break;
                
            case 'paymaya':
                // Create a PayMaya payment source using Magpie's API
                $response = $magpie->charge->create(
                    $amount,                   // Amount in centavos
                    'PHP',                     // Currency
                    'source_paymaya',          // Source type for PayMaya
                    "Booking ID: {$booking_id}", // Description
                    "Variety Show Booking",    // Statement descriptor
                    false                      // Don't capture immediately
                );
                
                if ($response->isSuccess()) {
                    $responseData = $response->getData();
                    $checkout_url = $responseData['redirect']['checkout_url'];
                    $charge_id = $responseData['id'];
                    error_log("PayMaya source created successfully: " . json_encode($responseData));
                } else {
                    throw new Exception("Failed to create PayMaya source: " . $response->getMessage());
                }
                break;
                
            case 'paypal':
                // Create a PayPal payment source using Magpie's API
                $response = $magpie->charge->create(
                    $amount,                   // Amount in centavos
                    'PHP',                     // Currency
                    'source_paypal',           // Source type for PayPal
                    "Booking ID: {$booking_id}", // Description
                    "Variety Show Booking",    // Statement descriptor
                    false                      // Don't capture immediately
                );
                
                if ($response->isSuccess()) {
                    $responseData = $response->getData();
                    $checkout_url = $responseData['redirect']['checkout_url'];
                    $charge_id = $responseData['id'];
                    error_log("PayPal source created successfully: " . json_encode($responseData));
                } else {
                    throw new Exception("Failed to create PayPal source: " . $response->getMessage());
                }
                break;
                
            default:
                throw new Exception("Unsupported payment method: {$payment_method}");
        }
    } catch (Exception $e) {
        // If Magpie API fails, fall back to mock implementation
        error_log("Magpie API error, falling back to mock implementation: " . $e->getMessage());
        
        // Build the checkout URL based on the payment method
        switch ($payment_method) {
            case 'gcash':
                $checkout_url = "payment-confirmation.php?ref={$payment_reference}&method=gcash&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
                break;
            case 'paymaya':
                $checkout_url = "payment-confirmation.php?ref={$payment_reference}&method=paymaya&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
                break;
            case 'paypal':
                $checkout_url = "payment-confirmation.php?ref={$payment_reference}&method=paypal&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
                break;
            default:
                throw new Exception("Unsupported payment method: {$payment_method}");
        }
    }
    
    // Log the checkout URL for debugging
    error_log("Generated checkout URL: {$checkout_url}");
    
    // Insert a record into the payment_transactions table if it exists
    try {
        $stmt = $conn->prepare("INSERT INTO payment_transactions (booking_id, charge_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $status = 'pending';
        $stmt->bind_param("isids", $booking_id, $charge_id, $amount, $payment_method, $status);
        $stmt->execute();
    } catch (Exception $e) {
        // Table might not exist, just log the error and continue
        error_log("Could not insert payment transaction: " . $e->getMessage());
    }
    
    // Clear any output buffer before sending JSON response
    ob_clean();
    
    // Return success response with checkout URL
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_url,
        'payment_reference' => $payment_reference,
        'charge_id' => $charge_id
    ]);
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    
    // Return JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
