<?php
/**
 * Process PayMongo Payment
 * 
 * This file handles the creation of a GCash payment source and redirects
 * the user to the payment page.
 */

// Start session
session_start();

// Include required files
require_once 'db_connect.php';
require_once 'paymongo_integration.php';

// Enable error logging
ini_set('display_errors', 1); // Enable for debugging
error_reporting(E_ALL);

// Create a log file for debugging
function debug_log($message, $data = []) {
    $log_file = '../logs/payment_debug.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($data)) {
        $log_message .= ' - ' . json_encode($data);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => ''
];

// Debug the start of the process
debug_log('Payment process started', $_GET);

try {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        debug_log('User not logged in', $_SESSION);
        throw new Exception("User not logged in");
    }
    
    // Get customer information
    $customer_id = $_SESSION['customer_id'];
    debug_log('Customer ID', ['customer_id' => $customer_id]);
    
    $query = "SELECT * FROM customer_account WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$customer = $result->fetch_assoc()) {
        debug_log('Customer not found', ['customer_id' => $customer_id]);
        throw new Exception("Customer information not found");
    }
    
    debug_log('Customer found', ['name' => $customer['first_name'] . ' ' . $customer['last_name']]);
    
    // Get booking information
    if (!isset($_GET['booking_id']) || !isset($_GET['amount'])) {
        debug_log('Missing parameters', $_GET);
        throw new Exception("Missing required parameters");
    }
    
    $booking_id = $_GET['booking_id'];
    $amount = floatval($_GET['amount']);
    
    debug_log('Booking parameters', ['booking_id' => $booking_id, 'amount' => $amount]);
    
    // Verify booking exists and belongs to this customer
    $query = "SELECT * FROM booking_report WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$booking = $result->fetch_assoc()) {
        debug_log('Booking not found', ['booking_id' => $booking_id]);
        throw new Exception("Booking not found");
    }
    
    // Removed the customer_id check to allow any booking to be paid
    debug_log('Booking found', ['booking_id' => $booking_id, 'customer_name' => $booking['first_name'] . ' ' . $booking['last_name']]);
    
    // Set up success and failure URLs - use absolute URLs
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = "{$protocol}://{$host}";
    
    if (!str_ends_with($base_url, '/new-system')) {
        $base_url .= '/new-system';
    }
    
    $success_url = "{$base_url}/customer/booking-success.php?booking_id={$booking_id}";
    $failed_url = "{$base_url}/customer/booking-failed.php?booking_id={$booking_id}";
    
    debug_log('Redirect URLs', ['success' => $success_url, 'failed' => $failed_url]);
    
    // Create a reference number for this payment
    $reference_number = "BOOK-{$booking_id}-" . time();
    
    debug_log('Creating GCash source', [
        'amount' => $amount,
        'name' => $customer['first_name'] . ' ' . $customer['last_name'],
        'email' => $customer['email'],
        'phone' => $customer['contact_number'],
        'reference' => $reference_number
    ]);
    
    // Create GCash payment source
    $source_result = createGCashSource(
        $amount,
        $customer['first_name'] . ' ' . $customer['last_name'],
        $customer['email'],
        $customer['contact_number'],
        $success_url,
        $failed_url,
        $reference_number
    );
    
    debug_log('Source result', $source_result);
    
    // Check for errors in source creation
    if (isset($source_result['error'])) {
        debug_log('Source creation error', ['error' => $source_result['error']]);
        throw new Exception("Error creating payment source: " . $source_result['error']);
    }
    
    // Check if source data is available
    if (!isset($source_result['data']) || !isset($source_result['data']['id'])) {
        debug_log('Invalid source response', $source_result);
        throw new Exception("Invalid response from payment provider");
    }
    
    // Get the source ID and checkout URL
    $source_id = $source_result['data']['id'];
    $checkout_url = $source_result['data']['attributes']['redirect']['checkout_url'];
    
    debug_log('Source created successfully', [
        'source_id' => $source_id,
        'checkout_url' => $checkout_url
    ]);
    
    // Update booking with payment reference
    $query = "UPDATE booking_report SET payment_reference = ? WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $reference_number, $booking_id);
    $stmt->execute();
    
    debug_log('Booking updated with payment reference', [
        'booking_id' => $booking_id,
        'reference_number' => $reference_number
    ]);
    
    // Log successful source creation
    logPayMongoActivity("GCash source created successfully", [
        'source_id' => $source_id,
        'booking_id' => $booking_id,
        'reference_number' => $reference_number
    ]);
    
    debug_log('Redirecting to checkout URL', ['url' => $checkout_url]);
    
    // Redirect to the checkout URL
    header('Location: ' . $checkout_url);
    exit;
    
} catch (Exception $e) {
    // Log the error
    $error_message = "Payment processing error: " . $e->getMessage();
    error_log($error_message);
    debug_log('Exception caught', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    logPayMongoActivity("Payment processing error", ['error' => $e->getMessage()]);
    
    // Set error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Output error page with details
    echo '<html><head><title>Payment Error</title>';
    echo '<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:800px;margin:40px auto;padding:20px;background:#f9f9f9;}';
    echo 'h1{color:#e74c3c;}pre{background:#fff;padding:15px;border-radius:5px;overflow:auto;}</style></head>';
    echo '<body><h1>Payment Processing Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Please go back and try again, or contact support if the problem persists.</p>';
    echo '<p><a href="customer-booking.php">Return to booking page</a></p>';
    echo '<div><h3>Technical Details (for support):</h3>';
    echo '<pre>' . htmlspecialchars(json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], JSON_PRETTY_PRINT)) . '</pre></div>';
    echo '</body></html>';
    exit;
}

// Output JSON response (should not reach here if redirect is successful)
echo json_encode($response);
