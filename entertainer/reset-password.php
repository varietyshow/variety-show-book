<?php
session_start();
include 'db_connect.php';

$message = '';
$validToken = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check expiry
    $stmt = $conn->prepare("SELECT entertainer_id FROM entertainer_account WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
    } else {
        $message = "Invalid or expired reset link.";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Add password validation
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    
    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        $message = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }
    else if ($password === $confirm_password) {
        // Update password and clear reset token
        $updateStmt = $conn->prepare("UPDATE entertainer_account SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
        $updateStmt->bind_param("ss", $password, $token);
        
        if ($updateStmt->execute()) {
            $message = "Password successfully updated. You can now login with your new password.";
            header("refresh:3;url=entertainer-loginpage.php");
        } else {
            $message = "Error updating password. Please try again.";
        }
        $updateStmt->close();
    } else {
        $message = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 500px;
            width: 100%;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .password-container {
            position: relative;
            width: 100%;
            margin-bottom: 5px;
        }

        .password-container input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .password-container input:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 5px rgba(155, 89, 182, 0.3);
        }

        .password-container input.valid-password {
            border-color: #2ecc71;
            box-shadow: 0 0 5px rgba(46, 204, 113, 0.3);
        }

        .password-container input.invalid-password {
            border-color: #e74c3c;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 16px;
            padding: 5px;
        }

        .password-requirements {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 11px;
        }

        .requirement {
            font-size: 10px;
            color: #666;
            margin: 4px 0;
            display: flex;
            align-items: center;
        }

        .requirement i {
            margin-right: 6px;
            font-size: 10px;
        }

        .requirement.valid {
            color: #2ecc71;
        }

        .requirement.invalid {
            color: #e74c3c;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #9b59b6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button[type="submit"]:hover {
            background: #8e44ad;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #9b59b6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #8e44ad;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 15px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 24px;
            }

            .password-container input {
                padding: 10px 35px 10px 12px;
                font-size: 14px;
            }

            .requirement {
                font-size: 10px;
            }

            .requirement i {
                font-size: 11px;
            }

            button[type="submit"] {
                padding: 10px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password')"></i>
                    </div>
                    <div class="password-requirements">
                        Password must contain:
                        <div class="requirement" id="length">
                            <i class="fas fa-times-circle"></i> At least 8 characters
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-times-circle"></i> At least one uppercase letter
                        </div>
                        <div class="requirement" id="lowercase">
                            <i class="fas fa-times-circle"></i> At least one lowercase letter
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-times-circle"></i> At least one number
                        </div>
                        <div class="requirement" id="special">
                            <i class="fas fa-times-circle"></i> At least one special character
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>
                
                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="entertainer-loginpage.php">Back to Login</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling;
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            }
        }

        const passwordInput = document.getElementById('password');
        const requirements = {
            length: document.getElementById('length'),
            uppercase: document.getElementById('uppercase'),
            lowercase: document.getElementById('lowercase'),
            number: document.getElementById('number'),
            special: document.getElementById('special')
        };

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = confirmPasswordInput.value;
            let validRequirements = 0;
            
            // Check length
            if(password.length >= 8) {
                requirements.length.classList.add('valid');
                requirements.length.classList.remove('invalid');
                requirements.length.querySelector('i').classList.remove('fa-times-circle');
                requirements.length.querySelector('i').classList.add('fa-check-circle');
                validRequirements++;
            } else {
                requirements.length.classList.remove('valid');
                requirements.length.classList.add('invalid');
                requirements.length.querySelector('i').classList.add('fa-times-circle');
                requirements.length.querySelector('i').classList.remove('fa-check-circle');
            }

            // Check uppercase
            if(/[A-Z]/.test(password)) {
                requirements.uppercase.classList.add('valid');
                requirements.uppercase.classList.remove('invalid');
                requirements.uppercase.querySelector('i').classList.remove('fa-times-circle');
                requirements.uppercase.querySelector('i').classList.add('fa-check-circle');
                validRequirements++;
            } else {
                requirements.uppercase.classList.remove('valid');
                requirements.uppercase.classList.add('invalid');
                requirements.uppercase.querySelector('i').classList.add('fa-times-circle');
                requirements.uppercase.querySelector('i').classList.remove('fa-check-circle');
            }

            // Check lowercase
            if(/[a-z]/.test(password)) {
                requirements.lowercase.classList.add('valid');
                requirements.lowercase.classList.remove('invalid');
                requirements.lowercase.querySelector('i').classList.remove('fa-times-circle');
                requirements.lowercase.querySelector('i').classList.add('fa-check-circle');
                validRequirements++;
            } else {
                requirements.lowercase.classList.remove('valid');
                requirements.lowercase.classList.add('invalid');
                requirements.lowercase.querySelector('i').classList.add('fa-times-circle');
                requirements.lowercase.querySelector('i').classList.remove('fa-check-circle');
            }

            // Check number
            if(/[0-9]/.test(password)) {
                requirements.number.classList.add('valid');
                requirements.number.classList.remove('invalid');
                requirements.number.querySelector('i').classList.remove('fa-times-circle');
                requirements.number.querySelector('i').classList.add('fa-check-circle');
                validRequirements++;
            } else {
                requirements.number.classList.remove('valid');
                requirements.number.classList.add('invalid');
                requirements.number.querySelector('i').classList.add('fa-times-circle');
                requirements.number.querySelector('i').classList.remove('fa-check-circle');
            }

            // Check special character
            if(/[^A-Za-z0-9]/.test(password)) {
                requirements.special.classList.add('valid');
                requirements.special.classList.remove('invalid');
                requirements.special.querySelector('i').classList.remove('fa-times-circle');
                requirements.special.querySelector('i').classList.add('fa-check-circle');
                validRequirements++;
            } else {
                requirements.special.classList.remove('valid');
                requirements.special.classList.add('invalid');
                requirements.special.querySelector('i').classList.add('fa-times-circle');
                requirements.special.querySelector('i').classList.remove('fa-check-circle');
            }

            // Update password input field color
            if(password.length > 0) {
                if(validRequirements === 5) {
                    this.classList.add('valid-password');
                    this.classList.remove('invalid-password');
                } else {
                    this.classList.add('invalid-password');
                    this.classList.remove('valid-password');
                }
            } else {
                this.classList.remove('valid-password', 'invalid-password');
            }

            // Update confirm password field
            if(confirmPassword.length > 0) {
                if(password === confirmPassword) {
                    confirmPasswordInput.classList.add('valid-password');
                    confirmPasswordInput.classList.remove('invalid-password');
                } else {
                    confirmPasswordInput.classList.add('invalid-password');
                    confirmPasswordInput.classList.remove('valid-password');
                }
            } else {
                confirmPasswordInput.classList.remove('valid-password', 'invalid-password');
            }
        });

        const confirmPasswordInput = document.getElementById('confirm_password');

        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;

            if(confirmPassword.length > 0) {
                if(password === confirmPassword) {
                    this.classList.add('valid-password');
                    this.classList.remove('invalid-password');
                } else {
                    this.classList.add('invalid-password');
                    this.classList.remove('valid-password');
                }
            } else {
                this.classList.remove('valid-password', 'invalid-password');
            }
        });
    </script>
</body>
</html> 