<?php
$error = isset($_GET['error']) ? $_GET['error'] : 'Unknown error';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Failed</title>
</head>
<body>
    <h1>Booking Failed</h1>
    <p><?php echo htmlspecialchars($error); ?></p>
    <a href="customer-booking.php">Try Again</a>
</body>
</html>
