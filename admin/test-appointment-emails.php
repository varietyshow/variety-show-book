<?php
require_once '../includes/mail-config.php';

// Database connection
$host = "sql12.freesqldatabase.com";
$dbname = "sql12775634";
$username = "sql12775634";
$password = "kPZFb8pXsU";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all appointments with email addresses
    $stmt = $pdo->query("SELECT b.*, 
                               c.email as customer_email, 
                               c.first_name as customer_fname,
                               c.last_name as customer_lname,
                               e.email as entertainer_email, 
                               e.first_name as entertainer_fname,
                               e.last_name as entertainer_lname,
                               e.roles as talent_type,
                               b.date_schedule,
                               b.time_start,
                               b.time_end
                        FROM booking_report b 
                        JOIN customer_account c ON b.customer_id = c.customer_id 
                        JOIN entertainer_account e ON b.entertainer_id = e.entertainer_id
                        ORDER BY b.book_id DESC
                        LIMIT 5");
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Appointment Email Test</h2>";
    
    if (empty($appointments)) {
        echo "<p>No appointments found in the database.</p>";
    }
    
    foreach ($appointments as $appointment) {
        echo "<hr>";
        echo "<h3>Appointment ID: " . $appointment['book_id'] . "</h3>";
        echo "<p>Customer: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "</p>";
        echo "<p>Customer Email: " . $appointment['customer_email'] . "</p>";
        echo "<p>Entertainer: " . $appointment['entertainer_fname'] . " " . $appointment['entertainer_lname'] . "</p>";
        echo "<p>Entertainer Email: " . $appointment['entertainer_email'] . "</p>";
        echo "<p>Status: " . $appointment['status'] . "</p>";
        echo "<p>Date: " . $appointment['date_schedule'] . "</p>";
        echo "<p>Time: " . $appointment['time_start'] . " - " . $appointment['time_end'] . "</p>";
        
        // Send test email to customer
        $customerSubject = "Test Email - Appointment Status";
        $customerBody = "Hello " . $appointment['customer_fname'] . ",<br><br>" .
                      "This is a test email regarding your appointment with " . $appointment['entertainer_fname'] . " " . $appointment['entertainer_lname'] . 
                      " scheduled for " . date('F j, Y', strtotime($appointment['date_schedule'])) . 
                      " from " . date('g:i A', strtotime($appointment['time_start'])) .
                      " to " . date('g:i A', strtotime($appointment['time_end'])) . ".<br><br>" .
                      "Current status: " . $appointment['status'] . "<br><br>" .
                      "Services: " . $appointment['talent_type'] . "<br><br>" .
                      "Thank you for using our service!";
        
        echo "<p>Sending test email to customer...</p>";
        $result = sendEmail($appointment['customer_email'], $customerSubject, $customerBody);
        echo $result ? "<p style='color:green'>Email sent to customer</p>" : "<p style='color:red'>Failed to send email to customer</p>";
        
        // Send test email to entertainer
        $entertainerSubject = "Test Email - Appointment Status";
        $entertainerBody = "Hello " . $appointment['entertainer_fname'] . " " . $appointment['entertainer_lname'] . ",<br><br>" .
                         "This is a test email regarding your appointment scheduled for " . 
                         date('F j, Y', strtotime($appointment['date_schedule'])) . 
                         " from " . date('g:i A', strtotime($appointment['time_start'])) .
                         " to " . date('g:i A', strtotime($appointment['time_end'])) . ".<br><br>" .
                         "Client: " . $appointment['customer_fname'] . " " . $appointment['customer_lname'] . "<br><br>" .
                         "Current status: " . $appointment['status'] . "<br><br>" .
                         "Services: " . $appointment['talent_type'] . "<br><br>" .
                         "Thank you for being part of our service!";
        
        echo "<p>Sending test email to entertainer...</p>";
        $result = sendEmail($appointment['entertainer_email'], $entertainerSubject, $entertainerBody);
        echo $result ? "<p style='color:green'>Email sent to entertainer</p>" : "<p style='color:red'>Failed to send email to entertainer</p>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
