<?php
// Test script for PayMongo GCash integration

// Enable error display for testing
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/config/paymongo_config.php';
require_once __DIR__ . '/customer/paymongo_integration.php';

// Create a test source
$amount = 100; // PHP 100
$name = "Test User";
$email = "test@example.com";
$phone = "09123456789";
$success_url = PAYMONGO_SUCCESS_URL . "&booking_id=1234&ref=TEST-REF&method=gcash";
$failed_url = PAYMONGO_FAILED_URL . "&booking_id=1234&ref=TEST-REF&method=gcash";
$reference = "TEST-" . uniqid();

echo "<h1>PayMongo GCash Test</h1>";
echo "<p>Creating GCash source for amount: PHP {$amount}</p>";

try {
    $response = createGCashSource(
        $amount,
        $name,
        $email,
        $phone,
        $success_url,
        $failed_url,
        $reference
    );
    
    echo "<h2>API Response:</h2>";
    echo "<pre>" . print_r($response, true) . "</pre>";
    
    if (isset($response['data']) && isset($response['data']['id'])) {
        $source_id = $response['data']['id'];
        $checkout_url = $response['data']['attributes']['redirect']['checkout_url'];
        
        echo "<h2>Success!</h2>";
        echo "<p>Source ID: {$source_id}</p>";
        echo "<p>Checkout URL: <a href='{$checkout_url}' target='_blank'>{$checkout_url}</a></p>";
        echo "<p><a href='{$checkout_url}' class='btn' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 4px;'>Go to GCash Payment Page</a></p>";
    } else {
        echo "<h2>Error creating source</h2>";
        if (isset($response['errors'])) {
            echo "<p>Error details:</p>";
            echo "<pre>" . print_r($response['errors'], true) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<h2>Exception occurred</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
