<?php
// Payment Processing Script with PayMongo Integration

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a debug log file
$debug_log = dirname(__DIR__) . '/logs/payment_debug.log';
file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment process started' . PHP_EOL, FILE_APPEND);
file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] POST data: ' . json_encode($_POST) . PHP_EOL, FILE_APPEND);

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Include required files
    require_once 'db_connect.php';
    require_once dirname(__DIR__) . '/config/paymongo_config.php';
    require_once 'paymongo_integration.php';
    
    // Log the included files
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Required files included' . PHP_EOL, FILE_APPEND);
    
    // Validate required POST data
    $required = ['payment_method', 'amount', 'booking_id', 'first_name', 'last_name', 'email', 'contact_number'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Missing fields: ' . implode(', ', $missing) . PHP_EOL, FILE_APPEND);
        throw new Exception("Missing required fields: " . implode(', ', $missing));
    }

    // Get POST data
    $payment_method = $_POST['payment_method'];
    $amount = floatval($_POST['amount']);
    $booking_id = $_POST['booking_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $customer_name = $first_name . ' ' . $last_name;

    // Log the request data for debugging
    error_log("Payment request received: method={$payment_method}, amount={$amount}, booking_id={$booking_id}");
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment request received: method=' . $payment_method . ', amount=' . $amount . ', booking_id=' . $booking_id . PHP_EOL, FILE_APPEND);
    
    // Create a unique reference and charge ID for this payment
    $payment_reference = 'PAY-' . uniqid();
    $charge_id = 'ch_' . uniqid();
    
    // Process payment based on payment method
    try {
        if ($payment_method === 'gcash') {
            // Use PayMongo API for GCash
            // Convert amount from centavos to PHP if needed
            $amount_in_php = $amount;
            if ($amount > 1000) { // If amount seems to be in centavos (e.g., 10000 for PHP 100)
                $amount_in_php = $amount / 100;
            }
            
            // Build success and failed URLs with parameters
            $success_url = PAYMONGO_SUCCESS_URL . "&booking_id={$booking_id}&ref={$payment_reference}&method={$payment_method}";
            $failed_url = PAYMONGO_FAILED_URL . "&booking_id={$booking_id}&ref={$payment_reference}&method={$payment_method}";
            
            // Log the amount conversion
            error_log("Amount conversion: original={$amount}, converted={$amount_in_php}");
            file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Amount conversion: original=' . $amount . ', converted=' . $amount_in_php . PHP_EOL, FILE_APPEND);
            
            $source_response = createGCashSource(
                $amount_in_php,
                $customer_name,
                $email,
                $contact_number,
                $success_url,
                $failed_url,
                $payment_reference
            );
            
            // Log the full response for debugging
            error_log("PayMongo response: " . json_encode($source_response));
            file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] PayMongo response: ' . json_encode($source_response) . PHP_EOL, FILE_APPEND);
            
            // Check if source creation was successful
            if (isset($source_response['data']) && isset($source_response['data']['id'])) {
                $source_id = $source_response['data']['id'];
                $checkout_url = $source_response['data']['attributes']['redirect']['checkout_url'];
                $charge_id = $source_id; // Use source ID as charge ID
                
                // Log successful source creation
                logPayMongoActivity("GCash source created successfully", [
                    'source_id' => $source_id,
                    'booking_id' => $booking_id,
                    'amount' => $amount_in_php,
                    'checkout_url' => $checkout_url
                ]);
                file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] GCash source created successfully' . PHP_EOL, FILE_APPEND);
                
                error_log("SUCCESS: GCash source created. Redirecting to: {$checkout_url}");
                file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] SUCCESS: GCash source created. Redirecting to: ' . $checkout_url . PHP_EOL, FILE_APPEND);
            } else {
                // Log error and throw exception
                logPayMongoActivity("Failed to create GCash source", $source_response);
                file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Failed to create GCash source' . PHP_EOL, FILE_APPEND);
                
                if (isset($source_response['errors'])) {
                    $error_details = json_encode($source_response['errors']);
                    error_log("PayMongo API Error: {$error_details}");
                    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] PayMongo API Error: ' . $error_details . PHP_EOL, FILE_APPEND);
                    throw new Exception("Failed to create payment source: {$error_details}");
                } else {
                    error_log("Unknown PayMongo API Error: " . json_encode($source_response));
                    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Unknown PayMongo API Error: ' . json_encode($source_response) . PHP_EOL, FILE_APPEND);
                    throw new Exception("Failed to create payment source. Please try again.");
                }
            }
        } else {
            // For other payment methods, use the mock payment gateway
            $checkout_url = "mock_payment_gateway.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}";
            file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Using mock payment gateway' . PHP_EOL, FILE_APPEND);
        }
    } catch (Exception $e) {
        // If PayMongo API fails, fall back to mock payment gateway
        error_log("PayMongo API error, falling back to mock implementation: " . $e->getMessage());
        file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] PayMongo API error, falling back to mock implementation: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        $checkout_url = "mock_payment_gateway.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}";
    }
    
    // Log the checkout URL for debugging
    error_log("Generated checkout URL: {$checkout_url}");
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Generated checkout URL: ' . $checkout_url . PHP_EOL, FILE_APPEND);
    
    // Insert a record into the payment_transactions table if it exists
    try {
        $stmt = $conn->prepare("INSERT INTO payment_transactions (booking_id, charge_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $status = 'pending';
        $stmt->bind_param("isids", $booking_id, $charge_id, $amount, $payment_method, $status);
        $stmt->execute();
        file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment transaction inserted' . PHP_EOL, FILE_APPEND);
    } catch (Exception $e) {
        // Table might not exist, just log the error and continue
        error_log("Could not insert payment transaction: " . $e->getMessage());
        file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Could not insert payment transaction: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
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
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment process completed' . PHP_EOL, FILE_APPEND);
    
} catch (Exception $e) {
    // Clear any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment processing error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // Return JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
    file_put_contents($debug_log, '[' . date('Y-m-d H:i:s') . '] Payment process failed' . PHP_EOL, FILE_APPEND);
}
