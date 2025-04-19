<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: customer-loginpage.php");
    exit();
}

require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $contactNumber = $_POST['contactNumber'];
    $email = $_POST['email'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $municipality = $_POST['municipality'];
    $province = $_POST['province'];
    $username = $_SESSION['username'];

    try {
        $stmt = $conn->prepare("UPDATE customer_account 
                              SET first_name = ?, 
                                  last_name = ?, 
                                  contact_number = ?,
                                  email = ?,
                                  street = ?, 
                                  barangay = ?, 
                                  municipality = ?, 
                                  province = ? 
                              WHERE username = ?");
        
        $stmt->bind_param("sssssssss", $firstName, $lastName, $contactNumber, $email, $street, $barangay, $municipality, $province, $username);
        
        if ($stmt->execute()) {
            $_SESSION['update_message'] = [
                'type' => 'success',
                'text' => 'Profile updated successfully!'
            ];
        } else {
            $_SESSION['update_message'] = [
                'type' => 'danger',
                'text' => 'Failed to update profile.'
            ];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Check if the error is due to duplicate email
        if ($e->getCode() == 1062) {
            $_SESSION['update_message'] = [
                'type' => 'danger',
                'text' => 'This email address is already in use.'
            ];
        } else {
            $_SESSION['update_message'] = [
                'type' => 'danger',
                'text' => 'An error occurred while updating the profile.'
            ];
        }
    }

    header("Location: customer-profile.php");
    exit();
}
?>