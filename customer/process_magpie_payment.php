<?php
// Magpie Payment Source Creation Script
// This script receives POST data with amount, booking_id, selected payment method, and creates a Magpie payment source.

header('Content-Type: application/json');
require_once '../config/magpie_config.php'; // Store your Magpie keys here

// Validate required POST data
$required = ['amount', 'booking_id', 'payment_method'];
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

// Prepare Magpie API request
$api_url = 'https://api.magpie.im/v1/sources';
$api_key = MAGPIE_SECRET_KEY;

$data = [
    'type' => $source_type,
    'amount' => $amount_cents,
    'currency' => 'php',
    'redirect' => [
        'success' => MAGPIE_SUCCESS_URL, // Set in config
        'failed' => MAGPIE_FAILED_URL
    ],
    'description' => "Booking #$booking_id payment via $payment_method"
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Magpie API error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$result = json_decode($response, true);

if ($http_code === 200 && isset($result['redirect']['checkout_url'])) {
    echo json_encode([
        'success' => true,
        'checkout_url' => $result['redirect']['checkout_url'],
        'source_id' => $result['id']
    ]);
} else {
    $error_message = isset($result['error']['message']) ? $result['error']['message'] : 'Unknown Magpie error';
    echo json_encode(['success' => false, 'message' => $error_message, 'response' => $result]);
}
