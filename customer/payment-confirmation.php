<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once 'db_connect.php';

try {
    if (!isset($_GET['charge_id'])) {
        throw new Exception('No charge ID provided');
    }

    $charge_id = $_GET['charge_id'];
    // Payment confirmation logic goes here.

    // Update the payment status in the database
    $stmt = $conn->prepare("UPDATE payment_transactions SET status = ? WHERE charge_id = ?");
    $status = 'successful'; // Replace with actual status from Magpie logic
    $stmt->bind_param("ss", $status, $charge_id);
    $stmt->execute();

    if ($status === 'successful') {
        // Payment successful
        $pageTitle = "Payment Confirmation";
        $message = "Your booking has been confirmed and payment has been processed successfully.";
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $pageTitle; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .confirmation-container {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    padding: 40px;
                    max-width: 600px;
                    width: 100%;
                    text-align: center;
                }
                .success-icon {
                    color: #28a745;
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                .error-icon {
                    color: #dc3545;
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                .booking-details {
                    margin-top: 30px;
                    text-align: left;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .booking-details p {
                    margin-bottom: 10px;
                }
                .btn-primary {
                    background-color: #4a90e2;
                    border-color: #4a90e2;
                    padding: 10px 20px;
                    font-weight: 500;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="confirmation-container">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                    &#10004;
                </div>
                <h2>Booking Successful!</h2>
                <p><?php echo $message; ?></p>
                <a href="customer-appointment.php" class="btn btn-primary">View My Appointments</a>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    } else {
        // Payment failed
        $pageTitle = "Payment Confirmation";
        $message = "Booking Failed: Payment was not successful.";
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $pageTitle; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .confirmation-container {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    padding: 40px;
                    max-width: 600px;
                    width: 100%;
                    text-align: center;
                }
                .success-icon {
                    color: #28a745;
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                .error-icon {
                    color: #dc3545;
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                .booking-details {
                    margin-top: 30px;
                    text-align: left;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .booking-details p {
                    margin-bottom: 10px;
                }
                .btn-primary {
                    background-color: #4a90e2;
                    border-color: #4a90e2;
                    padding: 10px 20px;
                    font-weight: 500;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="confirmation-container">
                <div class="error-icon">
                    <i class="bi bi-x-circle-fill"></i>
                    &#10060;
                </div>
                <h2>Booking Failed</h2>
                <p><?php echo $message; ?></p>
                <a href="customer-booking.php" class="btn btn-primary">Try Again</a>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    }

} catch (Exception $e) {
    // Handle error
    $pageTitle = "Payment Confirmation";
    $message = "Booking Failed: " . $e->getMessage();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $pageTitle; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .confirmation-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                padding: 40px;
                max-width: 600px;
                width: 100%;
                text-align: center;
            }
            .success-icon {
                color: #28a745;
                font-size: 80px;
                margin-bottom: 20px;
            }
            .error-icon {
                color: #dc3545;
                font-size: 80px;
                margin-bottom: 20px;
            }
            .booking-details {
                margin-top: 30px;
                text-align: left;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 5px;
            }
            .booking-details p {
                margin-bottom: 10px;
            }
            .btn-primary {
                background-color: #4a90e2;
                border-color: #4a90e2;
                padding: 10px 20px;
                font-weight: 500;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="confirmation-container">
            <div class="error-icon">
                <i class="bi bi-x-circle-fill"></i>
                &#10060;
            </div>
            <h2>Booking Failed</h2>
            <p><?php echo $message; ?></p>
            <a href="customer-booking.php" class="btn btn-primary">Try Again</a>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
