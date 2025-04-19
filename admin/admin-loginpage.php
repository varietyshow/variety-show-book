<?php
session_start();
include 'db_connect.php'; // Include the database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT password, first_name FROM admin_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Check if the user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stored_password, $first_name);
        $stmt->fetch();

        // Compare the passwords
        if ($password === $stored_password) {
            // Password is correct, set session variables or redirect as needed
            $_SESSION['first_name'] = $first_name; // Store first_name in session
            header("Location: admin-appointments.php"); // Redirect to a welcome page
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "No account found with that username";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Login</title>
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

        .container {
            width: 100%;
            max-width: 420px;
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

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }

        input[type="text"], 
        input[type="password"],
        input[type="submit"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
        }

        input[type="text"], 
        input[type="password"] {
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, 
        input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            padding-right: 40px; /* Make room for the eye icon */
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .forgot-password {
            text-align: right;
            margin: 10px 0 20px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        input[type="submit"] {
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

        input[type="submit"]:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .error-message {
            color: var(--error-color);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 2px;
            font-size: 14px;
            text-align: left;
        }

        /* Animation for form elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        form > * {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Responsive Styles */
        @media (max-width: 480px) {
            body {
                padding: 10px;
                height: auto;
                min-height: 100vh;
            }

            .container {
                padding: 10px;
                margin: 10px auto;
            }

            form {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            input[type="text"], 
            input[type="password"] {
                padding: 10px 12px;
                font-size: 16px;
            }

            .password-container {
                margin-bottom: 5px;
            }

            .forgot-password {
                margin: 8px 0 15px;
            }
        }

        /* Add styles for very small screens */
        @media (max-width: 320px) {
            .container {
                padding: 5px;
            }

            form {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            input[type="submit"] {
                padding: 8px 16px;
            }
        }

        /* Add styles to handle height issues */
        @media (max-height: 600px) {
            body {
                height: auto;
                padding: 20px 10px;
            }

            .container {
                margin: 10px auto;
            }

            form {
                padding: 20px;
            }

            h1 {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <form action="admin-loginpage.php" method="post">
            <h1>Manager Login</h1>
            <?php
            if (!empty($error)) {
                echo "<div class='error-message'>$error</div>";
            }
            ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password')"></i>
                </div>
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
            </div>
            
            <input type="submit" value="Login">
        </form>
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
    </script>
</body>
</html>
