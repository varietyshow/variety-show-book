<?php
// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', '../error_log.txt');

// For debugging - log all requests
error_log("[CANCEL] Request received: " . json_encode($_POST));

// Turn off error display for production - this prevents PHP errors from breaking JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Helper function to send JSON response and exit
function sendJsonResponse($success, $message) {
    // Log the response for debugging
    error_log("[CANCEL] Sending response: success=" . ($success ? 'true' : 'false') . ", message=" . $message);
    
    // Clear any output that might have been generated
    ob_end_clean();
    
    // Set proper content type
    header('Content-Type: application/json');
    
    // Output JSON response
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    sendJsonResponse(false, 'Not logged in');
}

// Check if required parameters are present
if (!isset($_POST['appointment_id']) || !isset($_POST['reason'])) {
    sendJsonResponse(false, 'Missing required parameters');
}

// Include mail configuration
require_once '../includes/mail-config.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_booking_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    sendJsonResponse(false, 'Database connection failed');
}

$appointment_id = intval($_POST['appointment_id']);
$reason = mysqli_real_escape_string($conn, $_POST['reason']);
$customer_id = $_SESSION['customer_id'];

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Check current status with row lock
    $check_sql = "SELECT br.*, ca.first_name as customer_fname, ca.last_name as customer_lname 
                FROM booking_report br 
                JOIN customer_account ca ON br.customer_id = ca.customer_id 
                WHERE br.book_id = ? AND br.customer_id = ? 
                AND LOWER(br.status) IN ('pending', 'approve', 'approved')
                FOR UPDATE"; // This locks the row
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        // Either the appointment doesn't exist, is already cancelled, or belongs to another customer
        $conn->rollback();
        if (isset($check_stmt)) $check_stmt->close();
        $conn->close();
        sendJsonResponse(false, 'Invalid appointment or already cancelled');
    }

    $appointment_details = $result->fetch_assoc();

    // Update the appointment status
    $update_sql = "UPDATE booking_report SET status = 'Cancelled', reason = ? WHERE book_id = ? AND customer_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $reason, $appointment_id, $customer_id);

    if ($update_stmt->execute()) {
        // Get admin email
        $admin_sql = "SELECT email FROM admin_account WHERE admin_id = 1";
        $admin_result = $conn->query($admin_sql);
        
        // Try to send email, but don't let email failures prevent cancellation
        try {
            if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
                $admin_email = $admin_row['email'];
                
                // Prepare email content
                $subject = "Appointment Cancellation Notification";
                $body = "Dear Admin,<br><br>";
                $body .= "An appointment has been cancelled. Here are the details:<br><br>";
                $body .= "<b>Appointment ID:</b> " . $appointment_id . "<br>";
                $body .= "<b>Customer Name:</b> " . $appointment_details['customer_fname'] . " " . $appointment_details['customer_lname'] . "<br>";
                $body .= "<b>Date:</b> " . $appointment_details['date_schedule'] . "<br>";
                $body .= "<b>Time:</b> " . $appointment_details['time_start'] . " - " . $appointment_details['time_end'] . "<br>";
                $body .= "<b>Entertainer(s):</b> " . $appointment_details['entertainer_name'] . "<br>";
                $body .= "<b>Cancellation Reason:</b> " . $reason . "<br><br>";
                $body .= "Please review this cancellation in your admin dashboard.<br><br>";
                $body .= "Best regards,<br>Booking System";

                // Send email notification - but don't let email failures affect the transaction
                sendEmail($admin_email, $subject, $body);
            }
        } catch (Exception $emailEx) {
            // Log email error but continue with cancellation
            error_log("Email sending failed: " . $emailEx->getMessage());
        }
        
        // Commit the transaction
        $conn->commit();
        
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($update_stmt)) $update_stmt->close();
        $conn->close();
        
        sendJsonResponse(true, 'Appointment cancelled successfully');
    } else {
        // Rollback if update fails
        $conn->rollback();
        
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($update_stmt)) $update_stmt->close();
        $conn->close();
        
        sendJsonResponse(false, 'Failed to cancel appointment');
    }
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    $conn->close();
    
    sendJsonResponse(false, 'An error occurred while cancelling the appointment: ' . $e->getMessage());
}

?>
