<?php
session_start();

if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php");
    exit();
}

// Database configuration
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12775634';
$username = 'sql12775634';
$password = 'kPZFb8pXsU';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $first_name = $_SESSION['first_name'];
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];

    // Validate password requirements
    if (strlen($new_password) < 8 ||
        !preg_match("/[A-Z]/", $new_password) ||
        !preg_match("/[a-z]/", $new_password) ||
        !preg_match("/[0-9]/", $new_password) ||
        !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
        throw new Exception("New password does not meet requirements");
    }

    // Check if new password matches confirmation
    if ($new_password !== $confirm_password) {
        throw new Exception("New passwords do not match");
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM entertainer_account WHERE first_name = ?");
    $stmt->bind_param("s", $first_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }

    $user = $result->fetch_assoc();
    
    // Direct password comparison since we're not using hashing
    if ($current_password !== $user['password']) {
        throw new Exception("Current password is incorrect");
    }

    // Update the password with plain text
    $update_stmt = $conn->prepare("UPDATE entertainer_account SET password = ? WHERE first_name = ?");
    $update_stmt->bind_param("ss", $new_password, $first_name);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update password");
    }

    $_SESSION['update_message'] = "Password updated successfully!";
    $_SESSION['active_tab'] = 'security';
    header("Location: entertainer-profile.php");
    exit();

} catch (Exception $e) {
    $_SESSION['update_message'] = "Error: " . $e->getMessage();
    $_SESSION['active_tab'] = 'security';
    header("Location: entertainer-profile.php");
    exit();
}
?> 
