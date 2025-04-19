<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';

// Validate the data
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// Admin email address
$admin_email = "admin@example.com"; // Replace with the actual admin email

// Prepare email content
$email_content = "New Contact Form Submission\n\n";
$email_content .= "Name: " . $name . "\n";
$email_content .= "Email: " . $email . "\n";
$email_content .= "Subject: " . $subject . "\n";
$email_content .= "Message:\n" . $message . "\n";

// Email headers
$headers = "From: " . $email . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Try to send the email
if (mail($admin_email, "Contact Form: " . $subject, $email_content, $headers)) {
    // Send success response
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    // Send error response
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email']);
}
?>
