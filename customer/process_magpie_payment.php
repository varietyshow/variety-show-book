<?php
// Magpie Payment Processing Script (Token-based)

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/magpie_config.php';

use MagpieApi\Magpie;

header('Content-Type: application/json');

// Validate required POST data
$required = ['token', 'amount', 'booking_id', 'first_name', 'last_name', 'email'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing $field"]);
        exit;
    }
}

$token = $_POST['token'];
$amount = intval($_POST['amount']); // amount in centavos
$booking_id = $_POST['booking_id'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];

try {
    $magpie = new Magpie(MAGPIE_PUBLISHABLE_KEY, MAGPIE_SECRET_KEY, true); // true = sandbox
    $charge = $magpie->charge->create(
        $amount,
        'php',
        $token,
        "Booking #$booking_id payment",
        '', // statement_descriptor
        true // capture
    );
    $result = $charge->getData();
    $http_code = $charge->getHttpStatus();

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
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Magpie SDK error: ' . $e->getMessage()]);
}
