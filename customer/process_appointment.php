<?php
session_start();

// Include mail configuration
require_once '../includes/mail-config.php';

// Define admin email
define('ADMIN_EMAIL', 'medyosnob@gmail.com'); // Add admin email

include 'db_connect.php'; // Include your database connection script

header('Content-Type: application/json'); // Set the content type to JSON

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

// Grab the data from the form
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$contact_number = $_POST['contact_number'] ?? '';
$appointment_date = $_POST['appointment_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$entertainer_ids = $_POST['entertainer'] ?? '';  // Entertainer IDs passed from the form
$street = $_POST['street'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$municipality = $_POST['municipality'] ?? '';
$province = $_POST['province'] ?? '';
$roles = $_POST['roles'] ?? ''; 
$rolesString = is_array($roles) ? implode(',', $roles) : '';

// Update the perform duration handling
$perform_duration = $_POST['perform_duration'] ?? '';

// Process and store all durations, even if they're 0
if (!empty($perform_duration)) {
    $perform_duration_string = $perform_duration;
} else {
    $perform_duration_string = ''; // Default empty string if no durations provided
}

// Log the duration data for debugging
error_log("Received perform duration: " . $perform_duration_string);

// Make sure to properly escape the duration string for database insertion
$perform_duration_string = $conn->real_escape_string($perform_duration_string);

// Debug log before database insertion
error_log("Final perform duration to be inserted: " . $perform_duration_string);

// Set default customer ID based on session data
$customer_id = $_SESSION['customer_id'] ?? NULL; 

// Split the entertainer IDs into an array
$entertainer_ids_array = explode(',', $entertainer_ids);
$valid_entertainer_ids = [];
$entertainer_names = []; // Array to hold names of entertainers

foreach ($entertainer_ids_array as $entertainer_id) {
    // Check if each entertainer exists in the database
    $query = "SELECT first_name, last_name FROM entertainer_account WHERE entertainer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $entertainer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $entertainer_data = $result->fetch_assoc();
        $entertainer_name = $entertainer_data['first_name'] . ' ' . $entertainer_data['last_name'];
        $valid_entertainer_ids[] = $entertainer_id;
        $entertainer_names[] = $entertainer_name;
    } else {
        echo json_encode(["success" => false, "message" => "Error: Entertainer with ID $entertainer_id not found."]);
        exit();
    }
}

// Prepare a comma-separated list of entertainer names
$entertainer_names_string = implode(', ', $entertainer_names);

// Check for existing appointment
$check_sql = "SELECT * FROM booking_report 
              WHERE customer_id = ? 
              AND entertainer_id = ? 
              AND date_schedule = ? 
              AND time_start = ? 
              AND time_end = ?";

$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("iisss", 
    $customer_id, 
    $valid_entertainer_ids[0], 
    $appointment_date, 
    $start_time, 
    $end_time
);
$stmt_check->execute();
$existing_appointment = $stmt_check->get_result();

if ($existing_appointment->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You already have an appointment for this entertainer at the selected time."]);
    $stmt_check->close();
    exit();
}

// Handle the file upload for payment_screenshot
$payment_image = ''; 
if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
    $uploadDirectory = "../images/"; 
    $fileTmpPath = $_FILES['payment_screenshot']['tmp_name'];
    $fileName = $_FILES['payment_screenshot']['name'];
    $fileSize = $_FILES['payment_screenshot']['size'];
    $fileType = $_FILES['payment_screenshot']['type'];
    
    // Validate and store the uploaded file
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '.' . $fileExtension;
    $dest_path = $uploadDirectory . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $payment_image = $dest_path; 
    } else {
        echo json_encode(["success" => false, "message" => "Error moving the uploaded file."]);
        exit();
    }
}

// Prepare total price and payments
$total_price = $_POST['total_price'] ?? 0;
$down_payment = $_POST['downpayment'] ?? 0;
$balance = $total_price - $down_payment;

// Define status
$status = "Pending"; // Set a default status

// Prepare the SQL query with perform_durations column
$sql = "INSERT INTO booking_report 
        (customer_id, entertainer_id, first_name, last_name, contact_number, 
         street, barangay, municipality, province, 
         date_schedule, time_start, time_end, entertainer_name, roles, 
         total_price, down_payment, balance, payment_image, status, perform_durations) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Bind all parameters including perform_durations
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(["success" => false, "message" => "SQL Prepare Error: " . $conn->error]);
    exit();
}

// Debug: Log all values being bound
error_log("Binding values: " . print_r([
    'customer_id' => $customer_id,
    'entertainer_id' => $valid_entertainer_ids[0],
    'first_name' => $first_name,
    'last_name' => $last_name,
    'contact_number' => $contact_number,
    'street' => $street,
    'barangay' => $barangay,
    'municipality' => $municipality,
    'province' => $province,
    'date_schedule' => $appointment_date,
    'time_start' => $start_time,
    'time_end' => $end_time,
    'entertainer_name' => $entertainer_names_string,
    'roles' => $rolesString,
    'total_price' => $total_price,
    'down_payment' => $down_payment,
    'balance' => $balance,
    'payment_image' => $payment_image,
    'status' => $status,
    'perform_durations' => $perform_duration_string
], true));

$stmt->bind_param("iissssssssssssdddsss", 
    $customer_id, 
    $valid_entertainer_ids[0], 
    $first_name, 
    $last_name, 
    $contact_number,
    $street, 
    $barangay, 
    $municipality, 
    $province, 
    $appointment_date, 
    $start_time, 
    $end_time, 
    $entertainer_names_string, 
    $rolesString,
    $total_price,
    $down_payment,
    $balance,
    $payment_image,
    $status,
    $perform_duration_string  // This should now contain the duration data
);

// Before executing the query, log all values
error_log("Values being inserted: " . print_r([
    'perform_durations' => $perform_duration_string,
    // ... other values ...
], true));

// Execute and check for errors
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Error executing query: " . $stmt->error]);
    exit();
}

// Send email notification to admin
$adminSubject = "New Appointment Request";
$adminBody = "
    <h2>New Appointment Request Details</h2>
    <p><strong>Customer:</strong> {$first_name} {$last_name}</p>
    <p><strong>Contact Number:</strong> {$contact_number}</p>
    <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment_date)) . "</p>
    <p><strong>Time:</strong> " . date('g:i A', strtotime($start_time)) . " - " . date('g:i A', strtotime($end_time)) . "</p>
    <p><strong>Location:</strong> {$street}, {$barangay}, {$municipality}, {$province}</p>
    <p><strong>Entertainer:</strong> {$entertainer_names_string}</p>
    <p><strong>Services:</strong> {$rolesString}</p>
    <p><strong>Total Price:</strong> ₱" . number_format($total_price, 2) . "</p>
    <p><strong>Down Payment:</strong> ₱" . number_format($down_payment, 2) . "</p>
    <p><strong>Balance:</strong> ₱" . number_format($balance, 2) . "</p>
    <p><strong>Status:</strong> {$status}</p>
    <br>
    <p>Please review this appointment request in your admin dashboard.</p>
";

// Log before sending email
error_log("Attempting to send admin notification email to: " . ADMIN_EMAIL);

// Send email to admin
if (sendEmail(ADMIN_EMAIL, $adminSubject, $adminBody)) {
    error_log("Admin notification email sent successfully");
} else {
    error_log("Failed to send admin notification email");
}

// If successful, return success message
echo json_encode(["success" => true, "message" => "Your appointment has been successfully submitted!"]);

// Close the statement and connection
$stmt->close();
$stmt_check->close();
$conn->close();
?>