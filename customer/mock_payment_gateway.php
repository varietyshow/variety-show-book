<?php
// Mock Payment Gateway - Simulates the Magpie payment interface
require_once 'db_connect.php';

// Get parameters from URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';
$payment_method = isset($_GET['method']) ? $_GET['method'] : 'gcash';
$charge_id = isset($_GET['charge_id']) ? $_GET['charge_id'] : '';
$payment_reference = isset($_GET['ref']) ? $_GET['ref'] : '';
$amount = 0;

// Get payment amount from booking
if (!empty($booking_id)) {
    $bookingQuery = "SELECT * FROM booking_report WHERE book_id = ?";
    $stmt = $conn->prepare($bookingQuery);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        $amount = $booking['down_payment'];
    }
}

// Format amount for display
$formatted_amount = number_format($amount, 2);

// Generate success URL
$success_url = "payment-confirmation.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
$cancel_url = "customer-booking.php";

// Reference number
$reference_number = strtoupper(substr($payment_reference, 4, 6));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magpie Payment Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #00bfa5;
            color: white;
            padding: 10px 0;
            text-align: center;
        }
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            display: flex;
            justify-content: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 20px;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        .step.active .step-number {
            background-color: #00bfa5;
        }
        .step-text {
            font-size: 12px;
            color: #666;
        }
        .step.active .step-text {
            color: #00bfa5;
            font-weight: bold;
        }
        .payment-amount {
            text-align: center;
            padding: 30px 0;
            background-color: #f8f9fa;
        }
        .amount-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        .currency {
            color: #00bfa5;
            font-weight: normal;
        }
        .payment-methods {
            padding: 20px;
        }
        .method-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .methods-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .method-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .method-card:hover, .method-card.selected {
            border-color: #00bfa5;
            box-shadow: 0 0 5px rgba(0, 191, 165, 0.3);
        }
        .method-card.selected {
            background-color: rgba(0, 191, 165, 0.05);
        }
        .method-icon {
            height: 40px;
            margin-bottom: 10px;
        }
        .method-name {
            font-size: 14px;
            color: #333;
        }
        .reference {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            color: #666;
            font-size: 12px;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            border-top: 1px solid #eee;
        }
        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
        }
        .btn-pay {
            background-color: #00bfa5;
            border: none;
            color: white;
        }
        .btn-pay:hover {
            background-color: #00a895;
        }
    </style>
</head>
<body>
    <div class="header">
        <h5>Magpie Payment Gateway</h5>
    </div>
    
    <div class="payment-container">
        <div class="reference">
            Reference Number: <?php echo $reference_number; ?>
        </div>
        
        <div class="progress-bar">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-text">Payment Information</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Billing Details</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Summary</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-text">Payment</div>
            </div>
        </div>
        
        <div class="payment-amount">
            <div class="amount-text">A great way to spend money!</div>
            <div class="amount"><span class="currency">₱</span><?php echo $formatted_amount; ?></div>
            <div class="amount-text">Amount to Pay</div>
        </div>
        
        <div class="payment-methods">
            <div class="method-title">SELECT PAYMENT METHOD</div>
            <div class="methods-grid">
                <div class="method-card" data-method="card">
                    <img src="https://www.pngitem.com/pimgs/m/5-50862_visa-mastercard-logo-png-transparent-png.png" alt="Credit/Debit Card" class="method-icon">
                    <div class="method-name">Credit/Debit Card</div>
                </div>
                <div class="method-card selected" data-method="gcash">
                    <img src="https://www.gcash.com/wp-content/uploads/2019/04/gcash-logo.png" alt="GCash" class="method-icon">
                    <div class="method-name">GCash</div>
                </div>
                <div class="method-card" data-method="grabpay">
                    <img src="https://www.grab.com/wp-content/uploads/media/grabpay-logo.png" alt="GrabPay" class="method-icon">
                    <div class="method-name">GrabPay</div>
                </div>
                <div class="method-card" data-method="otc">
                    <img src="https://www.coins.ph/wp-content/uploads/2018/10/coins-ph-logo.png" alt="OTC or coins.ph" class="method-icon">
                    <div class="method-name">OTC or coins.ph</div>
                </div>
                <div class="method-card" data-method="maya">
                    <img src="https://www.paymaya.com/wp-content/uploads/2019/07/paymaya-logo.png" alt="Maya" class="method-icon">
                    <div class="method-name">Maya</div>
                </div>
                <div class="method-card" data-method="bpi">
                    <img src="https://www.bpi.com.ph/content/dam/bpi/images/logos/bpi-logo.png" alt="BPI Online" class="method-icon">
                    <div class="method-name">BPI Online</div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="<?php echo $cancel_url; ?>" class="btn btn-cancel">Cancel</a>
            <a href="<?php echo $success_url; ?>" class="btn btn-pay" id="payButton">Pay Now</a>
        </div>
    </div>
    
    <script>
        // Add click event to payment method cards
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.method-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Update payment method in success URL
                const method = this.getAttribute('data-method');
                const payButton = document.getElementById('payButton');
                const currentUrl = payButton.getAttribute('href');
                
                // Replace the method parameter in the URL
                const newUrl = currentUrl.replace(/method=[^&]+/, `method=${method}`);
                payButton.setAttribute('href', newUrl);
            });
        });
        
        // Add loading animation when Pay Now button is clicked
        document.getElementById('payButton').addEventListener('click', function(e) {
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            this.classList.add('disabled');
            // Allow the redirect to happen after a short delay to show the processing state
            setTimeout(() => {
                return true;
            }, 1500);
        });
    </script>
</body>
</html>
