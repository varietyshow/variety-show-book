<?php
// Mock Payment Gateway - Simulates the Magpie payment interface
require_once 'db_connect.php';

// Get parameters from URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';
$payment_method = isset($_GET['method']) ? $_GET['method'] : 'gcash';
$charge_id = isset($_GET['charge_id']) ? $_GET['charge_id'] : '';
$payment_reference = isset($_GET['ref']) ? $_GET['ref'] : '';
$amount = 0;
$step = isset($_GET['step']) ? $_GET['step'] : 1;

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

// Generate URLs for navigation
$success_url = "payment-confirmation.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}&status=success";
$cancel_url = "customer-booking.php";
$step2_url = "mock_payment_gateway.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}&step=2";
$step3_url = "mock_payment_gateway.php?ref={$payment_reference}&method={$payment_method}&booking_id={$booking_id}&charge_id={$charge_id}&step=3";

// Format payment reference for display
$display_reference = !empty($charge_id) ? $charge_id : (!empty($payment_reference) ? $payment_reference : "");

// Get form data from session if available (for step 3)
session_start();
$email = isset($_GET['email']) ? $_GET['email'] : (isset($_SESSION['payment_email']) ? $_SESSION['payment_email'] : '');
$name = isset($_GET['name']) ? $_GET['name'] : (isset($_SESSION['payment_name']) ? $_SESSION['payment_name'] : '');
$phone = isset($_GET['phone']) ? $_GET['phone'] : (isset($_SESSION['payment_phone']) ? $_SESSION['payment_phone'] : '');

// Store form data in session if coming from step 2
if ($step == 3 && isset($_GET['email'])) {
    $_SESSION['payment_email'] = $email;
    $_SESSION['payment_name'] = $name;
    $_SESSION['payment_phone'] = $phone;
}
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
            padding: 5px 0;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .reference-bar {
            background-color: #f8f9fa;
            padding: 5px 15px;
            color: #666;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            min-height: 80vh;
        }
        .progress-steps {
            display: flex;
            justify-content: center;
            padding: 15px 0;
            background-color: white;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }
        .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #ccc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 8px;
        }
        .step-circle.active {
            background-color: #00bfa5;
        }
        .step-circle.completed {
            background-color: #00bfa5;
        }
        .step-circle.completed::after {
            content: "✓";
        }
        .step-text {
            font-size: 14px;
            color: #666;
        }
        .step-text.active {
            color: #00bfa5;
            font-weight: 500;
        }
        .step-connector {
            height: 1px;
            width: 30px;
            background-color: #ccc;
            margin: 0 5px;
        }
        .payment-amount {
            text-align: center;
            padding: 30px 0;
            background-color: white;
        }
        .amount-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount {
            font-size: 36px;
            font-weight: 300;
            color: #333;
        }
        .currency {
            color: #00bfa5;
            font-weight: normal;
        }
        .payment-methods {
            padding: 20px;
            background-color: white;
        }
        .method-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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
            object-fit: contain;
        }
        .method-name {
            font-size: 14px;
            color: #333;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: white;
        }
        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-next, .btn-pay {
            background-color: #00bfa5;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-next:hover, .btn-pay:hover {
            background-color: #00a895;
            color: white;
        }
        .btn-back {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .billing-details {
            padding: 20px;
            background-color: white;
        }
        .section-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        .form-label.required::before {
            content: "* ";
            color: #e74c3c;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .powered-by {
            padding: 10px;
            text-align: left;
            margin-top: 20px;
        }
        .powered-by img {
            height: 20px;
        }
        .method-badge {
            background-color: #f0f8ff;
            color: #00bfa5;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
        }
        .summary-section {
            padding: 20px;
            background-color: white;
        }
        .summary-card {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            justify-content: space-between;
        }
        .summary-title .edit-link {
            font-size: 14px;
            color: #00bfa5;
            text-decoration: none;
        }
        .summary-content {
            font-size: 14px;
            color: #666;
        }
        .summary-item {
            margin-bottom: 8px;
        }
        .summary-item strong {
            color: #333;
        }
        .summary-total {
            font-size: 18px;
            font-weight: 500;
            color: #333;
            text-align: right;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row">
                <div class="col text-start">
                    <!-- Header content -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="reference-bar">
        <div class="container">
            <div class="row">
                <div class="col">
                    Payment Reference: <?php echo $display_reference; ?> <?php if($step > 1): ?><span class="method-badge"><?php echo ucfirst($payment_method); ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="payment-container">
        <div class="progress-steps">
            <div class="step-item">
                <div class="step-circle <?php echo ($step > 1) ? 'completed' : ($step == 1 ? 'active' : ''); ?>">
                    <?php echo ($step > 1) ? '' : '1'; ?>
                </div>
                <div class="step-text <?php echo ($step == 1) ? 'active' : ''; ?>">Payment Information</div>
            </div>
            <div class="step-connector"></div>
            <div class="step-item">
                <div class="step-circle <?php echo ($step > 2) ? 'completed' : ($step == 2 ? 'active' : ''); ?>">
                    <?php echo ($step > 2) ? '' : '2'; ?>
                </div>
                <div class="step-text <?php echo ($step == 2) ? 'active' : ''; ?>">Billing Details</div>
            </div>
            <div class="step-connector"></div>
            <div class="step-item">
                <div class="step-circle <?php echo ($step == 3) ? 'active' : ''; ?>">3</div>
                <div class="step-text <?php echo ($step == 3) ? 'active' : ''; ?>">Summary</div>
            </div>
        </div>
        
        <?php if($step == 1): ?>
        <!-- Step 1: Payment Method Selection -->
        <div class="payment-amount">
            <div class="amount-text">A great way to spend money!</div>
            <div class="amount"><span class="currency">₱</span><?php echo $formatted_amount; ?></div>
            <div class="amount-text">Amount to Pay</div>
        </div>
        
        <div class="payment-methods">
            <div class="method-title">SELECT PAYMENT METHOD</div>
            <div class="methods-grid">
                <div class="method-card" data-method="card">
                    <img src="https://cdn-icons-png.flaticon.com/512/179/179457.png" alt="Credit/Debit Card" class="method-icon">
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
                <div class="method-card" data-method="unionbank">
                    <img src="https://www.unionbankph.com/assets/img/unionbank-logo.png" alt="UnionBank Online" class="method-icon">
                    <div class="method-name">UnionBank Online</div>
                </div>
                <div class="method-card" data-method="atome">
                    <img src="https://www.atome.sg/wp-content/uploads/2021/02/atome-logo.png" alt="Atome" class="method-icon">
                    <div class="method-name">Atome</div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="<?php echo $cancel_url; ?>" class="btn-cancel">Cancel</a>
            <a href="<?php echo $step2_url; ?>" class="btn-next" id="nextButton">Next</a>
        </div>
        
        <?php elseif($step == 2): ?>
        <!-- Step 2: Billing Details -->
        <div class="billing-details">
            <div class="section-title">Customer Information</div>
            
            <form id="billingForm" action="<?php echo $step3_url; ?>" method="GET">
                <!-- Hidden fields to preserve URL parameters -->
                <input type="hidden" name="ref" value="<?php echo $payment_reference; ?>">
                <input type="hidden" name="method" value="<?php echo $payment_method; ?>">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="charge_id" value="<?php echo $charge_id; ?>">
                <input type="hidden" name="step" value="3">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label required">E-mail Address</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">Contact Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label required">Name</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($name); ?>">
                </div>
                
                <div class="powered-by">
                    <img src="https://magpie.im/wp-content/uploads/2023/01/magpie-logo.png" alt="Powered by Magpie">
                </div>
                
                <div class="actions">
                    <a href="<?php echo str_replace('step=2', 'step=1', $_SERVER['REQUEST_URI']); ?>" class="btn-back">Back</a>
                    <button type="submit" class="btn-next">Next</button>
                </div>
            </form>
        </div>
        
        <?php elseif($step == 3): ?>
        <!-- Step 3: Summary -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="summary-title">
                    Payment Method <a href="<?php echo str_replace('step=3', 'step=1', $_SERVER['REQUEST_URI']); ?>" class="edit-link">Edit</a>
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <strong><?php echo ucfirst($payment_method); ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title">
                    Billing Details <a href="<?php echo str_replace('step=3', 'step=2', $_SERVER['REQUEST_URI']); ?>" class="edit-link">Edit</a>
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <strong>Name:</strong> <?php echo htmlspecialchars($name); ?>
                    </div>
                    <div class="summary-item">
                        <strong>Email:</strong> <?php echo htmlspecialchars($email); ?>
                    </div>
                    <?php if (!empty($phone)): ?>
                    <div class="summary-item">
                        <strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title">
                    Payment Details
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_id); ?>
                    </div>
                    <div class="summary-item">
                        <strong>Payment Reference:</strong> <?php echo htmlspecialchars($display_reference); ?>
                    </div>
                    <div class="summary-total">
                        Total Amount: <span class="currency">₱</span><?php echo $formatted_amount; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="<?php echo str_replace('step=3', 'step=2', $_SERVER['REQUEST_URI']); ?>" class="btn-back">Back</a>
            <a href="<?php echo $success_url; ?>" class="btn-pay" id="payButton">Pay Now</a>
        </div>
        <?php endif; ?>
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
                
                // Update payment method in next URL
                const method = this.getAttribute('data-method');
                const nextButton = document.getElementById('nextButton');
                if (nextButton) {
                    const currentUrl = nextButton.getAttribute('href');
                    // Replace the method parameter in the URL
                    const newUrl = currentUrl.replace(/method=[^&]+/, `method=${method}`);
                    nextButton.setAttribute('href', newUrl);
                }
            });
        });
        
        // Add loading animation when Pay Now button is clicked
        const payButton = document.getElementById('payButton');
        if (payButton) {
            payButton.addEventListener('click', function(e) {
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                this.classList.add('disabled');
                
                // Allow the redirect to happen after a short delay
                setTimeout(() => {
                    return true;
                }, 1000);
            });
        }
    </script>
</body>
</html>
