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
        $sql = "SELECT br.*, ca.first_name as customer_fname, ca.last_name as customer_lname,
                br.street, br.barangay, br.municipality, br.province
                FROM booking_report br 
                JOIN customer_account ca ON br.customer_id = ca.customer_id 
                WHERE br.book_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($appointment = $result->fetch_assoc()) {
            // Get entertainers for this booking
            $entertainers = [];
            $ent_sql = "SELECT ea.first_name, ea.last_name 
                        FROM booking_entertainers be
                        JOIN entertainer_account ea ON be.entertainer_id = ea.entertainer_id
                        WHERE be.book_id = ?";
            $ent_stmt = $conn->prepare($ent_sql);
            $ent_stmt->bind_param("i", $appointmentId);
            $ent_stmt->execute();
            $ent_result = $ent_stmt->get_result();
            
            while ($ent = $ent_result->fetch_assoc()) {
                $entertainers[] = $ent['first_name'] . ' ' . $ent['last_name'];
            }
            
            $entertainer_list = !empty($entertainers) ? implode(', ', $entertainers) : $appointment['entertainer_name'];
            
            // Format the venue address
            $venue = $appointment['street'];
            if (!empty($appointment['barangay'])) {
                $venue .= ', ' . $appointment['barangay'];
            }
            if (!empty($appointment['municipality'])) {
                $venue .= ', ' . $appointment['municipality'];
            }
            if (!empty($appointment['province'])) {
                $venue .= ', ' . $appointment['province'];
            }
            
            // Format date
            $formatted_date = date('M. d, Y', strtotime($appointment['date_schedule']));
            
            // Prepare email content with HTML formatting
            $subject = "New Appointment Booking Notification";
            
            // HTML email body
            $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { width: 100%; max-width: 600px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 20px; }
                    h2 { color: #444; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    table, th, td { border: 1px solid #ddd; }
                    th, td { padding: 10px; text-align: left; }
                    th { background-color: #f8f8f8; font-weight: bold; width: 40%; }
                    .footer { margin-top: 30px; font-size: 14px; color: #777; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Dear Admin, A new appointment has been booked. Here are the details:</h2>
                    </div>
                    
                    <p><strong>Appointment ID:</strong> ' . $appointmentId . '</p>
                    <p><strong>Date:</strong> ' . $formatted_date . '</p>
                    
                    <table>
                        <tr>
                            <th>Customer name</th>
                            <td>' . $appointment['customer_fname'] . ' ' . $appointment['customer_lname'] . '</td>
                        </tr>
                        <tr>
                            <th>Time</th>
                            <td>' . formatTime($appointment['time_start']) . ' – ' . formatTime($appointment['time_end']) . '</td>
                        </tr>
                        <tr>
                            <th>Entertainer(s)</th>
                            <td>' . $entertainer_list . '</td>
                        </tr>
                        <tr>
                            <th>Venue</th>
                            <td>' . $venue . '</td>
                        </tr>
                    </table>
                    
                    <p>Please review this booking in your admin dashboard.</p>
                    
                    <div class="footer">
                        <p>Best regards,<br>
                        Variety Show Booking System Team</p>
                    </div>
                </div>
            </body>
            </html>';

            // Plain text alternative
            $plainText = "Dear Admin,\n\n";
            $plainText .= "A new appointment has been booked. Here are the details:\n\n";
            $plainText .= "Appointment ID: " . $appointmentId . "\n";
            $plainText .= "Date: " . $formatted_date . "\n";
            $plainText .= "Customer Name: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "\n";
            $plainText .= "Time: " . formatTime($appointment['time_start']) . " - " . formatTime($appointment['time_end']) . "\n";
            $plainText .= "Entertainer(s): " . $entertainer_list . "\n";
            $plainText .= "Venue: " . $venue . "\n\n";
            $plainText .= "Please review this booking in your admin dashboard.\n\n";
            $plainText .= "Best regards,\nVariety Show Booking System Team";

            // Send email notification
            sendEmail($admin_email, $subject, $body, $plainText);
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
