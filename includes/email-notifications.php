<?php
require_once 'mail-config.php';

// Function to format time to 12-hour format with AM/PM
function formatTime($time) {
    return date("g:i A", strtotime($time));
}

/**
 * Send email notification to admin for new appointment
 */
function sendNewAppointmentNotification($conn, $appointmentId) {
    // Get admin email
    $admin_sql = "SELECT email FROM admin_account WHERE admin_id = 1";
    $admin_result = $conn->query($admin_sql);
    
    if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
        $admin_email = $admin_row['email'];
        
        // Get appointment details
        $sql = "SELECT br.*, ca.first_name as customer_fname, ca.last_name as customer_lname 
                FROM booking_report br 
                JOIN customer_account ca ON br.customer_id = ca.customer_id 
                WHERE br.book_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($appointment = $result->fetch_assoc()) {
            // Prepare email content
            $subject = "New Appointment Booking Notification";
            $body = "Dear Admin,\n\n";
            $body .= "A new appointment has been booked. Here are the details:\n\n";
            $body .= "Appointment ID: " . $appointmentId . "\n";
            $body .= "Customer Name: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "\n";
            $body .= "Date: " . $appointment['date_schedule'] . "\n";
            $body .= "Time: " . formatTime($appointment['time_start']) . " - " . formatTime($appointment['time_end']) . "\n";
            $body .= "Entertainer(s): " . $appointment['entertainer_name'] . "\n";
            $body .= "Status: " . $appointment['status'] . "\n\n";
            $body .= "Please review this booking in your admin dashboard.\n\n";
            $body .= "Best regards,\nBooking System";

            // Send email notification
            sendEmail($admin_email, $subject, $body);
        }
        
        $stmt->close();
    }
}

/**
 * Send email notification to admin for rescheduled appointment
 */
function sendRescheduleNotification($conn, $appointmentId, $oldDate, $oldTimeStart, $oldTimeEnd) {
    // Get admin email
    $admin_sql = "SELECT email FROM admin_account WHERE admin_id = 1";
    $admin_result = $conn->query($admin_sql);
    
    if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
        $admin_email = $admin_row['email'];
        
        // Get appointment details
        $sql = "SELECT br.*, ca.first_name as customer_fname, ca.last_name as customer_lname 
                FROM booking_report br 
                JOIN customer_account ca ON br.customer_id = ca.customer_id 
                WHERE br.book_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($appointment = $result->fetch_assoc()) {
            // Prepare email content
            $subject = "Appointment Reschedule Notification";
            $body = "Dear Admin,\n\n";
            $body .= "An appointment has been rescheduled. Here are the details:\n\n";
            $body .= "Appointment ID: " . $appointmentId . "\n";
            $body .= "Customer Name: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "\n\n";
            $body .= "Previous Schedule:\n";
            $body .= "Date: " . $oldDate . "\n";
            $body .= "Time: " . formatTime($oldTimeStart) . " - " . formatTime($oldTimeEnd) . "\n\n";
            $body .= "New Schedule:\n";
            $body .= "Date: " . $appointment['date_schedule'] . "\n";
            $body .= "Time: " . formatTime($appointment['time_start']) . " - " . formatTime($appointment['time_end']) . "\n";
            $body .= "Entertainer(s): " . $appointment['entertainer_name'] . "\n";
            $body .= "Status: " . $appointment['status'] . "\n\n";
            $body .= "Please review this rescheduling request in your admin dashboard.\n\n";
            $body .= "Best regards,\nBooking System";

            // Send email notification
            sendEmail($admin_email, $subject, $body);
        }
        
        $stmt->close();
    }
}

/**
 * Send email notification to customer and entertainer when admin updates appointment status
 */
function sendAppointmentStatusNotification($conn, $appointmentId, $status, $reason = '') {
    // Get appointment details with customer and entertainer info
    $sql = "SELECT br.*, 
            ca.first_name as customer_fname, ca.last_name as customer_lname, ca.email as customer_email,
            GROUP_CONCAT(DISTINCT ea.email) as entertainer_emails
            FROM booking_report br 
            JOIN customer_account ca ON br.customer_id = ca.customer_id 
            LEFT JOIN booking_entertainers be ON br.book_id = be.book_id
            LEFT JOIN entertainer_account ea ON be.entertainer_id = ea.entertainer_id
            WHERE br.book_id = ?
            GROUP BY br.book_id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($appointment = $result->fetch_assoc()) {
        // Send email to customer
        $subject = "Appointment Status Update";
        $body = "Dear " . $appointment['customer_fname'] . ",\n\n";
        $body .= "Your appointment has been " . strtolower($status) . " by the admin. Here are the details:\n\n";
        $body .= "Appointment ID: " . $appointmentId . "\n";
        $body .= "Date: " . $appointment['date_schedule'] . "\n";
        $body .= "Time: " . formatTime($appointment['time_start']) . " - " . formatTime($appointment['time_end']) . "\n";
        $body .= "Entertainer(s): " . $appointment['entertainer_name'] . "\n";
        $body .= "Status: " . $status . "\n";
        
        if (!empty($reason)) {
            $body .= "Reason: " . $reason . "\n";
        }
        
        if ($status == 'Approved') {
            $body .= "\nPlease make sure to arrive on time for your appointment.\n";
        } elseif ($status == 'Declined') {
            $body .= "\nIf you have any questions, please contact our support team.\n";
        }
        
        $body .= "\nBest regards,\nBooking System";
        
        sendEmail($appointment['customer_email'], $subject, $body);
        
        // Send email to entertainers if approved
        if ($status == 'Approved' && !empty($appointment['entertainer_emails'])) {
            $entertainerEmails = explode(',', $appointment['entertainer_emails']);
            
            foreach ($entertainerEmails as $entertainerEmail) {
                $subject = "New Approved Appointment";
                $body = "Dear Entertainer,\n\n";
                $body .= "You have a new approved appointment. Here are the details:\n\n";
                $body .= "Appointment ID: " . $appointmentId . "\n";
                $body .= "Customer: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "\n";
                $body .= "Date: " . $appointment['date_schedule'] . "\n";
                $body .= "Time: " . formatTime($appointment['time_start']) . " - " . formatTime($appointment['time_end']) . "\n";
                $body .= "Location: " . $appointment['street'] . ", " . $appointment['barangay'] . ", " . 
                        $appointment['municipality'] . ", " . $appointment['province'] . "\n";
                if (!empty($appointment['roles'])) {
                    $body .= "Your Role(s): " . $appointment['roles'] . "\n";
                }
                $body .= "\nPlease make sure to arrive on time for the appointment.\n\n";
                $body .= "Best regards,\nBooking System";
                
                sendEmail(trim($entertainerEmail), $subject, $body);
            }
        }
    }
    
    $stmt->close();
}
?>
