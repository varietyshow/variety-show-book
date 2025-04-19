<?php
session_start();
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verification_code = $_POST['verification_code'];
    $email = $_POST['email'];

    // Check if verification code exists and is valid
    $sql = "SELECT vt.customer_id, vt.expiry 
            FROM verification_tokens vt 
            JOIN customer_account ca ON vt.customer_id = ca.customer_id 
            WHERE ca.email = ? AND vt.token = ? 
            AND NOT EXISTS (
                SELECT 1 FROM customer_account 
                WHERE customer_id = vt.customer_id AND verified = 1
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $current_time = date('Y-m-d H:i:s');
        
        if ($current_time <= $row['expiry']) {
            // Update user as verified
            $update_sql = "UPDATE customer_account SET verified = 1 WHERE customer_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $row['customer_id']);
            
            // Mark token as used
            $token_sql = "UPDATE verification_tokens SET used = 1 WHERE customer_id = ? AND token = ?";
            $token_stmt = $conn->prepare($token_sql);
            $token_stmt->bind_param("is", $row['customer_id'], $verification_code);

            if ($update_stmt->execute() && $token_stmt->execute()) {
                $success = "Email verified successfully! You can now login to your account.";
            } else {
                $error = "Error updating verification status.";
            }
        } else {
            $error = "Verification code has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid verification code or email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --error-color: #ff4444;
            --success-color: #4CAF50;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .verification-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        .error-message {
            color: var(--error-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .success-message {
            color: var(--success-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h1>Email Verification</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?>
                <div class="login-link">
                    <a href="index.php">Click here to login</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" required>
                </div>
                <button type="submit">Verify Email</button>
            </form>
            <div class="login-link">
                <a href="index.php">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
