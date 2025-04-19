<?php
session_start();
require_once 'db_connect.php';

$bookingDetails = isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null;

if ($bookingDetails) {
    // Update booking status in database
    $stmt = $conn->prepare("UPDATE booking_report SET status = 'Confirmed', payment_status = 'Paid' WHERE booking_reference = ?");
    $stmt->bind_param("s", $bookingDetails['booking_reference']);
    $stmt->execute();

    // Clear the pending booking session
    unset($_SESSION['pending_booking']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .success-icon {
            color: #28a745;
            font-size: 60px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="mb-4">Payment Successful!</h2>
            <p class="mb-4">Your booking has been confirmed. Thank you for your payment.</p>
            <p class="text-muted mb-4">Booking Reference: <?php echo $bookingDetails['booking_reference'] ?? 'N/A'; ?></p>
            <div class="d-grid gap-2">
                <a href="customer-appointment.php" class="btn btn-primary">View My Bookings</a>
                <a href="customer-booking.php" class="btn btn-outline-secondary">Make Another Booking</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>