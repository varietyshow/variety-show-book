<?php
require_once 'db_connection.php';
require_once 'includes/mail-config.php';

header('Content-Type: application/json');

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($data['email'])) {
    $email = mysqli_real_escape_string($conn, $data['email']);
    
    try {
        // Get user details
        $stmt = $conn->prepare("SELECT customer_id, first_name FROM customer_account WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Delete any existing tokens
                $delete_stmt = $conn->prepare("DELETE FROM verification_tokens WHERE customer_id = ?");
                $delete_stmt->bind_param("i", $user['customer_id']);
                $delete_stmt->execute();
                
                // Generate new 6-digit verification code
                $verification_code = sprintf("%06d", mt_rand(0, 999999));
                $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store new verification code
                $token_sql = "INSERT INTO verification_tokens (customer_id, token, expiry) VALUES (?, ?, ?)";
                $token_stmt = $conn->prepare($token_sql);
                $token_stmt->bind_param("iss", $user['customer_id'], $verification_code, $expiry);
                $token_stmt->execute();
                
                // Send verification email
                $email_body = "
                    <h2>New Verification Code</h2>
                    <p>Dear {$user['first_name']},</p>
                    <p>Your new verification code is:</p>
                    <h1 style='text-align: center; font-size: 32px; letter-spacing: 5px; margin: 20px 0;'>$verification_code</h1>
                    <p>This code will expire in 15 minutes.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                ";
                
                if (sendEmail($email, "Your New Verification Code", $email_body)) {
                    $conn->commit();
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'New verification code sent to your email'
                    ]);
                } else {
                    throw new Exception("Failed to send verification email");
                }
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email not found'
            ]);
        }
    } catch (Exception $e) {
        error_log("Resend code error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to resend verification code. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?>
