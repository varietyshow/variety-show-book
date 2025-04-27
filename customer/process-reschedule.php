<?php
session_start();

if (!isset($_SESSION['first_name']) || !isset($_POST['appointment_id'])) {
    header("Location: customer-loginpage.php");
    exit();
}

$appointment_id = intval($_POST['appointment_id']);
$new_date = $_POST['new_date'];
$new_time_start = $_POST['new_time_start'];
$new_time_end = $_POST['new_time_end'];

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the old schedule details before updating
$old_schedule_sql = "SELECT date_schedule, time_start, time_end FROM booking_report WHERE book_id = ?";
$old_stmt = $conn->prepare($old_schedule_sql);
$old_stmt->bind_param("i", $appointment_id);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_schedule = $old_result->fetch_assoc();
$old_stmt->close();

// Include email notifications
require_once '../includes/email-notifications.php';

// Update the appointment
$sql = "UPDATE booking_report 
        SET date_schedule = ?, 
            time_start = ?, 
            time_end = ?,
            status = 'Pending'
        WHERE book_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $new_date, $new_time_start, $new_time_end, $appointment_id);

if ($stmt->execute()) {
    // Send email notification
    sendRescheduleNotification(
        $conn, 
        $appointment_id, 
        $old_schedule['date_schedule'],
        $old_schedule['time_start'],
        $old_schedule['time_end']
    );
    $_SESSION['message'] = "Appointment rescheduled successfully. Waiting for approval.";
} else {
    $_SESSION['message'] = "Error rescheduling appointment: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: customer-appointment.php");
exit();
?>
