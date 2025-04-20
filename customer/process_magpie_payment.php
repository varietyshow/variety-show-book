<?php
// Magpie Payment Source Creation Script
// This script receives POST data with amount, booking_id, selected payment method, and creates a Magpie payment source.

header('Content-Type: application/json');
require_once '../config/magpie_config.php'; // Store your Magpie keys here
require_once __DIR__ . 'vendor/autoload.php'; // Composer autoloader for Magpie SDK

use MagpieApi\Magpie;

// Validate required POST data
$required = ['amount', 'booking_id', 'payment_method', 'first_name', 'last_name', 'email'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing $field"]);
        exit;
    }
}

$amount = floatval($_POST['amount']);
$booking_id = $_POST['booking_id'];
$payment_method = $_POST['payment_method'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];

// Map form payment methods to Magpie source types
$magpie_source_types = [
    'gcash' => 'gcash',
    'paymaya' => 'paymaya',
    'paypal' => 'paypal',
];

if (!isset($magpie_source_types[$payment_method])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

$source_type = $magpie_source_types[$payment_method];

// Magpie expects amount in centavos (PHP * 100)
$amount_cents = intval(round($amount * 100));

// Use a realistic test amount if the amount is too high (for debugging)
if ($amount_cents > 100000) { // > 1,000 PHP
    $amount_cents = 1000; // 10.00 PHP
}

// Initialize Magpie SDK
$magpie = new Magpie(
    MAGPIE_PUBLISHABLE_KEY, // publishable key (can be null if not needed)
    MAGPIE_SECRET_KEY,      // secret key
    true                   // sandbox mode
);

// Prepare charge data
$charge_data = [
    'amount' => $amount_cents,
    'currency' => 'php',
    'source' => [
        'type' => $source_type,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'redirect' => [
            'success' => MAGPIE_SUCCESS_URL,
            'failed' => MAGPIE_FAILED_URL
        ]
    ],
    'description' => "Booking #$booking_id payment via $payment_method",
    'statement_descriptor' => '', // Optional, can be customized
    'capture' => true
];

// Debug: log the data being sent to Magpie
error_log('Magpie CHARGE data: ' . print_r($charge_data, true));

try {
    $response = $magpie->charge->create(
        $charge_data['amount'],
        $charge_data['currency'],
        $charge_data['source'],
        $charge_data['description'],
        $charge_data['statement_descriptor'],
        $charge_data['capture']
    );
    $result = $response->getData();
    $http_code = $response->getHttpStatus();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Magpie SDK error: ' . $e->getMessage()]);
    exit;
}

// Debug: log the raw response from Magpie
error_log('Magpie CHARGE SDK/raw response: ' . print_r($result, true));

// Success: Look for redirect URL for wallet payment
if ($http_code === 201 && isset($result['source']['redirect']['checkout_url'])) {
    echo json_encode([
        'success' => true,
        'checkout_url' => $result['source']['redirect']['checkout_url'],
        'charge_id' => $result['id']
    ]);
} else {
    $error_message = isset($result['error']['message']) ? $result['error']['message'] : (isset($result['message']) ? $result['message'] : 'Unknown Magpie error');
    echo json_encode(['success' => false, 'message' => $error_message, 'response' => $result]);
}
