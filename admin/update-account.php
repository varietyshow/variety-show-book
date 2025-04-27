<?php
session_start();

// Database configuration
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12775634';
$username = 'sql12775634';
$password = 'kPZFb8pXsU';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $firstName = htmlspecialchars($_POST['firstName']);
    $lastName = htmlspecialchars($_POST['lastName']);
    $contactNumber = htmlspecialchars($_POST['contact_number']);
    $email = htmlspecialchars($_POST['email']);

    // Get the admin_id from session (assuming you store it during login)
    $first_name = $_SESSION['first_name'];

    // Update the database
    $sql = "UPDATE admin_account 
            SET first_name = ?, 
                last_name = ?, 
                contact_number = ?,
                email = ?
            WHERE first_name = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstName, $lastName, $contactNumber, $email, $first_name);

    if ($stmt->execute()) {
        // Update successful
        $_SESSION['first_name'] = $firstName; // Update session with new first name
        $_SESSION['msg'] = "Profile updated successfully!";
    } else {
        $_SESSION['msg'] = "Error updating profile: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // Redirect back to profile page
    header("Location: admin-profile.php");
    exit();
}
?> 
