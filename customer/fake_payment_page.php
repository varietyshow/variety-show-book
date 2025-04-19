<?php
session_start();
require_once 'db_connect.php';

// Get booking details from session
$bookingDetails = isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null;

if (!$bookingDetails) {
    header('Location: customer-booking.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .payment-logo {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <div class="text-center mb-4">
                <h3>Complete Your Payment</h3>
                <p class="text-muted">Booking Reference: <?php echo $bookingDetails['booking_reference'] ?? 'N/A'; ?></p>
            </div>

            <form id="fakePaymentForm" onsubmit="return processPayment(event)">
                <div class="mb-3">
                    <label for="phoneNumber" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phoneNumber" required 
                           pattern="(09|\+639)\d{9}" 
                           placeholder="Enter GCash number (e.g., 09123456789)">
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Amount to Pay</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="amount" 
                               value="<?php echo $bookingDetails['down_payment'] ?? '0'; ?>" 
                               required min="1" step="0.01">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="payButton">
                        Process Payment
                    </button>
                    <a href="customer-booking.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function processPayment(event) {
            event.preventDefault();
            
            const button = document.getElementById('payButton');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Simulate payment processing delay
            setTimeout(() => {
                // Simulate 90% success rate
                const isSuccess = Math.random() < 0.9;

                if (isSuccess) {
                    // Redirect to success page
                    window.location.href = 'payment_success.php';
                } else {
                    alert('Payment failed. Please try again.');
                    button.disabled = false;
                    button.innerHTML = 'Process Payment';
                }
            }, 2000); // 2 second delay

            return false;
        }

        // Phone number formatting
        document.getElementById('phoneNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '0' + value.substr(2);
            }
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>