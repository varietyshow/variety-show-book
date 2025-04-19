<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end; /* Aligns content to the right */
            background-color: #f2f2f2;
            padding-right: 20px; /* Adds space from the right edge */
            box-sizing: border-box;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
            box-sizing: border-box;
        }

        h1 {
            margin-bottom: 20px;
        }

        .account-type {
            margin-bottom: 20px;
        }

        .account-type a {
            display: block;
            width: calc(100% - 20px);
            padding: 15px;
            margin: 10px auto;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
            background-color: #007BFF;
            transition: background-color 0.3s, transform 0.3s;
            box-sizing: border-box;
        }

        .account-type a:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login As:</h1>
        <div class="account-type">
            <a href="login-customer.html">Customer</a>
            <a href="login-entertainer.html">Entertainer</a>
            <a href="login-manager.html">Manager</a>
        </div>
    </div>
</body>
</html>
