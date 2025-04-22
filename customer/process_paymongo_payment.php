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

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => ''
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception("User not logged in");
    }
    
    // Get customer information
    $customer_id = $_SESSION['customer_id'];
    $query = "SELECT * FROM customer_account WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$customer = $result->fetch_assoc()) {
        throw new Exception("Customer information not found");
    }
    
    // Get booking information
    if (!isset($_GET['booking_id']) || !isset($_GET['amount'])) {
        throw new Exception("Missing required parameters");
    }
    
    $booking_id = $_GET['booking_id'];
    $amount = floatval($_GET['amount']);
    
    // Verify booking exists and belongs to this customer
    $query = "SELECT * FROM booking_report WHERE book_id = ? AND customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$booking = $result->fetch_assoc()) {
        throw new Exception("Booking not found or does not belong to this customer");
    }
    
    // Log the payment attempt
    logPayMongoActivity("Payment attempt initiated", [
        'booking_id' => $booking_id,
        'amount' => $amount,
        'customer_id' => $customer_id
    ]);
    
    // Set up success and failure URLs
    $success_url = "http://{$_SERVER['HTTP_HOST']}/new-system/customer/booking-success.php?booking_id={$booking_id}";
    $failed_url = "http://{$_SERVER['HTTP_HOST']}/new-system/customer/booking-failed.php?booking_id={$booking_id}";
    
    // Create a reference number for this payment
    $reference_number = "BOOK-{$booking_id}-" . time();
    
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
    
    // Check for errors in source creation
    if (isset($source_result['error'])) {
        throw new Exception("Error creating payment source: " . $source_result['error']);
    }
    
    // Check if source data is available
    if (!isset($source_result['data']) || !isset($source_result['data']['id'])) {
        throw new Exception("Invalid response from payment provider");
    }
    
    // Get the source ID and checkout URL
    $source_id = $source_result['data']['id'];
    $checkout_url = $source_result['data']['attributes']['redirect']['checkout_url'];
    
    // Update booking with payment reference
    $query = "UPDATE booking_report SET payment_reference = ? WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $reference_number, $booking_id);
    $stmt->execute();
    
    // Log successful source creation
    logPayMongoActivity("GCash source created successfully", [
        'source_id' => $source_id,
        'booking_id' => $booking_id,
        'reference_number' => $reference_number
    ]);
    
    // Return success response with redirect URL
    $response['success'] = true;
    $response['message'] = "Payment source created successfully";
    $response['redirect_url'] = $checkout_url;
    
    // Redirect to the checkout URL
    header('Location: ' . $checkout_url);
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    logPayMongoActivity("Payment processing error", ['error' => $e->getMessage()]);
    
    // Set error response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Output JSON response
    echo json_encode($response);
    exit;
}

// Output JSON response (should not reach here if redirect is successful)
echo json_encode($response);
