<?php
session_start();
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php");
    exit();
}

$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12775634';
$username = 'sql12775634';
$password = 'kPZFb8pXsU';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remember to sanitize and validate your data
    $firstName = $conn->real_escape_string(trim($_POST['firstName']));
    $lastName = $conn->real_escape_string(trim($_POST['lastName']));
    $contactNumber = $conn->real_escape_string(trim($_POST['contact_number']));
    $street = $conn->real_escape_string(trim($_POST['street']));
    $barangay = $conn->real_escape_string(trim($_POST['barangay']));
    $municipality = $conn->real_escape_string(trim($_POST['municipality']));
    $province = $conn->real_escape_string(trim($_POST['province']));
    $facebook = $conn->real_escape_string(trim($_POST['facebook']));
    $instagram = $conn->real_escape_string(trim($_POST['instagram']));

    // Get the current user's first name from session to identify the record to update
    $currentFirstName = $_SESSION['first_name'];

    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("UPDATE entertainer_account SET first_name=?, last_name=?, contact_number=?, street=?, barangay=?, municipality=?, province=?, facebook_acc=?, instagram_acc=? WHERE first_name=?");
    $stmt->bind_param("ssssssssss", $firstName, $lastName, $contactNumber, $street, $barangay, $municipality, $province, $facebook, $instagram, $currentFirstName);

    if ($stmt->execute()) {
        // Successfully updated
        $_SESSION['update_message'] = "Account updated successfully!";
        // Optionally update the session
        $_SESSION['first_name'] = $firstName; // Update session variable if needed
        header("Location: entertainer-profile.php");
        exit();
    } else {
        echo "Error updating account: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
