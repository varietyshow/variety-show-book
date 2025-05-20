<?php
require_once 'db_connection.php';
require_once 'includes/mail-config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize and validate input data
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $agreed_to_terms = isset($_POST['agree']) ? 1 : 0;

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            exit();
        }

        // Validate contact number format (11 digits)
        if (!preg_match('/^[0-9]{11}$/', $contact_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Contact number must be 11 digits']);
            exit();
        }

        // Check if username already exists
        $check_username = "SELECT username FROM customer_account WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
            exit();
        }

        // Check if email already exists
        $check_email = "SELECT email FROM customer_account WHERE email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
            exit();
        }

        // Check if contact number already exists
        $check_contact = "SELECT contact_number FROM customer_account WHERE contact_number = ?";
        $stmt = $conn->prepare($check_contact);
        $stmt->bind_param("s", $contact_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Contact number already exists']);
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        // Insert user with verified=0
        $sql = "INSERT INTO customer_account (first_name, last_name, contact_number, email, username, password, agreed_to_terms, verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", 
            $first_name, 
            $last_name, 
            $contact_number,
            $email,
            $username, 
            $password, 
            $agreed_to_terms
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create account: " . $stmt->error);
        }
        
        $customer_id = $conn->insert_id;

        // Generate 6-digit verification code
        $verification_code = sprintf("%06d", mt_rand(0, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Store verification code
        $token_sql = "INSERT INTO verification_tokens (customer_id, token, expiry) VALUES (?, ?, ?)";
        $token_stmt = $conn->prepare($token_sql);
        $token_stmt->bind_param("iss", $customer_id, $verification_code, $expiry);
        
        if (!$token_stmt->execute()) {
            throw new Exception("Failed to create verification token: " . $token_stmt->error);
        }

        // Send verification email
        $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='font-size: 24px; color: #333;'>Welcome to Our Service!</h1>
                
                <p>Dear $first_name,</p>
                
                <p>Thank you for registering. Your verification code is:</p>
                
                <h2 style='font-size: 36px; letter-spacing: 5px; text-align: center; color: #333;'>$verification_code</h2>
                
                <p>Please click the link below to verify your email:</p>
                
                <div style='text-align: left; margin: 20px 0;'>
                    <a href='https://variety-show-book-2.onrender.com/verify-email.php' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email</a>
                </div>
                
                <p>Or you can enter your verification code manually at: <a href='http://localhost/new-system/verify-email.php' style='color: #0066cc; text-decoration: underline;'>http://localhost/new-system/verify-email.php</a></p>
                
                <p>This code will expire in 15 minutes.</p>
                
                <p>If you did not create this account, please ignore this email.</p>
            </div>
        ";

        if (sendEmail($email, "Your Verification Code", $email_body)) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Verification code sent']);
            exit();
        } else {
            throw new Exception("Failed to send verification email");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
        exit();
    }
}
?>
