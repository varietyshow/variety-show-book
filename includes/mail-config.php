<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    error_log("[Email] Starting email send process to: " . $to);
    
    // Check if email is empty or invalid
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[Email] Invalid or empty email address: " . $to);
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Enable verbose debug output
        $mail->SMTPDebug = 3; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("[PHPMailer Debug][$level] $str");
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'varietyshow.booking@gmail.com';
        $mail->Password = 'rkiazwieuyphpvqw';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Additional SMTP settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set timeout
        $mail->Timeout = 60;
        
        error_log("[Email] SMTP settings configured");

        // Clear any existing recipients
        $mail->clearAddresses();
        $mail->clearAllRecipients();

        // Recipients
        $mail->setFrom('varietyshow.booking@gmail.com', 'Variety Show Booking System');
        $mail->addAddress($to);

        error_log("[Email] Recipients configured - To: " . $to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        error_log("[Email] Email content set - Subject: " . $subject);

        // Try to send
        $result = $mail->send();
        if ($result) {
            error_log("[Email] Successfully sent email to: " . $to);
            return true;
        } else {
            error_log("[Email] Failed to send email. PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    } catch (Exception $e) {
        error_log("[Email] Exception caught: " . $e->getMessage());
        error_log("[Email] Full exception: " . print_r($e, true));
        return false;
    }
}
