<?php
// Magpie Payment Processing Script (Server-side Source Creation)

// Ensure no HTML errors are output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once '../config/magpie_config.php';

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
    $amount = intval($_POST['amount']); // amount in centavos
    $booking_id = $_POST['booking_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];

    // Log the request data for debugging
    error_log("Payment request: " . json_encode($_POST));

    // Create Magpie instance
    $magpie = new Magpie(MAGPIE_PUBLISHABLE_KEY, MAGPIE_SECRET_KEY, true); // true = sandbox
    
    // Create a source for the payment
    $source_data = [
        'type' => $payment_method,
        'amount' => $amount,
        'currency' => 'PHP',
        'redirect' => [
            'success' => MAGPIE_SUCCESS_URL . '&booking_id=' . $booking_id,
            'failed' => MAGPIE_FAILED_URL . '&booking_id=' . $booking_id
        ],
        'billing' => [
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'phone' => $contact_number
        ],
        'metadata' => [
            'booking_id' => $booking_id
        ]
    ];
    
    // Log the source data for debugging
    error_log("Source data: " . json_encode($source_data));
    
    // Create the source
    $source = $magpie->source->create($source_data);
    $result = $source->getData();
    $http_code = $source->getHttpStatus();
    
    // Log the response for debugging
    error_log("Magpie response: " . json_encode($result));
    
    // Success: Look for redirect URL for wallet payment
    if ($http_code === 201 && isset($result['redirect']['checkout_url'])) {
        echo json_encode([
            'success' => true,
            'checkout_url' => $result['redirect']['checkout_url'],
            'source_id' => $result['id']
        ]);
    } else {
        $error_message = isset($result['error']['message']) ? $result['error']['message'] : (isset($result['message']) ? $result['message'] : 'Unknown Magpie error');
        echo json_encode(['success' => false, 'message' => $error_message, 'response' => $result]);
    }
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Magpie payment error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return JSON error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment processing error: ' . $e->getMessage()]);
}

// End output buffering and discard any unexpected output
ob_end_flush();
