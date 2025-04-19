<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Failed</title>
    <!-- Add your CSS and other head elements -->
</head>
<body>
    <div class="container">
        <div class="alert alert-danger">
            <?php 
            echo isset($_SESSION['payment_message']) 
                ? htmlspecialchars($_SESSION['payment_message']) 
                : 'Your booking could not be completed.';
            // Clear the message after displaying
            unset($_SESSION['payment_message']);
            ?>
        </div>
        <a href="customer-appointment.php" class="btn btn-primary">Return to Appointments</a>
    </div>
</body>
</html>