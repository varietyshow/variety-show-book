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

// Simple direct payment processing script

// Get booking ID and amount from the URL parameters
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

// Log the start of the process
debug_log('Starting payment process', [
    'booking_id' => $booking_id,
    'amount' => $amount,
    'session' => $_SESSION,
    'get' => $_GET
]);

// Validate parameters
if ($booking_id <= 0 || $amount <= 0) {
    show_error('Invalid booking ID or amount. Please try again.');
}

// Get customer information (either from session or directly from booking)
if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $query = "SELECT * FROM customer_account WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if (!$customer) {
        debug_log('Customer not found in database', ['customer_id' => $customer_id]);
        $customer = [
            'first_name' => 'Guest',
            'last_name' => 'Customer',
            'email' => 'guest@example.com',
            'contact_number' => '09123456789'
        ];
    }
} else {
    // Get customer info from booking
    $query = "SELECT * FROM booking_report WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) {
        show_error('Booking not found. Please try again.');
    }
    
    $customer = [
        'first_name' => $booking['first_name'] ?? 'Guest',
        'last_name' => $booking['last_name'] ?? 'Customer',
        'email' => $booking['email'] ?? 'guest@example.com',
        'contact_number' => $booking['contact_number'] ?? '09123456789'
    ];
}

// Create a unique reference number
$reference_number = "BOOK-{$booking_id}-" . time();

// Set up success and failure URLs
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = "{$protocol}://{$host}";

if (strpos($base_url, 'localhost') !== false || strpos($base_url, '127.0.0.1') !== false) {
    $base_url .= '/new-system';
}

$success_url = "{$base_url}/customer/booking-success.php?booking_id={$booking_id}";
$failed_url = "{$base_url}/customer/booking-failed.php?booking_id={$booking_id}";

debug_log('Payment details', [
    'customer' => $customer,
    'amount' => $amount,
    'reference' => $reference_number,
    'success_url' => $success_url,
    'failed_url' => $failed_url
]);

try {
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
    
    debug_log('Source creation result', $source_result);
    
    // Check for errors
    if (isset($source_result['error'])) {
        throw new Exception("Error creating payment source: " . $source_result['error']);
    }
    
    // Check if source data is available
    if (!isset($source_result['data']) || !isset($source_result['data']['id'])) {
        throw new Exception("Invalid response from payment provider");
    }
    
    // Get checkout URL
    $source_id = $source_result['data']['id'];
    $checkout_url = $source_result['data']['attributes']['redirect']['checkout_url'];
    
    // Update booking with payment reference
    $query = "UPDATE booking_report SET payment_reference = ? WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $reference_number, $booking_id);
    $stmt->execute();
    
    debug_log('Redirecting to GCash', ['checkout_url' => $checkout_url]);
    
    // Redirect to GCash checkout
    echo "<html><head><title>Redirecting to Payment...</title>";
    echo "<script>window.location.href = '{$checkout_url}';</script>";
    echo "</head><body>";
    echo "<h1>Redirecting to GCash Payment...</h1>";
    echo "<p>If you are not automatically redirected, <a href='{$checkout_url}'>click here</a>.</p>";
    echo "</body></html>";
    exit;
    
} catch (Exception $e) {
    debug_log('Payment error', ['error' => $e->getMessage()]);
    show_error($e->getMessage());
}

// Helper function to display errors
function show_error($message) {
    echo "<html><head><title>Payment Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#f9f9f9;}";
    echo "h1{color:#e74c3c;}a{color:#3498db;}</style></head>";
    echo "<body><h1>Payment Processing Error</h1>";
    echo "<p>{$message}</p>";
    echo "<p><a href='customer-booking.php'>Return to booking page</a></p>";
    echo "</body></html>";
    exit;
}

// Output JSON response (should not reach here if redirect is successful)
echo json_encode($response);
