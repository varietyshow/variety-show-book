<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

try {
    $mail = new PHPMailer(true);

    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      
    $mail->isSMTP();                                            
    $mail->Host       = 'smtp.gmail.com';                     
    $mail->SMTPAuth   = true;                                   
    $mail->Username   = 'medyosnob@gmail.com';                     
    $mail->Password   = 'qdxvpyddzdgnyspu';                               
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
    $mail->Port       = 587;                                    

    //Recipients
    $mail->setFrom('medyosnob@gmail.com', 'Test Sender');
    $mail->addAddress('medyosnob@gmail.com', 'Test Receiver');     

    //Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Test Email from Booking System';
    $mail->Body    = 'This is a test email to verify SMTP settings are working.';

    echo "Attempting to send email...<br>";
    $mail->send();
    echo "Message has been sent successfully!";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    error_log("Test email failed: " . $e->getMessage());
}
?>
