<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['appointment_id']) || !isset($_POST['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
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
        echo json_encode(['success' => false, 'message' => 'Invalid appointment or already cancelled']);
        $check_stmt->close();
        $conn->close();
        exit();
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
        if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
            $admin_email = $admin_row['email'];
            
            // Prepare email content
            $subject = "Appointment Cancellation Notification";
            $body = "Dear Admin,\n\n";
            $body .= "An appointment has been cancelled. Here are the details:\n\n";
            $body .= "Appointment ID: " . $appointment_id . "\n";
            $body .= "Customer Name: " . $appointment_details['customer_fname'] . " " . $appointment_details['customer_lname'] . "\n";
            $body .= "Date: " . $appointment_details['date_schedule'] . "\n";
            $body .= "Time: " . $appointment_details['time_start'] . " - " . $appointment_details['time_end'] . "\n";
            $body .= "Entertainer(s): " . $appointment_details['entertainer_name'] . "\n";
            $body .= "Cancellation Reason: " . $reason . "\n\n";
            $body .= "Please review this cancellation in your admin dashboard.\n\n";
            $body .= "Best regards,\nBooking System";

            // Send email notification
            sendEmail($admin_email, $subject, $body);
        }
        
        // Commit the transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
    } else {
        // Rollback if update fails
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
    }
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred while cancelling the appointment']);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    $conn->close();
}
?>