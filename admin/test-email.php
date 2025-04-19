<?php
require_once '../includes/mail-config.php';

// Database connection
$host = "localhost";
$dbname = "db_booking_system";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get a customer email
    $stmt = $pdo->query("SELECT email, first_name FROM customer_account LIMIT 1");
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get an entertainer email
    $stmt = $pdo->query("SELECT email, first_name FROM entertainer_account LIMIT 1");
    $entertainer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        // Send test email to customer
        $customerSubject = "Test Email - Customer";
        $customerBody = "Hello " . $customer['first_name'] . ",<br><br>" .
                      "This is a test email from your Variety Show Booking System.<br><br>" .
                      "You will receive similar emails when your appointments are approved, declined, or cancelled.<br><br>" .
                      "Thank you for using our service!";
        
        $result = sendEmail($customer['email'], $customerSubject, $customerBody);
        echo "Customer email test result: " . ($result ? "Success" : "Failed") . "<br>";
    }

    if ($entertainer) {
        // Send test email to entertainer
        $entertainerSubject = "Test Email - Entertainer";
        $entertainerBody = "Hello " . $entertainer['first_name'] . ",<br><br>" .
                         "This is a test email from your Variety Show Booking System.<br><br>" .
                         "You will receive similar emails when you have new approved appointments or when appointments are declined/cancelled.<br><br>" .
                         "Thank you for being part of our service!";
        
        $result = sendEmail($entertainer['email'], $entertainerSubject, $entertainerBody);
        echo "Entertainer email test result: " . ($result ? "Success" : "Failed") . "<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
