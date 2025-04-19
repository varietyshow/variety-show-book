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
        header('Location: booking-success.php');
        exit;
    } else {
        // Payment failed
        header('Location: booking-failed.php');
        exit;
    }

} catch (Exception $e) {
    // Handle error
    header('Location: booking-failed.php?error=' . urlencode($e->getMessage()));
    exit;
}
