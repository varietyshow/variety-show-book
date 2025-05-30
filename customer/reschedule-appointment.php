<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php");
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['appointment_id'])) {
    echo "No appointment selected for rescheduling.";
    exit();
}

$appointment_id = intval($_GET['appointment_id']);

// Database connection
$servername = "sql12.freesqldatabase.com";
$username = "sql12777569";
$password = "QlgHSeuU1n";
$dbname = "sql12777569";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch appointment details with better name matching
$sql = "SELECT br.*, 
        GROUP_CONCAT(DISTINCT ea.entertainer_id) as entertainer_ids,
        GROUP_CONCAT(DISTINCT CONCAT(ea.first_name, ' ', ea.last_name, ' (', ea.title, ')') 
                    ORDER BY CASE WHEN ea.entertainer_id = br.entertainer_id THEN 0 ELSE 1 END) as matched_names_with_title
        FROM booking_report br 
        LEFT JOIN entertainer_account ea ON 
            ea.entertainer_id = br.entertainer_id OR
            (FIND_IN_SET(CONCAT(ea.first_name, ' ', ea.last_name), REPLACE(br.entertainer_name, ', ', ',')) AND 
             (br.entertainer_id IS NULL OR br.entertainer_id = 0))
        WHERE br.book_id = ? AND br.customer_id = ?
        GROUP BY br.book_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appointment_id, $_SESSION['customer_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    $entertainer_name = $appointment['entertainer_name'];
    $entertainer_ids = $appointment['entertainer_ids'] ? array_unique(explode(',', $appointment['entertainer_ids'])) : [];
    $matched_names = $appointment['matched_names_with_title'] ? array_unique(explode(',', $appointment['matched_names_with_title'])) : [];
    
    // Debug information
    error_log("Appointment ID: " . $appointment_id);
    error_log("Entertainer Name(s) from booking: " . $entertainer_name);
    error_log("Matched Names with Title: " . ($appointment['matched_names_with_title'] ?? 'none'));
    error_log("Entertainer IDs: " . ($appointment['entertainer_ids'] ?? 'none'));
    error_log("Booking Entertainer ID: " . $appointment['entertainer_id']);
    
    if (empty($entertainer_name)) {
        die("Error: Could not find entertainer name for this appointment.");
    }
    
    // If we didn't get any entertainer IDs from the join, use the one from booking_report
    if ((empty($entertainer_ids) || count($entertainer_ids) > 1) && !empty($appointment['entertainer_id'])) {
        // Get the specific entertainer by ID
        $name_sql = "SELECT entertainer_id, CONCAT(first_name, ' ', last_name, ' (', title, ')') as full_name_with_title 
                    FROM entertainer_account 
                    WHERE entertainer_id = ?";
        $name_stmt = $conn->prepare($name_sql);
        $name_stmt->bind_param("i", $appointment['entertainer_id']);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        if ($name_result->num_rows > 0) {
            $name_row = $name_result->fetch_assoc();
            $entertainer_ids = [$name_row['entertainer_id']];
            $matched_names = [$name_row['full_name_with_title']];
            error_log("Found name from booking ID: " . $name_row['full_name_with_title']);
        }
        $name_stmt->close();
    }
    
    // If we still don't have any entertainer IDs, try to find them by exact name match
    if (empty($entertainer_ids)) {
        $names = array_map('trim', explode(',', $entertainer_name));
        
        // For each name in the package, find the corresponding entertainer
        $entertainer_ids = [];
        $matched_names = [];
        
        foreach ($names as $name) {
            // Try to find by full name
            $id_sql = "SELECT entertainer_id, CONCAT(first_name, ' ', last_name, ' (', title, ')') as full_name_with_title 
                       FROM entertainer_account 
                       WHERE CONCAT(first_name, ' ', last_name) = ? OR 
                             CONCAT(last_name, ' ', first_name) = ? OR
                             LOWER(CONCAT(first_name, ' ', last_name)) = LOWER(?) OR
                             LOWER(CONCAT(last_name, ' ', first_name)) = LOWER(?)";
            $id_stmt = $conn->prepare($id_sql);
            $id_stmt->bind_param("ssss", $name, $name, $name, $name);
            $id_stmt->execute();
            $id_result = $id_stmt->get_result();
            
            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $entertainer_ids[] = $row['entertainer_id'];
                $matched_names[] = $row['full_name_with_title'];
                error_log("Found entertainer by name: " . $name . " -> " . $row['entertainer_id']);
            } else {
                // Try to find by partial name match
                $name_parts = explode(' ', $name);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $id_sql = "SELECT entertainer_id, CONCAT(first_name, ' ', last_name, ' (', title, ')') as full_name_with_title 
                           FROM entertainer_account 
                           WHERE (first_name LIKE ? OR last_name LIKE ?)";
                $id_stmt = $conn->prepare($id_sql);
                $first_name_param = "%$first_name%";
                $last_name_param = "%$last_name%";
                $id_stmt->bind_param("ss", $first_name_param, $last_name_param);
                $id_stmt->execute();
                $id_result = $id_stmt->get_result();
                
                if ($id_result->num_rows > 0) {
                    $row = $id_result->fetch_assoc();
                    $entertainer_ids[] = $row['entertainer_id'];
                    $matched_names[] = $row['full_name_with_title'];
                    error_log("Found entertainer by partial name: " . $name . " -> " . $row['entertainer_id']);
                }
            }
            $id_stmt->close();
        }
        
        if (!empty($entertainer_ids)) {
            error_log("Found IDs by name match: " . implode(',', $entertainer_ids));
            error_log("Matched names with title: " . implode(',', $matched_names));
        }
    }
} else {
    echo "Appointment not found.";
    exit();
}

$stmt->close();

// Check if this is a package booking
$is_package_booking = !empty($appointment['package']);

// Flag to bypass availability check
$bypass_availability_check = false;

// For package bookings, we'll skip the entertainer availability check
if ($is_package_booking) {
    error_log("Package booking detected: " . $appointment['package']);
    $bypass_availability_check = true;
}

// If the entertainer_name contains commas, it's a booking with multiple entertainers
if (strpos($entertainer_name, ',') !== false) {
    error_log("Multiple entertainers detected: " . $entertainer_name);
    $bypass_availability_check = true;
}

// If we don't have entertainer IDs and we're not bypassing the check, show an error
if (empty($entertainer_ids) && !$bypass_availability_check) {
    die("Error: Could not find entertainer ID for: " . $entertainer_name);
}

function checkEntertainerAvailability($conn, $entertainer_ids, $entertainer_names, $date, $start_time, $end_time, $current_booking_id) {
    global $bypass_availability_check;
    
    // If we're bypassing the availability check, return available
    if ($bypass_availability_check) {
        return ['available' => true, 'message' => ''];
    }
    // First check if all entertainers have set this date as Available
    foreach ($entertainer_ids as $key => $entertainer_id) {
        $entertainer_name = $entertainer_names[$key] ?? 'Unknown Entertainer';
        
        $sched_sql = "SELECT status FROM sched_time 
                      WHERE entertainer_id = ? AND date = ?";
        $sched_stmt = $conn->prepare($sched_sql);
        $sched_stmt->bind_param("is", $entertainer_id, $date);
        $sched_stmt->execute();
        $sched_result = $sched_stmt->get_result();
        
        if ($sched_result->num_rows === 0) {
            return ['available' => false, 'message' => $entertainer_name . ' has not set their schedule for ' . date('l, F j, Y', strtotime($date))];
        }
        
        $schedule = $sched_result->fetch_assoc();
        if ($schedule['status'] !== 'Available') {
            return ['available' => false, 'message' => $entertainer_name . '\'s schedule is ' . $schedule['status'] . ' for ' . date('l, F j, Y', strtotime($date))];
        }
        $sched_stmt->close();
    }
    
    // Then check for any conflicting bookings for any of the entertainers
    foreach ($entertainer_ids as $key => $entertainer_id) {
        $entertainer_name = $entertainer_names[$key] ?? 'Unknown Entertainer';
        
        $sql = "SELECT br.*, CONCAT(ea.first_name, ' ', ea.last_name, ' (', ea.title, ')') as entertainer_full_name 
                FROM booking_report br
                JOIN entertainer_account ea ON br.entertainer_id = ea.entertainer_id
                WHERE br.entertainer_id = ? 
                AND br.date_schedule = ? 
                AND br.book_id != ?
                AND br.status = 'Approved'
                AND ((br.time_start <= ? AND br.time_end > ?) 
                    OR (br.time_start < ? AND br.time_end >= ?)
                    OR (br.time_start >= ? AND br.time_end <= ?))";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iississss", 
            $entertainer_id, 
            $date, 
            $current_booking_id,
            $end_time, 
            $start_time,
            $end_time, 
            $end_time,
            $start_time, 
            $end_time
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conflict = $result->fetch_assoc();
            return [
                'available' => false, 
                'message' => $conflict['entertainer_full_name'] . ' already has an approved booking on ' . 
                           date('l, F j, Y', strtotime($date)) . 
                           ' from ' . date('h:i A', strtotime($conflict['time_start'])) . 
                           ' to ' . date('h:i A', strtotime($conflict['time_end']))
            ];
        }
        $stmt->close();
    }
    
    return ['available' => true, 'message' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['new_date'];
    $new_time_start = $_POST['new_time_start'];
    $new_time_end = $_POST['new_time_end'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : 'Customer requested reschedule';

    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check entertainer availability
    $availability = checkEntertainerAvailability($conn, $entertainer_ids, $matched_names, $new_date, $new_time_start, $new_time_end, $appointment_id);
    if (!$availability['available']) {
        header("Location: reschedule-appointment.php?appointment_id=$appointment_id&error=" . urlencode($availability['message']));
        exit();
    }

    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // Save the previous appointment details to history
        $history_sql = "INSERT INTO appointment_history (book_id, previous_date, previous_time_start, previous_time_end, action_type, reason) 
                        VALUES (?, ?, ?, ?, 'Reschedule', ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("issss", 
            $appointment_id, 
            $appointment['date_schedule'], 
            $appointment['time_start'], 
            $appointment['time_end'], 
            $reason
        );
        $history_stmt->execute();
        $history_stmt->close();
        
        // Proceed with rescheduling logic
        $sql = "UPDATE booking_report 
                SET date_schedule = ?, 
                    time_start = ?, 
                    time_end = ?,
                    status = 'Pending',
                    remarks = 'Reschedule'
                WHERE book_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $new_date, $new_time_start, $new_time_end, $appointment_id);
        
        if ($stmt->execute()) {
            // Send email notification to admin
            try {
                // Get admin email
                $admin_sql = "SELECT email FROM admin_account WHERE admin_id = 1";
                $admin_result = $conn->query($admin_sql);
                
                if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
                    $admin_email = $admin_row['email'];
                    
                    // Prepare email content
                    $subject = "Appointment Reschedule Notification";
                    $body = "Dear Admin,<br><br>";
                    $body .= "An appointment has been rescheduled. Here are the details:<br><br>";
                    $body .= "<b>Appointment ID:</b> " . $appointment_id . "<br>";
                    $body .= "<b>Customer Name:</b> " . $_SESSION['first_name'] . (isset($_SESSION['last_name']) ? " " . $_SESSION['last_name'] : "") . "<br><br>";
                    
                    $body .= "<b>Previous Schedule:</b><br>";
                    $body .= "Date: " . date('F j, Y', strtotime($appointment['date_schedule'])) . "<br>";
                    $body .= "Time: " . date('h:i A', strtotime($appointment['time_start'])) . " - " . 
                               date('h:i A', strtotime($appointment['time_end'])) . "<br><br>";
                    
                    $body .= "<b>New Schedule:</b><br>";
                    $body .= "Date: " . date('F j, Y', strtotime($new_date)) . "<br>";
                    $body .= "Time: " . date('h:i A', strtotime($new_time_start)) . " - " . 
                               date('h:i A', strtotime($new_time_end)) . "<br><br>";
                    
                    $body .= "<b>Reason for Rescheduling:</b> " . $reason . "<br><br>";
                    $body .= "Please review this rescheduled appointment in your admin dashboard.<br><br>";
                    $body .= "Best regards,<br>Booking System";

                    // Send email notification if mail-config.php exists
                    if (file_exists('../includes/mail-config.php')) {
                        require_once '../includes/mail-config.php';
                        if (function_exists('sendEmail')) {
                            sendEmail($admin_email, $subject, $body);
                        }
                    }
                }
            } catch (Exception $emailEx) {
                // Log email error but continue with rescheduling
                error_log("Email sending failed: " . $emailEx->getMessage());
            }
            
            $conn->commit();
            $_SESSION['message'] = "Appointment rescheduled successfully. Waiting for approval.";
        } else {
            $conn->rollback();
            $_SESSION['message'] = "Error rescheduling appointment: " . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
    
    $conn->close();

    header("Location: customer-appointment.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --error-color: #ff4444;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            max-width: 600px;
            padding: 15px;
            margin: 20px auto;
            box-sizing: border-box;
        }

        form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .appointment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }

        .appointment-details h3 {
            margin-top: 0;
            color: var(--text-color);
            font-size: 18px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }

        input[type="date"],
        input[type="time"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="date"]:focus,
        input[type="time"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .submit-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        form > * {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
                height: auto;
                min-height: 100vh;
            }

            .container {
                padding: 10px;
                margin: 10px auto;
            }

            form {
                padding: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            button {
                width: 100%;
            }
        }
        
        .alert {
            margin: 20px;
        }
        .fade-out {
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        .fade-out.hide {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger mt-3 fade-out" role="alert" id="error-alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <form action="reschedule-appointment.php?appointment_id=<?php echo $appointment_id; ?>" method="POST">
            <h1>Reschedule Appointment</h1>
            
            <div class="appointment-details">
                <h3>Current Appointment Details</h3>
                <div class="form-group">
                    <label>Entertainer:</label>
                    <div class="form-control-static">
                        <?php 
                        if (!empty($matched_names)) {
                            echo htmlspecialchars(implode(', ', $matched_names));
                        } else {
                            echo htmlspecialchars($entertainer_name);
                        }
                        ?>
                    </div>
                </div>
                <p><strong>Current Date:</strong> <?php echo htmlspecialchars($appointment['date_schedule']); ?></p>
                <p><strong>Current Time:</strong> 
                    <?php 
                        $start_time = date('h:i A', strtotime($appointment['time_start']));
                        $end_time = date('h:i A', strtotime($appointment['time_end']));
                        echo "$start_time - $end_time"; 
                    ?>
                </p>
                <p><strong>Package:</strong> <?php echo !empty($appointment['package']) ? htmlspecialchars($appointment['package']) : 'No Package'; ?></p>
                <p><strong>Roles:</strong> <?php echo htmlspecialchars($appointment['roles']); ?></p>
                <?php if (empty($appointment['package'])): ?>
                <p><strong>Performance Duration:</strong> <?php echo htmlspecialchars($appointment['perform_durations']); ?></p>
                <?php endif; ?>
            </div>

            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
            
            <div class="form-group">
                <label for="new_date">New Date:</label>
                <input type="date" id="new_date" name="new_date" required 
                       min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="new_time_start">New Start Time:</label>
                <input type="time" id="new_time_start" name="new_time_start" required>
            </div>

            <div class="form-group">
                <label for="new_time_end">New End Time:</label>
                <input type="time" id="new_time_end" name="new_time_end" required>
            </div>

            <div class="form-group">
                <label for="reason">Reason for Rescheduling:</label>
                <textarea id="reason" name="reason" rows="4" required 
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"
                    placeholder="Please provide a reason for rescheduling this appointment..."></textarea>
            </div>

            <div class="buttons">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Confirm Reschedule</button>
            </div>
        </form>
    </div>

    <script>
        async function checkAvailability(date, startTime, endTime) {
            // Check if we should bypass the availability check
            const bypassCheck = <?php echo $bypass_availability_check ? 'true' : 'false'; ?>;
            
            // If we're bypassing the check (for packages or multiple entertainers), return available
            if (bypassCheck) {
                console.log('Bypassing availability check');
                return {
                    available: true,
                    message: ''
                };
            }
            
            const formData = new FormData();
            formData.append('date', date);
            formData.append('start_time', startTime);
            formData.append('end_time', endTime);
            formData.append('entertainer_name', '<?php echo addslashes($entertainer_name); ?>');
            formData.append('appointment_id', '<?php echo $appointment_id; ?>');

            try {
                const submitBtn = document.querySelector('.submit-btn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';

                const response = await fetch('check-entertainer-availability.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Reschedule';

                if (!data.success) {
                    return {
                        available: false,
                        message: data.error || 'An unexpected error occurred'
                    };
                }

                return {
                    available: data.available,
                    message: data.error || ''
                };
            } catch (error) {
                console.error('Error checking availability:', error);
                const submitBtn = document.querySelector('.submit-btn');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Reschedule';
                return {
                    available: false,
                    message: 'Error checking entertainer availability. Please try again.'
                };
            }
        }

        // Set minimum date to today
        document.getElementById('new_date').min = new Date().toISOString().split('T')[0];

        // Add event listener to cancel button
        document.querySelector('.cancel-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const confirmCancel = confirm("Are you sure you want to cancel rescheduling?");
            if (confirmCancel) {
                window.location.href = 'customer-appointment.php';
            }
        });

        // Add form submit event listener
        document.querySelector('form').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const startTime = document.getElementById('new_time_start').value;
            const endTime = document.getElementById('new_time_end').value;
            const date = document.getElementById('new_date').value;
            
            // Basic time validation - just check that end time is after start time
            if (startTime >= endTime) {
                alert('End time must be later than start time');
                return;
            }

            // Validate minimum duration (1 hour)
            const startDateTime = new Date(`2000-01-01T${startTime}`);
            const endDateTime = new Date(`2000-01-01T${endTime}`);
            const diffInMinutes = (endDateTime - startDateTime) / (1000 * 60);
            
            if (diffInMinutes < 60) {
                alert('Appointment duration must be at least 1 hour');
                return;
            }

            // Check entertainer availability
            const result = await checkAvailability(date, startTime, endTime);
            
            if (!result.available) {
                alert(result.message || 'The entertainer is not available at the selected time. Please choose a different time.');
                return;
            }

            // If all validations pass, submit the form
            this.submit();
        });
    </script>
    <script>
        // Auto-hide alert after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('error-alert');
            if (alert) {
                setTimeout(function() {
                    alert.classList.add('hide');
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500); // Wait for fade animation to complete
                }, 3000);
            }
        });
    </script>
</body>
</html>
