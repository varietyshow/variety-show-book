<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: customer-loginpage.php");
    exit();
}

// Database connection
$host = "sql12.freesqldatabase.com";
$dbname = "sql12777569";
$username = "sql12777569";
$password = "QlgHSeuU1n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to validate password strength
function isPasswordStrong($password) {
    return preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Verify if new passwords match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['update_message'] = [
            'type' => 'danger',
            'text' => 'New passwords do not match!'
        ];
        header("Location: customer-profile.php");
        exit();
    }

    // Validate password strength
    if (!isPasswordStrong($newPassword)) {
        $_SESSION['update_message'] = [
            'type' => 'danger',
            'text' => 'New password does not meet the requirements!'
        ];
        header("Location: customer-profile.php");
        exit();
    }

    try {
        // First verify current password
        $stmt = $pdo->prepare("SELECT password FROM customer_account WHERE username = ?");
        $stmt->execute([$_SESSION['username']]);
        $user = $stmt->fetch();

        // Direct password comparison
        if ($user && $currentPassword === $user['password']) {
            // Current password is correct, proceed with update
            $updateStmt = $pdo->prepare("UPDATE customer_account SET password = ? WHERE username = ?");
            $updateStmt->execute([$newPassword, $_SESSION['username']]);

            $_SESSION['update_message'] = [
                'type' => 'success',
                'text' => 'Password updated successfully!'
            ];
        } else {
            $_SESSION['update_message'] = [
                'type' => 'danger',
                'text' => 'Current password is incorrect!'
            ];
        }
    } catch(PDOException $e) {
        $_SESSION['update_message'] = [
            'type' => 'danger',
            'text' => 'Error updating password. Please try again.'
        ];
    }
    
    header("Location: customer-profile.php");
    exit();
} 
