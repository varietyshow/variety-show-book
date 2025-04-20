<?php
// For AJAX requests, we want to return JSON errors
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

if (isAjaxRequest()) {
    // Prevent any output before our JSON response
    ob_start();
    
    // Set error handling for AJAX requests
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $errstr
        ]);
        exit;
    });
    
    // Set exception handler for AJAX requests
    set_exception_handler(function($e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    });
}

session_start();
error_log("Starting appointment processing...");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'db_connect.php';
require_once '../includes/mail-config.php';
require_once '../includes/email-notifications.php';

// Debug request method
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Add this function after your session_start()
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Check if user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php");
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database connection
$host = 'sql12.freesqldatabase.com'; // Your database host
$dbname = 'sql12774230'; // Your database name
$username = 'sql12774230'; // Your database username
$password = 'ytPEFx33BF'; // Your database password

$conn = new mysqli($host, $username, $password, $dbname);

try {
    // Fetch data from booking_report table
    $stmt = $conn->query("
        SELECT br.* 
        FROM booking_report br 
        ORDER BY br.date_schedule DESC
    ");
    $appointments = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}

// Function to get appointment details
function getAppointmentDetails($conn, $book_id) {
    error_log("Getting appointment details for book_id: " . $book_id);

    $sql = "SELECT br.*, 
            ca.email as customer_email, 
            ca.first_name as customer_fname, 
            ca.last_name as customer_lname,
            ea.first_name as entertainer_fname, 
            ea.last_name as entertainer_lname,
            CONCAT(ea.first_name, ' ', ea.last_name) as entertainer_name,
            ea.email as entertainer_email,
            br.roles,
            br.date_schedule,
            br.time_start,
            br.time_end
            FROM booking_report br
            LEFT JOIN customer_account ca ON br.customer_id = ca.customer_id
            LEFT JOIN entertainer_account ea ON br.entertainer_id = ea.entertainer_id
            WHERE br.book_id = ?";
            
    error_log("Executing SQL Query: " . $sql . " with book_id: " . $book_id);
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        
        if ($appointment) {
            error_log("Appointment details found for book_id: " . $book_id);
            error_log("Full appointment data: " . print_r($appointment, true));
            error_log("Customer email: " . ($appointment['customer_email'] ?? 'not set'));
            error_log("Customer name: " . ($appointment['customer_fname'] ?? 'not set') . " " . ($appointment['customer_lname'] ?? 'not set'));
            error_log("Entertainer email: " . ($appointment['entertainer_email'] ?? 'not set'));
            error_log("Entertainer name: " . ($appointment['entertainer_fname'] ?? 'not set') . " " . ($appointment['entertainer_lname'] ?? 'not set'));
            error_log("Appointment date: " . ($appointment['date_schedule'] ?? 'not set'));
            error_log("Appointment time: " . ($appointment['time_start'] ?? 'not set') . " - " . ($appointment['time_end'] ?? 'not set'));
            error_log("Services: " . ($appointment['roles'] ?? 'not set'));
            
            // Make sure we have the correct name fields
            $appointment['first_name'] = $appointment['customer_fname'];
            $appointment['last_name'] = $appointment['customer_lname'];
            
            return $appointment;
        } else {
            error_log("No appointment found for book_id: " . $book_id);
            return false;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in getAppointmentDetails: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    } catch (Exception $e) {
        error_log("General error in getAppointmentDetails: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Function to send JSON response
function sendJsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Process POST requests for actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("POST request received with action");
    error_log("POST data received: " . print_r($_POST, true));
    
    if (!isset($_POST['book_id'])) {
        sendJsonResponse('error', 'No book_id specified');
    }

    $action = $_POST['action'];
    $book_id = $_POST['book_id'];
    error_log("Processing action: $action for book_id: $book_id");

    // Get appointment details
    $appointment = getAppointmentDetails($conn, $book_id);
    if (!$appointment) {
        sendJsonResponse('error', 'Appointment not found');
    }

    switch ($action) {
        case 'approve':
            error_log("Processing approval for book_id: " . $book_id);
            try {
                $stmt = $conn->prepare("UPDATE booking_report SET status = 'Approved' WHERE book_id = ?");
                $stmt->bind_param("i", $book_id);
                if ($stmt->execute()) {
                    error_log("Appointment status updated to Approved");
                    
                    // Send notifications to customer and entertainer
                    sendAppointmentStatusNotification($conn, $book_id, 'Approved');
                    
                    sendJsonResponse('success', 'Appointment approved successfully');
                } else {
                    sendJsonResponse('error', 'Failed to update appointment status');
                }
            } catch (mysqli_sql_exception $e) {
                error_log("Database error during approval: " . $e->getMessage());
                sendJsonResponse('error', 'Database error occurred');
            } catch (Exception $e) {
                error_log("General error during approval: " . $e->getMessage());
                sendJsonResponse('error', 'An error occurred');
            }
            break;

        case 'decline':
            if (!isset($_POST['reason'])) {
                sendJsonResponse('error', 'No reason provided');
            }
            
            $reason = trim($_POST['reason']);
            if (empty($reason)) {
                sendJsonResponse('error', 'Reason cannot be empty');
            }
            
            error_log("Processing decline for book_id: $book_id with reason: $reason");
            
            try {
                $stmt = $conn->prepare("UPDATE booking_report SET status = 'Declined', reason = ? WHERE book_id = ?");
                $stmt->bind_param("si", $reason, $book_id);
                if ($stmt->execute()) {
                    error_log("Appointment status updated to Declined");
                    
                    // Send notifications to customer and entertainer
                    try {
                        sendAppointmentStatusNotification($conn, $book_id, 'Declined', $reason);
                    } catch (Exception $e) {
                        error_log("Error sending notification: " . $e->getMessage());
                        // Continue with the response even if notification fails
                    }
                    
                    sendJsonResponse('success', 'Appointment declined successfully');
                } else {
                    sendJsonResponse('error', 'Failed to update appointment status: ' . $stmt->error);
                }
            } catch (mysqli_sql_exception $e) {
                error_log("Database error during decline: " . $e->getMessage());
                sendJsonResponse('error', 'Database error occurred: ' . $e->getMessage());
            } catch (Exception $e) {
                error_log("General error during decline: " . $e->getMessage());
                sendJsonResponse('error', 'An error occurred: ' . $e->getMessage());
            }
            break;

        case 'cancel':
            $reason = $_POST['reason'];
            error_log("Processing cancellation for book_id: $book_id with reason: $reason");
            
            try {
                $stmt = $conn->prepare("UPDATE booking_report SET status = 'Cancelled', reason = ? WHERE book_id = ?");
                $stmt->bind_param("si", $reason, $book_id);
                if ($stmt->execute()) {
                    error_log("Appointment status updated to Cancelled");
                    
                    // Send notifications to customer and entertainer
                    sendAppointmentStatusNotification($conn, $book_id, 'Cancelled', $reason);
                    
                    sendJsonResponse('success', 'Appointment cancelled successfully');
                } else {
                    sendJsonResponse('error', 'Failed to update appointment status');
                }
            } catch (mysqli_sql_exception $e) {
                error_log("Database error during cancellation: " . $e->getMessage());
                sendJsonResponse('error', 'Database error occurred');
            } catch (Exception $e) {
                error_log("General error during cancellation: " . $e->getMessage());
                sendJsonResponse('error', 'An error occurred');
            }
            break;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // This is a normal page load, continue with HTML output
} else {
    sendJsonResponse('error', 'Invalid request method');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
         body {
            overflow: auto; /* Only show scrollbar when necessary */
        }

        .content {
            overflow: hidden; /* Hide scrollbar initially */
        }

        /* Show scrollbar when content overflows */
        .content {
            overflow-y: auto;
        }

            /* Schedule List Styles */
        .content {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Creates space between elements */
            align-items: center;
            margin-left: 20px;
            padding: 20px;
            padding-top: 80px; /* Increased to account for fixed header */
            background-color: #f2f2f2;
            min-height: 100vh;
        }

        .schedule-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1100px;
        }


        .schedule-container input[type="text"] {
            padding: 10px;
            width: 40%;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .schedule-header {
            background-color: #fff;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
        }

        .schedule-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .schedule-table th, .schedule-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }

        .schedule-table th {
            background-color: #f8f8f8;
            font-weight: normal;
        }

        .button-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .schedule-header .refresh-btn
         {
            width: 40px;
            height: 40px;
            border: none;
            background-color: white;
            color: black;
            font-size: 18px;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .schedule-header .refresh-btn:hover
        {
            background-color: #f0fff0;
        }

        #status-select {
            padding: 10px;
            width: 30%;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 20px; /* Space between dropdown and table */
        }

        .nav-items {
            display: flex;
            align-items: center;
            gap: 20px; /* Keep the gap between nav links */
            margin-right: 20px; /* Add space between nav-items and profile dropdown */
        }

        .nav-items a {
            text-decoration: none;
            color: white; /* Adjust color as needed */
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-items a:hover {
            background-color: #87CEFA; /* Light blue background on hover */
            text-decoration: none; /* Ensure no underline on hover */
            color: black;
        }

        .dropbtn {
            background: none; /* Remove default button background */
            border: none; /* Remove default button border */
            cursor: pointer; /* Pointer cursor on hover */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

        .action-btn {
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 2px;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
            vertical-align: middle;
            font-weight: 500;
            box-shadow: none;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .view-btn {
            background-color: #f8f9fa;
            color: #495057;
        }

        .approve-btn {
            background-color: #e7f5e7;
            color: #28a745;
        }

        .decline-btn {
            background-color: #fee7e7;
            color: #dc3545;
        }

        .cancel-btn {
            background-color: #fff3cd;
            color: #856404;
        }

        .action-btn:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transform: none;
            filter: none;
        }

/* Add tooltip styles */
.action-btn {
    position: relative;
}

.action-btn::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 8px;
    background-color: #333;
    color: white;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.action-btn:hover::after {
    opacity: 1;
    visibility: visible;
}

/* Add modal styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.7); /* Dark background with more opacity */
}

.modal-content {
    background-color: #fff;
    margin: 60px auto; /* Center modal with 50px margin from the top */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    max-width: 600px; /* Limit the maximum width */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Box shadow for depth */
    position: relative; /* Position relative for absolute positioned close button */
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer; /* Pointer cursor on hover */
}

.close:hover,
.close:focus {
    color: #ff3333; /* Change color on hover */
    text-decoration: none;
    outline: none;
}

/* Heading styles */
.modal-content h2 {
    margin: 0;
    color: #333;
    font-size: 24px;
}

.modal-content h2 {
    margin: 0;
    color: #333;
    font-size: 24px;
    text-align: center; /* Center the heading text */
}

/* Details Styling */
#modalDetails {
    padding: 10px 0; /* Add padding for better spacing */
}

/* Additional styling for detail paragraphs */
#modalDetails p {
    padding: 8px 0;
    border-bottom: 1px solid #ddd; /* Optional border below each detail */
    margin: 8px 0;
}

/* Last detail styling */
#modalDetails p:last-child {
    border-bottom: none; /* Remove border from last element */
}

.modal-content {
    max-height: 80vh; /* Maximum height of 80% of viewport height */
    overflow-y: auto; /* Enable scrolling if content is too long */
}

#modalDetails {
    padding: 20px;
}

#modalDetails p {
    margin: 10px 0;
    font-size: 16px;
}

#modalDetails strong {
    color: #333;
}

#modalDetails ul {
    list-style: none;
    padding-left: 20px;
    margin: 5px 0 15px 0;
}

#modalDetails li {
    margin: 5px 0;
    color: #666;
}

#modalDetails span {
    font-weight: bold;
}

#reasonText {
    resize: vertical;
    min-height: 100px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
}

#reasonText:focus {
    outline: none;
    border-color: #87CEFA;
    box-shadow: 0 0 5px rgba(135, 206, 250, 0.5);
}

#reasonModalContent {
    padding: 20px;
}

/* Header and Navigation Styles */
header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 0;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background-color: #333;
    z-index: 1000;
}

.navbar-brand {
    flex-shrink: 0;
    padding-left: 0;
    margin-left: 0;
}

nav {
    display: flex;
    align-items: center;
    margin-left: auto;
    margin-right: 15px;
}

.nav-items {
    display: flex;
    align-items: center;
    gap: 20px; /* Increased from 10px to 20px to match entertainer page */
}

/* Mobile Menu Toggle */
.menu-toggle {
    display: none;
    cursor: pointer;
    padding: 10px;
    background: none;
    border: none;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1001;
}

.menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 5px 0;
    transition: 0.4s;
}

/* Mobile Styles */
@media (max-width: 768px) {
    /* Show hamburger menu, hide regular nav */
    .menu-toggle {
        display: block;
        margin-left: auto;
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
        z-index: 1002;
    }

    .menu-toggle span {
        display: block;
        width: 25px;
        height: 3px;
        background-color: white;
        margin: 5px 0;
        transition: 0.4s;
    }

    .nav-items {
        display: none;
        position: absolute;
        top: 60px;
        right: -20px;
        left: auto;
        width: 160px;
        background-color: #333;
        flex-direction: column;
        padding: 3px 0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        gap: 0;
        opacity: 0;
        visibility: hidden;
        transform: translateX(20px);
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    }

    .nav-items.active {
        display: flex;
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    .nav-items a {
        color: white;
        padding: 8px 12px;
        width: 100%;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
        font-size: 13px;
        transition: background-color 0.3s;
    }

    .nav-items a:hover {
        background-color: #87CEFA;
        color: black;
    }

    .nav-items a:last-child {
        border-bottom: none;
    }

    /* Show mobile-only items */
    .mobile-only {
        display: block !important;
    }

    .nav-items a.mobile-only {
        display: block !important;
        border-top: 2px solid rgba(255, 255, 255, 0.2);
        margin-top: 4px;
        padding-top: 8px;
    }

    /* Hide desktop dropdown on mobile */
    .dropdown {
        display: none !important;
    }

    /* Animation for menu toggle */
    .menu-toggle.active span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }

    .menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .menu-toggle.active span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }
}

/* Add these styles for mobile-only links */
.mobile-only {
    display: none !important; /* Hidden by default on desktop */
}

@media (max-width: 768px) {
    .mobile-only {
        display: block !important; /* Show on mobile */
    }
}

.reason-btn {
    background-color: #e9ecef;
    color: #6c757d;
}

/* Add tooltip max-width to prevent long reasons from breaking layout */
.action-btn::after {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Update modal styles */
#reasonViewModal .modal-content {
    max-width: 400px;
    padding: 25px;
}

#reasonViewModal h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5em;
    text-align: center;
}

#reasonViewText {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    color: #495057;
    line-height: 1.5;
    margin: 0;
    word-wrap: break-word;
}

#reasonViewModal .close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    transition: color 0.3s ease;
}

#reasonViewModal .close:hover {
    color: #dc3545;
}

/* View Reason Modal Styles */
.view-reason-modal {
    max-width: 500px;
    margin: 20px auto;
    background: white;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    position: relative;
    transform: translateY(50px);
    animation: slideIn 0.3s ease-out forwards;
}

@keyframes slideIn {
    to {
        transform: translateY(0);
    }
}

.modal-header {
    padding: 20px 25px;
    background-color: #f8f9fa;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
    font-weight: 600;
}

.modal-header .close {
    font-size: 24px;
    color: #666;
    cursor: pointer;
    transition: color 0.2s;
    padding: 5px;
    margin: -5px;
}

.modal-header .close:hover {
    color: #dc3545;
}

.modal-body {
    padding: 25px;
}

.reason-container {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.reason-icon {
    font-size: 24px;
    color: #6c757d;
    margin-top: 3px;
}

#reasonViewText {
    margin: 0;
    line-height: 1.6;
    color: #444;
    font-size: 1rem;
    flex: 1;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
}

.modal-btn {
    padding: 8px 20px;
    border-radius: 6px;
    border: none;
    background-color: #6c757d;
    color: white;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.modal-btn:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.modal-btn:active {
    transform: translateY(0);
}

/* Update the modal backdrop */
.modal {
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
}

/* Add these styles for the completion select dropdown */
.completion-select {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    width: 140px;
    cursor: pointer;
}

.completion-select:hover {
    border-color: #999;
}

.completion-select option {
    padding: 5px;
}

/* Add styles for the action cell */
.schedule-table td:last-child {
    white-space: nowrap; /* Prevent buttons from wrapping */
    min-width: 150px; /* Ensure minimum width for buttons */
    text-align: left; /* Align buttons to the left */
}

/* Update button container styles */
.button-container {
    display: inline-flex; /* Use inline-flex for horizontal alignment */
    gap: 4px; /* Consistent gap between buttons */
    align-items: center;
}

/* Add styles for the reason modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modal h3 {
    margin-top: 0;
    color: #333;
}

.modal textarea {
    width: 100%;
    min-height: 100px;
    margin: 10px 0;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}

.modal button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.modal button:hover {
    opacity: 0.9;
}

.modal .button-container {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

/* Add styles for decline button */
.decline-btn {
    background: none;
    border: none;
    color: #dc3545;/* Update the SweetAlert2 modal styles */
.swal2-popup {
    width: 90% !important; /* Change from fixed width to percentage */
    max-width: 32em !important; /* Add max-width */
    padding: 1.25em !important;
    margin: 0 auto !important;
    overflow: hidden !important;
}

.cancel-reason-container {
    margin: 0 auto;
    width: 100%; /* Change from 90% to 100% */
    max-width: 100%;
    padding: 0 1em; /* Add padding */
}

.cancel-reason-container textarea {
    width: 100% !important;
    min-height: 120px !important; /* Reduce minimum height for mobile */
    padding: 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-size: 14px !important;
    resize: vertical;
    margin: 0 auto;
    display: block;
    box-sizing: border-box !important;
}

/* Add media queries for different screen sizes */
@media (max-width: 768px) {
    .swal2-popup {
        padding: 1em !important;
        width: 95% !important;
    }

    .cancel-reason-container {
        padding: 0 0.5em;
    }

    .cancel-reason-container label {
        font-size: 14px;
    }

    .cancel-reason-container textarea {
        min-height: 100px !important;
        font-size: 13px !important;
    }

    .swal2-title {
        font-size: 1.5em !important;
    }

    .swal2-actions {
        flex-direction: column;
        gap: 0.5em;
    }

    .swal2-confirm, .swal2-cancel {
        margin: 0.25em !important;
        width: 100% !important;
    }
}

@media (max-width: 480px) {
    .swal2-popup {
        padding: 0.75em !important;
        width: 98% !important;
    }

    .cancel-reason-container textarea {
        min-height: 80px !important;
    }

    .swal2-title {
        font-size: 1.25em !important;
    }
}

/* Add smooth transitions */
.swal2-popup {
    transition: all 0.3s ease-in-out;
}

/* Improve button touch targets on mobile */
.swal2-confirm, .swal2-cancel {
    min-height: 44px !important; /* Minimum touch target size */
    padding: 0.5em 1em !important;
}

/* Ensure modal is centered on all devices */
.swal2-container {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 10px !important;
}

    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
}

.decline-btn:hover {
    color: #bd2130;
}

.decline-btn i {
    font-size: 16px;
}

/* Add loading spinner styles */
.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success Modal Styles */
#successModal .reason-container {
    text-align: center;
    padding: 20px;
}

#successModal .reason-icon {
    font-size: 48px;
    color: #28a745;
    margin-bottom: 15px;
}

#successModal #successText {
    font-size: 18px;
    color: #333;
    margin: 10px 0;
}

#successModal .modal-header {
    background-color: #28a745;
    color: white;
}

#successModal .modal-btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

#successModal .modal-btn:hover {
    background-color: #218838;
}

/* Browser-style Loading Indicator */
.browser-loading {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #f3f3f3;
    z-index: 9999;
}

.browser-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 20%;
    height: 100%;
    background-color: #3498db;
    animation: loading 1s infinite ease-in-out;
}

@keyframes loading {
    0% {
        left: -20%;
    }
    50% {
        left: 40%;
        width: 40%;
    }
    100% {
        left: 100%;
        width: 20%;
    }
}

/* Hide old loading overlay */
.loading-overlay {
    display: none !important;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.dropbtn img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 4px;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #333;
    min-width: 160px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border-radius: 4px;
    z-index: 1000;
    margin-top: 4px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.dropdown-content.show {
    display: block;
    opacity: 1;
    visibility: visible;
}

.dropdown-content a {
    color: white;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    font-size: 13px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: background-color 0.3s;
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover {
    background-color: #87CEFA;
    color: black;
}

/* Remove underline from dropdown links */
.dropdown-content a,
.dropdown-content a:hover,
.dropdown-content a:focus,
.dropdown-content a:active {
    text-decoration: none !important;
}

/* Hide mobile-only items in desktop view */
.mobile-only {
    display: none !important;
}

/* Mobile styles for appointment container */
@media (max-width: 768px) {
    .content {
        padding: 70px 10px 20px;
    }

    .welcome-message {
        margin: 0 0 20px 0;
        padding: 15px;
    }

    .schedule-container {
        padding: 10px;
        margin: 0;
        overflow-x: auto;
    }

    /* Card-based layout for mobile */
    .schedule-table {
        display: block;
        width: 100%;
    }

    .schedule-table thead {
        display: none; /* Hide table headers on mobile */
    }

    .schedule-table tbody {
        display: block;
        width: 100%;
    }

    .schedule-table tr {
        display: block;
        width: 100%;
        margin-bottom: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 12px;
    }

    .schedule-table td {
        display: block;
        width: 100%;
        padding: 8px 0;
        text-align: left;
        border: none;
        position: relative;
    }

    /* Add labels for each cell */
    .schedule-table td:before {
        content: attr(data-label);
        font-weight: bold;
        display: inline-block;
        width: 120px;
        color: #666;
    }

    /* Style for action buttons container */
    .schedule-table td:last-child {
        display: flex;
        gap: 8px;
        justify-content: flex-start;
        padding-top: 12px;
        border-top: 1px solid #eee;
        margin-top: 8px;
    }

    /* Status styles */
    .status-cell {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        margin-left: 120px;
    }

    /* Filter and refresh section */
    .filter-section {
        flex-direction: column;
        gap: 10px;
        padding: 10px;
    }

    .filter-section select,
    .filter-section .refresh-btn {
        width: 100%;
        margin: 0;
    }

    /* Action buttons */
    .action-btn {
        padding: 6px 12px;
        font-size: 14px;
    }

    .action-btn i {
        margin-right: 4px;
    }

    /* Modal adjustments for mobile */
    .modal-content {
        width: 90%;
        margin: 20px auto;
    }

    .modal-body {
        padding: 15px;
    }

    .modal-footer {
        padding: 10px 15px;
    }
}

.loading-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
    vertical-align: text-bottom;
    border: 0.2em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    -webkit-animation: spinner-border .75s linear infinite;
    animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}
</style>
<style>
/* Add custom styles for SweetAlert2 modal */
.swal2-popup {
    width: 32em !important;
    padding: 1.25em !important;
    overflow: hidden !important;
}

.swal2-html-container {
    margin: 1em 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}

.cancel-reason-container {
    margin: 0 auto;
    width: 90%;
    max-width: 100%;
}

.cancel-reason-container label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    text-align: left;
    color: #333;
}

.cancel-reason-container textarea {
    width: 100% !important;
    min-height: 150px !important;
    padding: 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-size: 14px !important;
    resize: vertical;
    margin: 0 auto;
    display: block;
    box-sizing: border-box !important;
}

/* Remove horizontal scrollbar from modal */
.swal2-container {
    padding: 0 !important;
}

.swal2-title {
    padding: 0.5em 0 !important;
}

.decline-reason-container {
    margin: 0 auto;
    width: 90%;
    max-width: 100%;
}

.decline-reason-container label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    text-align: left;
    color: #333;
}

.decline-reason-container textarea {
    width: 100% !important;
    min-height: 150px !important;
    padding: 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-size: 14px !important;
    resize: vertical;
    margin: 0 auto;
    display: block;
    box-sizing: border-box !important;
}
</style>

<body>
    <div class="browser-loading" id="browserLoading"></div>
    <header>
        <a class="navbar-brand" href="#">
            <img src="../images/logo.jpg" alt="Brand Logo">
        </a>
        <nav>
            <button class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-items">
                <a href="admin-dashboard.php">Dashboard</a>
                <a href="admin-appointments.php">Appointment List</a>
                <a href="admin-entertainer.php">Entertainer List</a>
                <a href="admin-roles.php">Roles List</a>
                <a href="admin-profile.php" class="mobile-only">View Profile</a>
                <a href="logout.php" class="mobile-only">Logout</a>
            </div>
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown(event)">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdownContent">
                    <a href="admin-profile.php">View Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="content">
        <!-- <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We're glad to have you here. Let's get started!</p>
        </section> -->

        <div class="schedule-container">
            <h2>Appointments</h2>
            <div class="schedule-header">
                <select id="status-select">
                    <option value="">All Status</option>
                    <option value="Approved">Approved</option>
                    <option value="Pending">Pending</option>
                    <option value="Declined">Declined</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <div class="right-controls">
                    <button class="add-btn" onclick="window.location.href='add-appointment.php'">
                        <i class="fas fa-plus"></i> Add Appointment
                    </button>
                </div>
            </div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date Schedule</th>
                        <th>Customer Name</th>
                        <th>Venue</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body">
    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $appointment): ?>
            <tr data-status="<?php echo htmlspecialchars($appointment['status']); ?>">
                <td data-label="Date Schedule"><?php echo formatDate($appointment['date_schedule']); ?></td>
                <td data-label="Customer Name"><?php echo htmlspecialchars(ucfirst($appointment['first_name'])) . ' ' . htmlspecialchars(ucfirst($appointment['last_name'])); ?></td>
                <td data-label="Venue">
                    <?php
                        echo htmlspecialchars(ucfirst($appointment['street'])) . ', ' .
                            htmlspecialchars(ucfirst($appointment['barangay'])) . ', ' .
                            htmlspecialchars(ucfirst($appointment['municipality'])) . ', ' .
                            htmlspecialchars(ucfirst($appointment['province']));
                    ?>
                </td>
                <td data-label="Total Price"><?php echo htmlspecialchars($appointment['total_price']); ?></td>
                <td data-label="Status" style="color: <?php 
                    $status = strtolower($appointment['status']);
                    switch ($status) {
                        case 'approved':
                            echo '#28a745';
                            break;
                        case 'pending':
                            echo '#ffa500';
                            break;
                        case 'declined':
                        case 'cancelled':
                            echo '#dc3545';
                            break;
                        default:
                            echo 'inherit';
                    }
                ?>"><?php echo htmlspecialchars($appointment['status'] ?? ''); ?></td>
                <td data-label="Remarks">
                    <?php 
                    $status = strtolower($appointment['status']);
                    if ($status === 'approved'): ?>
                        <select class="completion-select" 
                                data-book-id="<?php echo htmlspecialchars($appointment['book_id']); ?>"
                                data-previous-value="<?php echo htmlspecialchars($appointment['remarks']); ?>"
                                onchange="updateCompletion(this)">
                            <option value="Pending" <?php echo ($appointment['remarks'] === 'Pending') ? 'selected' : ''; ?>>
                                Event Pending
                            </option>
                            <option value="Complete" <?php echo ($appointment['remarks'] === 'Complete') ? 'selected' : ''; ?>>
                                Event Completed
                            </option>
                        </select>
                    <?php else:
                        switch ($status) {
                            case 'pending':
                                echo 'Waiting for approval';
                                break;
                            case 'declined':
                                echo 'Booking declined';
                                break;
                            case 'cancelled':
                                echo 'Event cancelled';
                                break;
                            default:
                                echo '-';
                        }
                    endif; ?>
                </td>
                <td data-label="Action">
                    <div class="button-container">
                        <button class="action-btn view-btn" 
                                onclick="window.open('view-appointment.php?id=<?php echo htmlspecialchars($appointment['book_id']); ?>', '_blank')"
                                data-tooltip="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($appointment['status'] === 'Pending'): ?>
                            <button class="action-btn approve-btn" 
                                    data-id="<?php echo htmlspecialchars($appointment['book_id']); ?>" 
                                    onclick="handleApprove('<?php echo htmlspecialchars($appointment['book_id']); ?>', this)"
                                    data-tooltip="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="action-btn decline-btn" 
                                    data-id="<?php echo htmlspecialchars($appointment['book_id']); ?>" 
                                    onclick="handleDecline('<?php echo htmlspecialchars($appointment['book_id']); ?>', this)"
                                    data-tooltip="Decline">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php elseif ($appointment['status'] === 'Approved'): ?>
                            <?php if ($appointment['remarks'] !== 'Complete'): ?>
                                <button class="action-btn cancel-btn" 
                                        data-id="<?php echo htmlspecialchars($appointment['book_id']); ?>" 
                                        onclick="handleCancel('<?php echo htmlspecialchars($appointment['book_id']); ?>')"
                                        data-tooltip="Cancel">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php endif; ?>
                        <?php elseif ($appointment['status'] === 'Declined' || $appointment['status'] === 'Cancelled'): ?>
                            <button class="action-btn reason-btn" 
                                    onclick="viewReason('<?php echo htmlspecialchars($appointment['book_id']); ?>')"
                                    data-tooltip="View Reason">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr class="message-row">
            <td colspan="7" style="text-align: center; background-color: #f0f0f0; color: gray;">
                No appointments available.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>

            <div id="no-status-message" style="display: none; text-align: center; padding: 20px; color: #666;">
    No appointments available for the selected status.
</div>

            <div class="pagination">
                <div class="pagination-controls">
                    <div class="items-per-page">
                        <label for="items-per-page">Items per page:</label>
                        <select id="items-per-page">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                        </select>
                    </div>
                    <div class="page-controls">
                        <button disabled aria-label="Previous Page">◀</button>
                        <span id="pagination-info">0-0 of 0</span>
                        <button disabled aria-label="Next Page">▶</button>
                    </div>
                </div>
            </div>

            <style>
                .pagination {
                    margin-top: 20px;
                }
                
                .pagination-controls {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 0;
                }

                .items-per-page {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .items-per-page select {
                    padding: 4px 8px;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                }

                .page-controls {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .page-controls button {
                    padding: 4px 8px;
                    border: 1px solid #ddd;
                    background: white;
                    border-radius: 4px;
                    cursor: pointer;
                }

                .page-controls button:disabled {
                    cursor: not-allowed;
                    opacity: 0.6;
                }

                .page-controls span {
                    margin: 0 8px;
                }
            </style>
        </div>
    </div>

    <div id="reasonViewModal" class="modal">
        <div class="modal-content view-reason-modal">
            <div class="modal-header">
                <h2>Status Reason</h2>
                <span class="close" onclick="closeReasonViewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="reason-container">
                    <i class="fas fa-info-circle reason-icon"></i>
                    <p id="reasonViewText"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeReasonViewModal()" class="modal-btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        function handleCancel(book_id) {
            Swal.fire({
                title: 'Cancel Appointment',
                text: 'Please provide a reason for cancelling:',
                input: 'textarea',
                inputPlaceholder: 'Enter reason here...',
                showCancelButton: true,
                confirmButtonText: 'Cancel Appointment',
                cancelButtonText: 'Go Back',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                inputValidator: (value) => {
                    if (!value) {
                        return 'You need to provide a reason!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = result.value;
                    fetch('admin-appointments.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=cancel&book_id=${book_id}&reason=${encodeURIComponent(reason)}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Appointment cancelled successfully!',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Failed to cancel appointment');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to process request',
                            icon: 'error'
                        });
                    });
                }
            });
        }
    </script>
    <script>
       // Function to format time to 12-hour format
function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':'); // Split the time string
    let hour = parseInt(hours, 10);
    const suffix = hour >= 12 ? 'PM' : 'AM'; // Determine if it's AM or PM
    hour = hour % 12 || 12; // Convert to 12-hour format, ensuring 0 becomes 12
    return `${hour}:${minutes} ${suffix}`; // Return formatted time
}

     // Update the rows in the modal
document.querySelectorAll('.view-btn').forEach(button => {
    button.addEventListener('click', function() {
        const appointment = JSON.parse(this.getAttribute('data-details'));
        const modalDetails = document.getElementById('modalDetails');

        // Format the venue address
        const venue = `${appointment.street}, ${appointment.barangay}, ${appointment.municipality}, ${appointment.province}`;

        // Format entertainer names with capitalized first letters
        const formattedEntertainerName = appointment.entertainer_name ? 
            capitalizeWords(appointment.entertainer_name) : 'Not assigned';

        // Get roles and rates
        const roles = appointment.roles.split(',').map(r => r.trim());
        const rates = appointment.role_rates ? appointment.role_rates.split(',').map(r => parseFloat(r)) : [];
        const durations = appointment.role_durations ? appointment.role_durations.split(',') : [];

        // Generate payment table rows
        const paymentTableRows = generatePaymentTableRows(appointment);

        modalDetails.innerHTML = `
            <p><strong>Customer Information:</strong></p>
            <ul>
                <li>Name: ${capitalizeWords(appointment.first_name)} ${capitalizeWords(appointment.last_name)}</li>
                <li>Contact Number: ${appointment.contact_number}</li>
            </ul>
            
            <p><strong>Event Details:</strong></p>
            <ul>
                <li>Date: ${appointment.date_schedule}</li>
                <li>Start Time: ${formatTime(appointment.time_start)}</li>
                <li>End Time: ${formatTime(appointment.time_end)}</li>
                <li>Venue: ${capitalizeWords(venue)}</li>
                <li>Entertainers: ${formattedEntertainerName}</li>
            </ul>
            
            <p><strong>Payment Information:</strong></p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Entertainer Name</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Role</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Duration</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    ${paymentTableRows}
                </tbody>
            </table>
            <ul>
                <li>Total Price: ₱${parseFloat(appointment.total_price).toLocaleString()}</li>
                <li>Down Payment: ₱${parseFloat(appointment.down_payment).toLocaleString()}</li>
                <li>Remaining Balance: ₱${parseFloat(appointment.balance).toLocaleString()}</li>
            </ul>
            
            <p><strong>Status:</strong> <span style="color: ${getStatusColor(appointment.status)}">${appointment.status}</span></p>
            ${(appointment.status === 'Declined' || appointment.status === 'Cancelled') && appointment.status_reason ? 
                `<p><strong>Reason:</strong> ${appointment.status_reason}</p>` : 
                ''}
        `;

        // Show the modal
        document.getElementById('myModal').style.display = "block";
    });
});

// Add this helper function for status colors
function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'approved':
            return '#28a745'; // Green
        case 'pending':
            return '#ffc107'; // Yellow/Orange
        case 'declined':
            return '#dc3545'; // Red
        default:
            return '#6c757d'; // Gray
    }
}

function handleApprove(book_id) {
    Swal.fire({
        title: 'Approve Appointment',
        text: 'Are you sure you want to approve this appointment?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('admin-appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve&book_id=${book_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Appointment approved successfully!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to approve appointment',
                        icon: 'error',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to process request',
                    icon: 'error',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
    });
}


</script>

<script>
    // Function to view reason
    async function viewReason(bookId) {
        try {
            const formData = new FormData();
            formData.append('book_id', bookId);

            const response = await fetch('get_reason.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text(); // Get the raw response text
            console.log('Raw response:', text); // Log it for debugging
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response from server');
            }
            
            if (data.success) {
                const reasonModal = document.getElementById('reasonViewModal');
                const reasonText = document.getElementById('reasonViewText');
                reasonText.textContent = data.reason || 'No reason provided';
                reasonModal.style.display = 'block';
            } else {
                throw new Error(data.message || 'Failed to fetch reason');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while fetching the reason'
            });
        }
    }

    // Function to close reason modal
    function closeReasonViewModal() {
        const modal = document.getElementById('reasonViewModal');
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('reasonViewModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>
    <script>
        // Toggle mobile menu
        function toggleMenu() {
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            if (navItems && menuToggle) {
                navItems.classList.toggle('active');
                menuToggle.classList.toggle('active');
            }
        }

        // Toggle dropdown menu for profile
        function toggleDropdown(event) {
            event.preventDefault();
            event.stopPropagation();
            const dropdownContent = document.getElementById('dropdownContent');
            dropdownContent.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // Close mobile menu when clicking outside
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (navItems.classList.contains('active') && 
                !event.target.closest('.nav-items') && 
                !event.target.closest('.menu-toggle')) {
                menuToggle.classList.remove('active');
                navItems.classList.remove('active');
            }

            // Close dropdown when clicking outside
            if (!event.target.matches('.dropbtn') && 
                !event.target.matches('.dropbtn img') && 
                !event.target.closest('.dropdown-content')) {
                const dropdowns = document.getElementsByClassName('dropdown-content');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });
    </script>
    <style>
.right-controls {
    margin-left: auto;
    padding-right: 20px;
}

.add-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.add-btn i {
    font-size: 14px;
}

.add-btn:hover {
    background-color: #45a049;
}
</style>
</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all rows and pagination elements
        const tableBody = document.getElementById('schedule-table-body');
        const itemsPerPageSelect = document.getElementById('items-per-page');
        const paginationInfo = document.getElementById('pagination-info');
        const [prevButton, nextButton] = document.querySelectorAll('.page-controls button');
        const statusSelect = document.getElementById('status-select');
        
        let currentPage = 1;
        let allRows = Array.from(tableBody.querySelectorAll('tr'));
        let filteredRows = allRows;
        
        // Function to filter rows based on status
        function filterRows() {
            const selectedStatus = statusSelect.value.toLowerCase();
            if (selectedStatus === '') {
                filteredRows = allRows;
            } else {
                filteredRows = allRows.filter(row => {
                    const rowStatus = row.getAttribute('data-status').toLowerCase();
                    return rowStatus === selectedStatus;
                });
            }
            
            // Show/hide no results message
            const noStatusMessage = document.getElementById('no-status-message');
            noStatusMessage.style.display = filteredRows.length === 0 ? 'block' : 'none';
            
            // Reset to first page when filter changes
            currentPage = 1;
            updateTable();
        }
        
        // Function to update table rows based on pagination
        function updateTable() {
            const itemsPerPage = parseInt(itemsPerPageSelect.value);
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            
            // Hide all rows first
            allRows.forEach(row => row.style.display = 'none');
            
            // Show only filtered rows for current page
            filteredRows.slice(start, end).forEach(row => row.style.display = '');
            
            // Update pagination info and buttons
            paginationInfo.textContent = `${Math.min(filteredRows.length, 1)}-${Math.min(end, filteredRows.length)} of ${filteredRows.length}`;
            prevButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === totalPages || totalPages === 0;
        }
        
        // Event listeners for pagination controls
        itemsPerPageSelect.addEventListener('change', function() {
            currentPage = 1;
            updateTable();
        });
        
        prevButton.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                updateTable();
            }
        });
        
        nextButton.addEventListener('click', function() {
            const itemsPerPage = parseInt(itemsPerPageSelect.value);
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                updateTable();
            }
        });
        
        // Add event listener for status filter
        statusSelect.addEventListener('change', filterRows);
        
        // Initialize table
        updateTable();
    });
</script>

<script>
    function handleDecline(book_id) {
        Swal.fire({
            title: 'Decline Appointment',
            text: 'Please provide a reason for declining:',
            input: 'textarea',
            inputPlaceholder: 'Enter reason here...',
            showCancelButton: true,
            confirmButtonText: 'Decline',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                fetch('admin-appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=decline&book_id=${book_id}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Appointment declined successfully!',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Failed to decline appointment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to process request',
                        icon: 'error'
                    });
                });
            }
        });
    }
</script>
