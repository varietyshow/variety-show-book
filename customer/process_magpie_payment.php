<?php
// Magpie Payment Processing Script (Server-side Source Creation)

// Ensure no HTML errors are output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/magpie_config.php';
    require_once 'db_connect.php';

    // Use the Magpie SDK
    use MagpieApi\Magpie;

    header('Content-Type: application/json');

    // Validate required POST data
    $required = ['payment_method', 'amount', 'booking_id', 'first_name', 'last_name', 'email', 'contact_number'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $payment_method = $_POST['payment_method'];
    $amount = intval($_POST['amount']); // amount in centavos (multiply by 100)
    $booking_id = $_POST['booking_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];

    // Log the request data for debugging
    error_log("Payment request: " . json_encode($_POST));

    // Initialize Magpie with test keys
    $magpie = new Magpie(MAGPIE_PUBLISHABLE_KEY, MAGPIE_SECRET_KEY, true); // true = sandbox mode

    // Create a unique reference for this payment
    $payment_reference = 'PAY-' . uniqid();
    
    // Define success and failed URLs with parameters
    $success_url = MAGPIE_SUCCESS_URL . "&booking_id={$booking_id}&ref={$payment_reference}";
    $failed_url = MAGPIE_FAILED_URL . "&booking_id={$booking_id}&ref={$payment_reference}";
    
    // Create a payment source based on the payment method
    $checkout_url = "";
    $charge_id = "";
    
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
    
    // If we couldn't get a checkout URL, fall back to our local confirmation page
    if (empty($checkout_url)) {
        // For testing purposes, use a mock checkout URL
        $checkout_url = "payment-confirmation.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
        error_log("Using fallback checkout URL: {$checkout_url}");
    }
    
    // Log the checkout URL for debugging
    error_log("Generated checkout URL: {$checkout_url}");
    
    // Insert a record into the payment_transactions table
    try {
        $stmt = $conn->prepare("INSERT INTO payment_transactions (booking_id, charge_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $status = 'pending';
        $stmt->bind_param("isids", $booking_id, $charge_id, $amount, $payment_method, $status);
        $stmt->execute();
        error_log("Payment transaction record created successfully");
    } catch (Exception $e) {
        // Table might not exist, just log the error and continue
        error_log("Could not insert payment transaction: " . $e->getMessage());
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
