<?php
session_start();
include 'db_connect.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$success_message = ''; // To store success message
$error_message = '';   // To store error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $title = htmlspecialchars($_POST['title']);
    
    // Handle est price (either from dropdown or custom input)
    $est_price = isset($_POST['custom_price']) && !empty($_POST['custom_price']) 
        ? htmlspecialchars($_POST['custom_price']) 
        : htmlspecialchars($_POST['est_price']);
    
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_name = basename($_FILES['image']['name']);
        $image_folder = '../uploads/'; // Changed to use parent directory

        // Create uploads directory if it doesn't exist
        if (!file_exists($image_folder)) {
            mkdir($image_folder, 0777, true);
        }

        // Move the uploaded file to the desired folder
        if (move_uploaded_file($image_tmp_name, $image_folder . $image_name)) {
            // Prepare an insert statement
            $sql = "INSERT INTO entertainer_account (first_name, last_name, title, est_price, username, password, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $first_name, $last_name, $title, $est_price, $username, $password, $image_name);

            if ($stmt->execute()) {
                $success_message = "New entertainer added successfully!";
                // Add JavaScript redirect after success
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'admin-entertainer.php';
                    }, 2000); // 2000 milliseconds = 2 seconds
                </script>";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error uploading the image.";
        }
    } else {
        $error_message = "No image uploaded or there was an error.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entertainer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --error-color: #ff4444;
            --success-color: #28a745;
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
            max-width: 600px;
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

        .message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            color: white;
        }

        .success {
            background-color: var(--success-color);
        }

        .error {
            background-color: var(--error-color);
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
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        #imagePreview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin: 10px 0;
            border-radius: 8px;
            display: none;
            border: 2px solid var(--border-color);
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            color: var(--text-color);
            background-color: white;
            margin-top: 5px;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }

        button {
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

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            form {
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="POST" action="add-entertainer.php" enctype="multipart/form-data">
            <h1>Add New Entertainer</h1>

            <?php if ($success_message): ?>
                <div class="message success"><?= $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="message error"><?= $error_message; ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="image">Upload Image</label>
                <img id="imagePreview" src="#" alt="Image Preview">
                <input type="file" name="image" accept="image/*" required onchange="previewImage(event)">
            </div>
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label for="est_price">Est Price</label>
                <select name="est_price" id="est_price" onchange="toggleCustomPrice()" required>
                    <option value="">Select Price Range</option>
                    <option value="500 - 1000">₱500 - ₱1000</option>
                    <option value="1000 - 5000">₱1000 - ₱5000</option>
                    <option value="2500 - 8000">₱2500 - ₱8000</option>
                    <option value="5000 - 10000">₱5000 - ₱10000</option>
                    <option value="custom">Custom Price Range</option>
                </select>
                <div id="custom_price_container" style="display: none; margin-top: 10px;">
                    <input type="text" id="custom_price" name="custom_price" placeholder="Enter price range (e.g. 1000 - 3000)" style="width: 100%;">
                </div>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Add Entertainer</button>
            <a href="admin-entertainer.php" class="back-link">Back to Entertainer List</a>
        </form>
    </div>

    <script>
        function previewImage(event) {
            const imagePreview = document.getElementById('imagePreview');
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = "";
                imagePreview.style.display = 'none';
            }
        }

        function toggleCustomPrice() {
            const select = document.getElementById('est_price');
            const customContainer = document.getElementById('custom_price_container');
            const customInput = document.getElementById('custom_price');
            
            if (select.value === 'custom') {
                customContainer.style.display = 'block';
                customInput.required = true;
                select.name = ''; // Remove the name attribute from select
            } else {
                customContainer.style.display = 'none';
                customInput.required = false;
                customInput.value = ''; // Clear the custom input
                select.name = 'est_price'; // Restore the name attribute to select
            }
        }
    </script>
</body>
</html>