<?php
/**
 * PayMongo Integration Helper
 * 
 * This file contains functions to interact with the PayMongo API
 * for creating payment sources, processing payments, and handling webhooks.
 */

require_once dirname(__DIR__) . '/config/paymongo_config.php';

/**
 * Create a PayMongo source for GCash payments
 * 
 * @param float $amount Amount in PHP (will be converted to cents)
 * @param string $name Customer name
 * @param string $email Customer email
 * @param string $phone Customer phone number
 * @param string $success_url URL to redirect on successful payment
 * @param string $failed_url URL to redirect on failed payment
 * @param string $reference_number Optional reference number for the transaction
 * @return array Response from PayMongo API
 */
function createGCashSource($amount, $name, $email, $phone, $success_url, $failed_url, $reference_number = '') {
    // Convert amount to cents (PayMongo requires amount in cents)
    $amount_in_cents = round($amount * 100);
    
    // Prepare the request data
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amount_in_cents,
                'redirect' => [
                    'success' => $success_url,
                    'failed' => $failed_url
                ],
                'billing' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ],
                'type' => 'gcash',
                'currency' => 'PHP'
            ]
        ]
    ];
    
    // Add reference number if provided
    if (!empty($reference_number)) {
        $data['data']['attributes']['reference_number'] = $reference_number;
    }
    
    // Log the request data for debugging
    error_log("PayMongo source request: " . json_encode($data));
    
    // Make the API request
    $response = makePayMongoRequest('sources', $data);
    
    return $response;
}

/**
 * Create a PayMongo payment using a source
 * 
 * @param string $source_id The ID of the source to use for payment
 * @param string $description Description of the payment
 * @return array Response from PayMongo API
 */
function createPayment($source_id, $description = '') {
    // Prepare the request data
    $data = [
        'data' => [
            'attributes' => [
                'source' => [
                    'id' => $source_id,
                    'type' => 'source'
                ],
                'description' => $description
            ]
        ]
    ];
    
    // Make the API request
    $response = makePayMongoRequest('payments', $data);
    
    return $response;
}

/**
 * Retrieve a payment by ID
 * 
 * @param string $payment_id The ID of the payment to retrieve
 * @return array Response from PayMongo API
 */
function retrievePayment($payment_id) {
    // Make the API request
    $response = makePayMongoRequest("payments/{$payment_id}", null, 'GET');
    
    return $response;
}

/**
 * Make a request to the PayMongo API
 * 
 * @param string $endpoint API endpoint to call
 * @param array $data Data to send in the request
 * @param string $method HTTP method (POST, GET, etc.)
 * @return array Response from PayMongo API
 */
function makePayMongoRequest($endpoint, $data = null, $method = 'POST') {
    // Initialize cURL
    $ch = curl_init();
    
    // Set the URL
    curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/' . $endpoint);
    
    // Set the authorization header with the secret key
    curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ":");
    
    // Set request method
    if ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Set the request data if provided
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Set headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Return the response instead of outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Enable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Set timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("cURL Error: " . $error);
        return ['error' => $error, 'http_code' => 0];
    }
    
    // Get the HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL
    curl_close($ch);
    
    // Log the raw response for debugging
    error_log("PayMongo raw response: " . $response);
    
    // Decode the response
    $decoded_response = json_decode($response, true);
    
    // Add HTTP status code to the response
    $decoded_response['http_code'] = $http_code;
    
    return $decoded_response;
}

/**
 * Log PayMongo API activity
 * 
 * @param string $message Message to log
 * @param array $data Data to include in the log
 */
function logPayMongoActivity($message, $data = []) {
    $log_file = dirname(__DIR__) . '/logs/paymongo.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Format the log message
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    
    // Add data if provided
    if (!empty($data)) {
        $log_message .= ' - ' . json_encode($data);
    }
    
    // Append to log file
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    
    // Also log to PHP error log for easier debugging
    error_log($log_message);
}
