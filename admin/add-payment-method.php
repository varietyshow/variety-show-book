<?php
// Start the session
session_start();

// Database configuration
$servername = "localhost"; // Change as needed
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "db_booking_system"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $paymentMethod = $_POST['paymentMethod'];
    $mobileNumbers = $_POST['mobileNumbers'];
    $name = $_POST['name'];
    
    // Handle the file upload (QR code)
    if (isset($_FILES['qr_upload']) && $_FILES['qr_upload']['error'] == 0) {
        $targetDir = "../images/"; // Ensure this directory exists
        $targetFile = $targetDir . basename($_FILES['qr_upload']['name']);
        move_uploaded_file($_FILES['qr_upload']['tmp_name'], $targetFile);
        
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO billing (payment_method, mobile_number, name, qr_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $paymentMethod, $mobileNumbers, $name, $targetFile);

        // Execute the statement
        if ($stmt->execute()) {
            // Store the success message in session
            $_SESSION['msg'] = "New payment method added successfully";
        } else {
            // Optionally store error messages
            $_SESSION['msg'] = "Error: " . $stmt->error;
        }

        $stmt->close();
        
        // Redirect to admin-profile.php
        header("Location: admin-profile.php");
        exit();
    } else {
        $_SESSION['msg'] = "Error uploading file.";
        header("Location: admin-profile.php");
        exit();
    }
}

$conn->close();
?>