<?php
session_start();
include 'db_connect.php';

$message = '';
$account_found = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['contact_number'])) {
        $contact_number = $_POST['contact_number'];
        
        // Check if contact number exists in database
        $stmt = $conn->prepare("SELECT customer_id FROM customer_account WHERE contact_number = ?");
        $stmt->bind_param("s", $contact_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Store the contact number in session for the next step
            $_SESSION['reset_contact_number'] = $contact_number;
            $account_found = true;
            $message = "Account found! You can now proceed to reset your password.";
        } else {
            $message = "No account found with that contact number.";
        }
        $stmt->close();
    }

    // Handle the reset password request
    if (isset($_POST['proceed_reset'])) {
        $contact_number = $_SESSION['reset_contact_number'];
        
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $updateStmt = $conn->prepare("UPDATE customer_account SET reset_token = ?, reset_expiry = ? WHERE contact_number = ?");
        $updateStmt->bind_param("sss", $token, $expiry, $contact_number);
        
        if ($updateStmt->execute()) {
            $_SESSION['reset_token'] = $token;
            header("Location: reset-password.php?token=" . $token);
            exit();
        } else {
            $message = "Error processing request. Please try again.";
        }
        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --text-color: #333;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: var(--text-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .message {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            input[type="text"] {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'No account') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$account_found): ?>
        <!-- Step 1: Contact Number Verification Form -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="contact_number" name="contact_number" required 
                           placeholder="Enter your registered contact number">
                </div>
            </div>
            <button type="submit">
                <i class="fas fa-search"></i> Find Account
            </button>
        </form>
        
        <?php else: ?>
        <!-- Step 2: Proceed to Reset Password -->
        <form method="POST" action="">
            <input type="hidden" name="proceed_reset" value="1">
            <button type="submit">
                <i class="fas fa-key"></i> Proceed to Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="customer-loginpage.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html> 