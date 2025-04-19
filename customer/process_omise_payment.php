<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once 'db_connect.php';
require_once 'fake_payment_gateway.php';

header('Content-Type: application/json');

try {
    // Get the JSON data from the request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $required_fields = ['amount', 'currency', 'payment_method', 'phone_number'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Process payment using fake payment gateway
    $payment = FakePaymentGateway::createPayment(
        $data['amount'],
        $data['currency'],
        $data['payment_method'],
        $data['phone_number']
    );

    if ($payment['status'] === 'success') {
        // Store transaction details in database
        $stmt = $conn->prepare("INSERT INTO payment_transactions (transaction_id, amount, status, payment_method, created_at) VALUES (?, ?, ?, ?, NOW())");
        $amount = $data['amount'] / 100; // Convert back to original currency
        $status = 'pending';
        $stmt->bind_param("sdss", $payment['transaction_id'], $amount, $status, $data['payment_method']);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Payment initiated successfully',
            'redirect_url' => $payment['redirect_url']
        ]);
    } else {
        throw new Exception($payment['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

