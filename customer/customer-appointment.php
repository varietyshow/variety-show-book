<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database connection
$servername = "localhost"; // Update with your server name
$username = "root"; // Update with your database username
$password = ""; // Update with your database password
$dbname = "db_booking_system"; // Update with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add this near the top of the file after database connection
$rolesQuery = "SELECT role_name, rate, duration, duration_unit FROM roles";
$rolesResult = $conn->query($rolesQuery);

$defaultRates = [];
$defaultDurations = [];

if ($rolesResult->num_rows > 0) {
    while($row = $rolesResult->fetch_assoc()) {
        $roleName = strtolower($row['role_name']);
        $defaultRates[$roleName] = floatval($row['rate']);
        $defaultDurations[$roleName] = $row['duration'] . ' ' . $row['duration_unit'];
    }
}

// Convert PHP arrays to JavaScript objects
$defaultRatesJSON = json_encode($defaultRates);
$defaultDurationsJSON = json_encode($defaultDurations);

// Pagination settings
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Get total number of records
$total_query = "SELECT COUNT(*) as total FROM booking_report WHERE customer_id = ?";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("i", $_SESSION['customer_id']);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Modify the main query to include pagination
$sql = "SELECT br.* 
        FROM booking_report br 
        WHERE br.customer_id = ?
        ORDER BY br.date_schedule DESC, br.time_start DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    die("Failed to prepare statement");
}

$stmt->bind_param("iii", $_SESSION['customer_id'], $results_per_page, $offset);
if (!$stmt->execute()) {
    error_log("Failed to execute statement: " . $stmt->error);
    die("Failed to execute statement");
}
$result = $stmt->get_result();

error_log("Found " . $result->num_rows . " bookings for customer");

// Display message if it exists
if (isset($_SESSION['message'])) {
    echo "<div id='message' class='alert'>" . $_SESSION['message'] . "</div>";
    unset($_SESSION['message']); // Clear the message after displaying it
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style2.css">

</head>
<style>
         body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            overflow: auto;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #333;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            position: relative;
            gap: 20px;
        }

        .navbar-brand img {
            height: 40px;
        }

        nav {
            display: flex;
            align-items: center;
            flex-grow: 1;
            justify-content: flex-end;
            gap: 15px;
        }

        .nav-items {
            display: flex;
            gap: 15px;
            margin-right: 65px;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-items a:hover {
            background-color: #555;
        }

        .dropdown {
            margin-left: auto;
        }

        .menu-toggle {
            display: none;
            border: none;
            background: none;
            cursor: pointer;
            padding: 10px;
            margin-right: -10px;
            margin-left: auto;
            position: relative;
            z-index: 1000;
        }

        .menu-toggle .bar {
            display: block;
            width: 24px;
            height: 2px;
            background-color: #fff;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .menu-toggle.active .bar:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active .bar:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active .bar:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .nav-items {
                display: none;
                position: absolute;
                top: 100%;
                left: auto;
                right: -10px;
                width: 140px;
                background-color: #333;
                flex-direction: column;
                padding: 0;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                border-radius: 4px;
                margin-top: -1px;
            }

            .nav-items.show {
                display: flex;
            }

            .nav-items a {
                padding: 8px 12px;
                width: 100%;
                text-align: left;
                color: #fff;
                font-size: 12px;
                background-color: #333;
                transition: all 0.2s ease;
            }

            .nav-items a + a {
                margin-top: 0;
            }

            .nav-items a:hover {
                background-color: #87CEFA;
                color: #000;
                padding-left: 20px;
            }

            .dropdown {
                display: none;
            }

            .mobile-profile-links {
                display: block;
                width: 100%;
            }

            .mobile-profile-links a {
                display: block;
                width: 100%;
                padding: 4px 12px;
                color: #fff;
                text-decoration: none;
                font-size: 12px;
                background-color: #333;
                transition: all 0.2s ease;
            }

            .mobile-profile-links a + a {
                margin-top: 0;
            }

            .mobile-profile-links a:hover {
                background-color: #87CEFA;
                color: #000;
                padding-left: 20px;
            }

            .mobile-profile-links a:last-child {
                border-radius: 0 0 4px 4px;
            }
        }

        @media (min-width: 769px) {
            .mobile-profile-links {
                display: none;
            }
        }

        .content {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            margin-left: 20px;
            padding: 20px;
            padding-top: 10px;
            min-height: 100vh;
            overflow-y: auto;
        }

        /* Show scrollbar when content overflows */
        .content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .schedule-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin: 20px;
            overflow-x: auto;
        }

        /* Mobile-friendly styles for appointment container */
        @media (max-width: 768px) {
            .schedule-container {
                padding: 15px;
                margin: 10px;
                border-radius: 10px;
            }

            .schedule-container h2 {
                font-size: 1.5rem;
                margin-bottom: 15px;
                text-align: center;
            }

            /* Make tables responsive */
            table {
                width: 100%;
                display: block;
                overflow-x: auto;
            }

            table th, table td {
                min-width: 120px;
                padding: 8px;
                font-size: 14px;
            }

            /* Style buttons in mobile view */
            .view-btn, .cancel-btn, .view-reason-btn {
                padding: 6px 12px;
                font-size: 13px;
                margin: 2px;
                white-space: nowrap;
            }

            /* Improve status filter responsiveness */
            .status-filter {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-bottom: 15px;
                justify-content: center;
            }

            .status-filter button {
                padding: 6px 12px;
                font-size: 13px;
                flex: 1 1 auto;
                min-width: 80px;
                max-width: 150px;
            }

            /* Improve modal responsiveness */
            #appointment-modal .modal-content {
                width: 90%;
                max-width: 400px;
                margin: 20px auto;
                padding: 15px;
            }

            #appointment-modal .close {
                font-size: 24px;
                padding: 5px;
            }

            /* Style appointment details in modal */
            .appointment-details {
                font-size: 14px;
                line-height: 1.4;
            }

            .appointment-details p {
                margin: 8px 0;
            }
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

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .pagination span {
            color: #888;
        }

        .pagination-controls {
            display: flex;
            gap: 5px;
        }

        .pagination-controls img {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .header {
                width: 100%;
                left: 0;
            }

            .content {
                margin-left: 0;
                padding-top: 60px; /* Adjust padding for smaller screens */
                padding: 10px; /* Add padding to the content */
            }

            .schedule-container {
                width: 100%; /* Full width for small screens */
                padding: 10px; /* Adjust padding */
            }

            .schedule-header input[type="text"] {
                width: 100%; /* Full width input for smaller screens */
            }

            .pagination {
                flex-direction: row; /* Stack pagination controls vertically */
                align-items: flex-start;
            }

            .pagination-controls {
                width: 100%;
                justify-content: space-between; /* Space out controls */
            }
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
            gap: 0px; /* Space between items */
            margin-right: 80px; /* Adjust this value to increase space from the profile image */
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

        .dropbtn img {
            width: 40px; /* Adjust image size */
            height: 40px; /* Adjust size as needed */
            border-radius: 40%; /* Make the image circular */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

        .status-selection {
            margin-top: 20px;
        }

        .status-selection label {
            margin-right: 10px;
        }

        .status-selection select {
            padding: 10px;
            margin-right: 10px;
        }

        .edit-btn {
            background-color: #007bff; /* Blue background */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            min-width: 100px;
            font-size: 14px;
            font-weight: normal;
            text-align: center;
            margin: 2px;
        }

        .edit-btn:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .cancel-btn {
            background-color: red; /* Red background */
            color: white; /* White text */
            border: none; /* Remove border */
            padding: 10px 15px; /* Add padding */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s; /* Smooth transition */
        }

        .cancel-btn:hover {
            background-color: darkred; /* Darker red on hover */
        }

        .alert {
    padding: 10px;
    background-color: #4CAF50; /* Green background */
    color: white; /* White text */
    margin-bottom: 15px;
    border-radius: 5px;
    text-align: center;
}

.view-reason-btn {
    background-color: #808080; /* Gray background */
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.view-reason-btn:hover {
    background-color: #666666; /* Darker gray on hover */
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7);
}

.modal-content {
    background-color: #fff;
    margin: 60px auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: relative;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #ff3333;
    text-decoration: none;
    outline: none;
}

.view-btn {
    background-color: #808080; /* Changed to match view-reason-btn gray color */
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: normal;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    min-width: 100px;
    justify-content: center;
}

.view-btn:hover {
    background-color: #666666; /* Changed to match view-reason-btn hover color */
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.view-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}



/* Make all action buttons consistent */
.view-btn,
.view-reason-btn,
.edit-btn,
.cancel-btn {
    min-width: 100px;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: normal;
    text-align: center;
    margin: 2px;
}

.view-reason-btn {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.view-reason-btn:hover {
    background-color: #e9ecef;
    border-color: #ced4da;
    color: #212529;
}

@media (max-width: 768px) {
    #reason-modal .modal-content {
        width: 95%;
        margin: 0 10px;
    }
}

  /* Mobile-friendly header styles */
  header {
    display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 0;
            top: 0;
            background-color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            z-index: 1000;
        }

        .navbar-brand {
            padding: 10px 20px;
        }

        .navbar-brand img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
        }

        .menu-toggle {
            display: none;
            border: none;
            background: none;
            cursor: pointer;
            padding: 10px;
            margin-right: -10px;
            margin-left: auto;
            position: relative;
            z-index: 1000;
        }

        .menu-toggle .bar {
            display: block;
            width: 24px;
            height: 2px;
            background-color: #fff;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .menu-toggle.active .bar:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active .bar:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active .bar:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
        }

        .nav-items {
            display: flex;
            align-items: center;
            margin-left: auto;
            padding-right: 20px;
            margin-right: 10px;
        }

        .nav-items a {
            color: #fff;
            text-decoration: none;
            padding: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-items a:hover {
            color: #fff;
            background-color: #87CEFA;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            display: flex;
            align-items: center;
        }

        .dropbtn img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #333;
            min-width: 160px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 4px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #fff;
            padding: 6px 12px;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background-color: #87CEFA;
            color: #000;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
                margin-right: 15px;
            }

            .menu-toggle i {
                font-size: 24px;
                color: #fff;
            }

            .nav-items {
                display: none;
                position: absolute;
                top: 100%;
                left: auto;
                right: -10px;
                width: 140px;
                background-color: #333;
                flex-direction: column;
                padding: 0;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                border-radius: 4px;
                margin-top: -1px;
            }

            .nav-items.show {
                display: flex;
            }

            .nav-items a {
                padding: 8px 12px;
                width: 100%;
                text-align: left;
                color: #fff;
                font-size: 12px;
                background-color: #333;
                transition: all 0.2s ease;
            }

            .nav-items a + a {
                margin-top: 0;
            }

            .nav-items a:hover {
                background-color: #87CEFA;
                color: #000;
                padding-left: 20px;
            }

            .dropdown {
                display: none;
            }

            .mobile-profile-links {
                display: block;
                width: 100%;
            }

            .mobile-profile-links a {
                display: block;
                width: 100%;
                padding: 4px 12px;
                color: #fff;
                text-decoration: none;
                font-size: 12px;
                background-color: #333;
                transition: all 0.2s ease;
            }

            .mobile-profile-links a + a {
                margin-top: 0;
            }

            .mobile-profile-links a:hover {
                background-color: #87CEFA;
                color: #000;
                padding-left: 20px;
            }

            .mobile-profile-links a:last-child {
                border-radius: 0 0 4px 4px;
            }
        }

        @media (min-width: 769px) {
            .mobile-profile-links {
                display: none;
            }
        }

        @media screen and (min-width: 769px) {
            .nav-items {
                display: flex;
                justify-content: flex-end;
                gap: 20px;
            }
            
            .dropdown {
                margin-left: 20px;
            }
        }

        @media screen and (max-width: 768px) {
            .nav-items {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dropdown {
                margin-left: 0;
            }
        }
</style>
<body>
<header>
        <a class="navbar-brand" href="#">
            <img src="../images/logo.jpg" alt="Brand Logo">
        </a>
        <button class="menu-toggle" onclick="toggleMenu()">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <nav class="nav-items">
            <a href="customer-gallery.php">Entertainer Gallery</a>
            <a href="customer-booking.php">Book Appointment</a>
            <a href="customer-appointment.php">My Appointment</a>
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdown-content">
                    <a href="customer-profile.php">View Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <div class="mobile-profile-links">
                <a href="customer-profile.php">View Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>
    </main>

    <div class="content">
        <div class="schedule-container">
            <h2>Appointments</h2>

                    <!-- Status Selection -->
        <div class="status-selection">
        <select id="status-select" onchange="filterAppointments()">
                <option value="">Select All</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Declined">Declined</option>
            </select>
        </div>

        <div id="no-appointments-message" style="display:none; color: red;">
            No appointments found for the selected status.
        </div>

            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time Start</th>
                        <th>Time End</th>
                        <th>Entertainer</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body">
                <?php if ($result->num_rows > 0): ?>
                    <?php foreach ($result as $row): ?>
                        <tr data-status="<?php echo htmlspecialchars($row['status']); ?>">
                            <td><?php echo htmlspecialchars($row['date_schedule']); ?></td>
                            <td><?php 
                                $timeStart = DateTime::createFromFormat('H:i:s', $row['time_start']);
                                echo $timeStart ? $timeStart->format('h:i A') : ''; 
                            ?></td>
                            <td><?php 
                                $timeEnd = DateTime::createFromFormat('H:i:s', $row['time_end']);
                                echo $timeEnd ? $timeEnd->format('h:i A') : ''; 
                            ?></td>
                            <td><?php echo htmlspecialchars($row['entertainer_name']); ?></td>
                            <td>
                                <?php 
                                    $status = htmlspecialchars($row['status']);
                                    $statusColor = '';
                                    switch($status) {
                                        case 'Approved':
                                            $statusColor = 'color: green; font-weight: bold;';
                                            break;
                                        case 'Pending':
                                            $statusColor = 'color: orange; font-weight: bold;';
                                            break;
                                        case 'Declined':
                                        case 'Cancelled':
                                            $statusColor = 'color: red; font-weight: bold;';
                                            break;
                                    }
                                    echo "<span style='$statusColor'>$status</span>";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                            <td>
                                <?php 
                                $status = strtolower($row['status']);
                                if (($status === 'cancelled' || $status === 'declined') && !empty($row['reason'])): 
                                ?>
                                    <button class='view-reason-btn' onclick='viewReason(<?php echo json_encode($row['reason']); ?>)'>
                                        View Reason
                                    </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'Approved'): ?>
                                    <button class='view-btn' onclick='window.location.href="view-appointment-details.php?id=<?php echo $row["book_id"]; ?>"'>
                                        View Details
                                    </button>
                                    <button class='edit-btn' onclick='window.location.href="reschedule-appointment.php?appointment_id=<?php echo $row["book_id"]; ?>"'>Reschedule</button>
                                <?php elseif ($row['status'] === 'Pending'): ?>
                                    <button class='view-btn' onclick='window.location.href="view-appointment-details.php?id=<?php echo $row["book_id"]; ?>"'>
                                        View Details
                                    </button>
                                    <button class='edit-btn' onclick='window.location.href="reschedule-appointment.php?appointment_id=<?php echo $row["book_id"]; ?>"'>Reschedule</button>
                                    <button class='cancel-btn' onclick='cancelAppointment(<?php echo $row["book_id"]; ?>)'>Cancel</button>
                                <?php elseif ($row['status'] === 'Cancelled' || $row['status'] === 'Declined'): ?>
                                    <button class='view-btn' onclick='window.location.href="view-appointment-details.php?id=<?php echo $row["book_id"]; ?>"'>
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No appointments found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination controls -->
        <div class="pagination">
            <?php if($total_pages > 1): ?>
                <?php if($page > 1): ?>
                    <a href="?page=1">&laquo; First</a>
                    <a href="?page=<?php echo $page-1; ?>">&lsaquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; First</span>
                    <span class="disabled">&lsaquo; Prev</span>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>">Next &rsaquo;</a>
                    <a href="?page=<?php echo $total_pages; ?>">Last &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &rsaquo;</span>
                    <span class="disabled">Last &raquo;</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

   <!-- GCash Payment Modal -->
<div id="gcash-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
    <div style="background: white; padding: 20px; border-radius: 8px; width: 300px; text-align: center;">
        <h2>Down Payment via GCash</h2>
        
        <!-- QR Code Image -->
        <img src="../images/qrcode.png" alt="GCash QR Code" style="width: 50%; margin-bottom: 10px;"/>

        <p>Scan the QR code or enter your GCash number for the down payment.</p>
        <input type="text" id="gcash-number" placeholder="GCash Number" required style="width: 100%; margin-bottom: 10px; padding: 8px;"/>
        <input type="number" id="amount" placeholder="Amount" required style="width: 100%; margin-bottom: 10px; padding: 8px;"/>
        <button onclick="processDownPayment()">Pay Now</button>
        <button onclick="closeGcashModal()">Cancel</button>
    </div>
</div>

<!-- Add this modal HTML code just before the GCash modal -->
<div id="reason-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reason for <?php echo isset($status) ? ucfirst(strtolower($status)) : 'Status Change'; ?></h2>
            <button class="close-btn" onclick="closeReasonModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="reason-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <p id="reasonText" class="reason-text"></p>
        </div>
    </div>
</div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            const dropdownContent = document.getElementById('dropdown-content');
            dropdown.classList.toggle('show');
        }
        
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });
            }
            
            // Close modals when clicking outside
            const reasonModal = document.getElementById('reason-modal');
            const gcashModal = document.getElementById('gcash-modal');
            if (event.target === reasonModal) {
                closeReasonModal();
            }
            if (event.target === gcashModal) {
                closeGcashModal();
            }
            
            const detailsModal = document.getElementById('details-modal');
            if (event.target === detailsModal) {
                closeDetailsModal();
            }
            
            const viewDetailsModal = document.getElementById('viewDetailsModal');
            if (event.target === viewDetailsModal) {
                closeViewDetailsModal();
            }
        }

        function filterAppointments() {
            const selectedStatus = document.getElementById('status-select').value;
            const rows = document.querySelectorAll('#schedule-table-body tr[data-status]');
            let hasAppointments = false;

            // Remove any existing message row
            const existingMessageRow = document.querySelector('.message-row');
            if (existingMessageRow) {
                existingMessageRow.remove();
            }

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (selectedStatus === "" || status === selectedStatus) {
                    row.style.display = ""; // Show row
                    hasAppointments = true;
                } else {
                    row.style.display = "none"; // Hide row
                }
            });

            // If no appointments are visible, add a single message row
            if (!hasAppointments) {
                const tbody = document.getElementById('schedule-table-body');
                const messageRow = document.createElement('tr');
                messageRow.className = 'message-row';
                messageRow.innerHTML = `
                    <td colspan="7" style="text-align: center; background-color: #f0f0f0; color: gray;">
                        No appointments found for the selected status.
                    </td>
                `;
                tbody.appendChild(messageRow);
            }
        }

        function rescheduleAppointment(appointmentId) {
    // Logic to handle rescheduling the appointment
    alert("Reschedule appointment ID: " + appointmentId);
}

function cancelAppointment(appointmentId) {
    if (confirm('Are you sure you want to cancel this appointment?')) {
        // Create and show the modal for entering cancellation reason
        const modalHtml = `
            <div id="cancel-reason-modal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
                <div style="background: white; padding: 20px; border-radius: 8px; width: 500px; max-height: 80vh; overflow-y: auto;">
                    <h2 style="margin-bottom: 20px;">Enter Reason for Cancellation</h2>
                    <textarea 
                        id="cancel-reason-text" 
                        style="width: 100%; 
                        padding: 10px; 
                        margin-bottom: 20px; 
                        border: 1px solid #ddd; 
                        border-radius: 4px; 
                        min-height: 100px; 
                        resize: vertical;"
                        placeholder="Please provide a reason for cancelling this appointment..."
                    ></textarea>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button onclick="closeReasonModal()" 
                            style="padding: 8px 15px; 
                            border-radius: 4px; 
                            border: 1px solid #ddd; 
                            background: #f5f5f5; 
                            cursor: pointer;">Cancel</button>
                        <button onclick="submitCancellation(${appointmentId})" 
                            style="padding: 8px 15px; 
                            border-radius: 4px; 
                            border: none; 
                            background: #dc3545; 
                            color: white; 
                            cursor: pointer;">Submit</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
}

function closeReasonModal() {
    // Handle both types of modals (reason view modal and cancel reason modal)
    const reasonViewModal = document.getElementById('reason-modal');
    const cancelReasonModal = document.getElementById('cancel-reason-modal');
    
    if (reasonViewModal) {
        reasonViewModal.style.display = 'none';
    }
    
    if (cancelReasonModal) {
        cancelReasonModal.remove(); // Remove the dynamically added modal
    }
}

function submitCancellation(appointmentId) {
    const reasonText = document.getElementById('cancel-reason-text').value.trim();
    const submitButton = document.querySelector(`button[onclick="submitCancellation(${appointmentId})"]`);
    
    if (!reasonText) {
        alert('Please enter a reason for cancellation.');
        return;
    }

    // Disable the submit button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = 'Cancelling...';
    
    // Send the cancellation request to the server
    fetch('cancel-appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `appointment_id=${appointmentId}&reason=${encodeURIComponent(reasonText)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Appointment cancelled successfully');
            location.reload(); // Refresh the page to show updated status
        } else {
            alert(data.message || 'Failed to cancel appointment');
            // Re-enable the button on failure
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the appointment');
        // Re-enable the button on error
        submitButton.disabled = false;
        submitButton.innerHTML = 'Submit';
    })
    .finally(() => {
        closeReasonModal();
    });
}

function downPayment(appointmentId) {
    // Display the GCash modal for down payment
    const modal = document.getElementById('gcash-modal');
    modal.style.display = 'flex';
    modal.setAttribute('data-appointment-id', appointmentId); // Store the appointment ID in the modal
}

function closeGcashModal() {
    const modal = document.getElementById('gcash-modal');
    modal.style.display = 'none'; // Hide the modal
}

function processDownPayment() {
    const appointmentId = document.getElementById('gcash-modal').getAttribute('data-appointment-id');
    const gcashNumber = document.getElementById('gcash-number').value;
    const amount = document.getElementById('amount').value;

    // Simple validation
    if(!gcashNumber || !amount || amount <= 0) {
        alert('Please enter a valid GCash number and amount.');
        return;
    }

    // Simulate processing the payment
    alert(`Processing down payment of PHP ${amount} for appointment ID ${appointmentId} via GCash number ${gcashNumber}...`);
    
    // Here you could implement the API call to GCash or your payment processor

    // Close the modal after processing
    closeGcashModal();
}

function viewReason(reason) {
    const modal = document.getElementById('reason-modal');
    const reasonText = document.getElementById('reasonText');
    reasonText.textContent = reason || 'No reason provided';
    modal.style.display = 'flex';
    // Trigger reflow to ensure transition works
    modal.offsetHeight;
    modal.classList.add('show');
}

function closeReasonModal() {
    const modal = document.getElementById('reason-modal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300); // Match the transition duration
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reason-modal');
    if (event.target === modal) {
        closeReasonModal();
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReasonModal();
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('viewDetailsModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const message = document.getElementById('message');
    if (message) {
        setTimeout(() => {
            message.style.display = 'none'; // Hide the message after 2 seconds
        }, 2000);
    }
});

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    let hour = parseInt(hours, 10);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return `${hour}:${minutes} ${suffix}`;
}

function capitalizeWords(str) {
    return str.split(' ').map(word => {
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'approved':
            return 'green';
        case 'pending':
            return 'orange';
        case 'declined':
        case 'cancelled':
            return 'red';
        default:
            return 'black';
    }
}

function generatePaymentTableRows(appointment) {
    // Check if this is a package booking
    if (appointment.package && appointment.package.trim() !== '') {
        return `
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;" colspan="2">${capitalizeWords(appointment.package)}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">₱${parseFloat(appointment.total_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
    }

    // For non-package bookings, use the existing logic
    const entertainers = appointment.entertainer_name.split(',').map(e => e.trim());
    const allRoles = appointment.roles.split(',').map(r => r.trim());
    const allDurations = appointment.perform_durations ? appointment.perform_durations.split(',').map(d => d.trim()) : [];
    const roleRates = appointment.role_rates ? appointment.role_rates.split(',').map(rate => parseFloat(rate)) : [];
    
    let rows = '';
    let currentRoleIndex = 0;
    
    entertainers.forEach((entertainer) => {
        let isFirstRow = true;
        let roleCount = 0;
        
        while (currentRoleIndex + roleCount < allRoles.length) {
            if (roleCount >= 1 && allRoles[currentRoleIndex + roleCount].toLowerCase() === allRoles[currentRoleIndex].toLowerCase()) {
                break;
            }
            roleCount++;
        }
        
        for (let i = 0; i < roleCount; i++) {
            const role = allRoles[currentRoleIndex + i];
            const duration = allDurations[currentRoleIndex + i];
            const rate = roleRates[currentRoleIndex + i];
            
            const displayRate = rate || defaultRates[role.toLowerCase()] || 0;
            
            let durationDisplay = duration;
            if (!duration || duration === 'null null') {
                durationDisplay = defaultDurations[role.toLowerCase()] || 'N/A';
            }
            
            rows += `
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">
                        ${isFirstRow ? capitalizeWords(entertainer) : ''}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${capitalizeWords(role)}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${durationDisplay}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">₱${displayRate.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
            
            isFirstRow = false;
        }
        
        currentRoleIndex += roleCount;
    });
    
    return rows;
}

// Add these helper functions if not already present
function getDefaultRate(role) {
    return defaultRates[role.toLowerCase()] || 0;
}

function getDefaultDuration(role) {
    return defaultDurations[role.toLowerCase()] || 'N/A';
}

// Add these at the top of your JavaScript code
const defaultRates = <?php echo $defaultRatesJSON; ?>;
const defaultDurations = <?php echo $defaultDurationsJSON; ?>;

        // Toggle mobile menu
        function toggleMenu() {
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            navItems.classList.toggle('show');
            menuToggle.classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (!event.target.closest('.nav-items') && !event.target.closest('.menu-toggle')) {
                navItems.classList.remove('show');
                menuToggle.classList.remove('active');
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>