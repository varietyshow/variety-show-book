<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];
        $first_name = $_SESSION['first_name'];

        // First, verify the current password
        $stmt = $conn->prepare("SELECT password FROM admin_account WHERE first_name = ?");
        $stmt->bind_param("s", $first_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Check if current password matches directly
        if (!$user || $current_password !== $user['password']) {
            $_SESSION['msg'] = "Current password is incorrect.";
            header("Location: admin-profile.php");
            exit();
        }

        // Verify that new password and confirm password match
        if ($new_password !== $confirm_password) {
            $_SESSION['msg'] = "New passwords do not match.";
            header("Location: admin-profile.php");
            exit();
        }

        // Update the password without hashing
        $update_stmt = $conn->prepare("UPDATE admin_account SET password = ? WHERE first_name = ?");
        $update_stmt->bind_param("ss", $new_password, $first_name);
        
        if ($update_stmt->execute()) {
            $_SESSION['msg'] = "Password updated successfully!";
        } else {
            $_SESSION['msg'] = "Error updating password.";
        }

        $conn->close();
        header("Location: admin-profile.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['msg'] = "An error occurred: " . $e->getMessage();
        header("Location: admin-profile.php");
        exit();
    }
}
?> 
