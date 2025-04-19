<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verification_code = $_POST['verification_code'];
    $email = $_POST['email'];
    
    // Get current time
    $current_time = date('Y-m-d H:i:s');
    
    try {
        // Get the verification token details
        $stmt = $conn->prepare("
            SELECT vt.*, ca.customer_id 
            FROM verification_tokens vt 
            JOIN customer_account ca ON vt.customer_id = ca.customer_id 
            WHERE ca.email = ? AND vt.token = ? AND vt.expiry > ? 
            AND NOT EXISTS (
                SELECT 1 
                FROM verification_tokens 
                WHERE customer_id = vt.customer_id 
                AND expiry > ?
                AND token_id > vt.token_id
            )
        ");
        
        $stmt->bind_param("ssss", $email, $verification_code, $current_time, $current_time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token_data = $result->fetch_assoc();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update user as verified
                $update_stmt = $conn->prepare("UPDATE customer_account SET verified = 1 WHERE customer_id = ?");
                $update_stmt->bind_param("i", $token_data['customer_id']);
                $update_stmt->execute();
                
                // Delete all tokens for this user
                $delete_stmt = $conn->prepare("DELETE FROM verification_tokens WHERE customer_id = ?");
                $delete_stmt->bind_param("i", $token_data['customer_id']);
                $delete_stmt->execute();
                
                $conn->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Email verified successfully'
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired verification code'
            ]);
        }
    } catch (Exception $e) {
        error_log("Verification error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Verification failed. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>
