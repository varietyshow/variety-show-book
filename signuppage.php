<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --error-color: #ff4444;
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
            box-sizing: border-box;
        }

        .registration-container {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            margin: 20px auto;
            box-sizing: border-box;
        }

        form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
        }

        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
            border-radius: 50%;
            object-fit: cover;
        }

        h3 {
            color: var(--text-color);
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .registration-content p {
            text-align: center;
            color: gray;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
            position: relative;
            background: none;
            padding: 0;
        }

        label.required::after {
            content: '*';
            color: red;
            margin-left: 4px;
        }

        input[type="text"],
        input[type="password"],
        input[type="date"],
        input[type="number"],
        input[type="tel"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .registration-form input:focus + label,
        .registration-form input:not(:placeholder-shown) + label,
        .registration-form select:focus + label,
        .registration-form select:not(:placeholder-shown) + label {
            position: relative;
            top: auto;
            left: auto;
            font-size: 14px;
            color: var(--text-color);
            background-color: transparent;
            padding: 0;
        }

        .next-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }

        .next-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .signin-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-color);
        }

        .signin-link a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .signin-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* Verification Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #666;
        }

        .verification-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .verification-message {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }

        .verification-code-input {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .verification-code-input input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: 500;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0;
            -moz-appearance: textfield; /* Remove number spinners in Firefox */
        }

        /* Remove number spinners in Chrome, Safari, Edge, Opera */
        .verification-code-input input::-webkit-outer-spin-button,
        .verification-code-input input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .verification-code-input input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .verify-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .verify-btn:hover {
            background-color: var(--primary-hover);
        }

        .resend-link {
            text-align: center;
            font-size: 14px;
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .registration-container {
                padding: 10px;
            }

            form {
                padding: 20px;
            }
        }

        @media (max-width: 320px) {
            .registration-container {
                padding: 5px;
            }

            form {
                padding: 15px;
            }

            h3 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="color: var(--error-color); text-align: center; margin-bottom: 20px;">
                <?php 
                switch($_GET['error']) {
                    case 'registration_failed':
                        echo "Registration failed. Please try again.";
                        break;
                    case 'username_taken':
                        echo "Username is already taken. Please choose another.";
                        break;
                    case 'contact_taken':
                        echo "Contact number is already registered.";
                        break;
                    default:
                        echo "An error occurred. Please try again.";
                }
                ?>
            </div>
        <?php endif; ?>
        <form class="registration-form" action="register.php" method="post">
            <div class="registration-header">
                <img src="images/logo.jpg" alt="Name Logo" class="logo">
                <h3>Registration</h3>
                <p>Create your account</p>
            </div>

            <!-- Personal Information -->
            <div class="form-group">
                <label for="first_name" class="required">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name" class="required">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="contact_number" class="required">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" pattern="[0-9]{11}" maxlength="11" placeholder="11 digits number" required>
            </div>
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <!-- Account Credentials -->
            <div class="form-group">
                <label for="username" class="required">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password" class="required">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="next-btn">Sign Up</button>
            
            <p class="signin-link">Already have an account? <a href="customer/customer-loginpage.php">Sign in instead</a></p>
        </form>
    </div>

    <!-- Verification Modal -->
    <div class="modal" id="verificationModal">
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <h3 class="verification-title">Email Verification</h3>
            <p class="verification-message">We've sent a verification code to your email address. Please enter the code below to verify your account.</p>
            <form id="verificationForm" action="verify.php" method="post">
                <div class="verification-code-input">
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                </div>
                <input type="hidden" name="verification_code" id="verificationCodeHidden">
                <input type="hidden" name="email" id="verificationEmail">
                <button type="submit" class="verify-btn">Verify Email</button>
            </form>
            <div class="resend-link">
                Didn't receive the code? <a href="#" id="resendCode">Resend</a>
            </div>
        </div>
    </div>

    <script>
        // Handle registration form submission
        document.querySelector('.registration-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get the form data
            const formData = new FormData(this);
            
            // Send the registration request
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show verification modal
                    document.getElementById('verificationModal').classList.add('show');
                    document.getElementById('verificationEmail').value = formData.get('email');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });

        // Handle verification code input
        const codeInputs = document.querySelectorAll('.verification-code-input input');
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value) {
                    if (index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });
        });

        // Handle verification form submission
        document.getElementById('verificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Combine all code inputs
            const code = Array.from(codeInputs).map(input => input.value).join('');
            document.getElementById('verificationCodeHidden').value = code;
            
            // Submit verification
            const formData = new FormData(this);
            fetch('verify.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'customer/customer-loginpage.php?verification=success';
                } else {
                    alert(data.message || 'Verification failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });

        // Close modal
        document.querySelector('.close-modal').addEventListener('click', () => {
            document.getElementById('verificationModal').classList.remove('show');
        });

        // Handle resend code
        document.getElementById('resendCode').addEventListener('click', function(e) {
            e.preventDefault();
            const email = document.getElementById('verificationEmail').value;
            
            fetch('resend_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Verification code has been resent to your email.');
                } else {
                    alert(data.message || 'Failed to resend code. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    </script>
</body>
</html>
