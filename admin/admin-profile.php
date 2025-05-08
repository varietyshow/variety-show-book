<?php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database configuration
$host = 'sql12.freesqldatabase.com'; // Your database host
$dbname = 'sql12777569'; // Your database name
$username = 'sql12777569'; // Your database username
$password = 'QlgHSeuU1n'; // Your database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data based on the session 
$sql = "SELECT * FROM admin_account WHERE first_name = '$first_name'";
$result = $conn->query($sql);


// Check if user was found
if ($result->num_rows > 0) {
    // Fetch user data
    $user_data = $result->fetch_assoc();
} else {
    echo "No user data found.";
    exit();
}

if (isset($_SESSION['msg'])) {
    echo '<div id="session-msg">' . $_SESSION['msg'] . '</div>';
    unset($_SESSION['msg']); // Clear the message after displaying it
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<style>
/* Main Content Styles */
.main-content {
            padding: 120px 40px;
            margin-top: 0;
            min-height: calc(100vh - 60px);
            background-color: #f5f5f5;
            transition: 0.3s;
        }

        .profile-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: -20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 100px 15px 20px;
            }

            .profile-container {
                padding: 15px;
                margin-top: -15px;
            }
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tabs a {
            padding: 10px 20px;
            text-decoration: none;
            color: #333;
            border-bottom: 2px solid transparent;
            transition: border-bottom 0.3s ease, background-color 0.3s ease, color 0.3s ease;
        }

        .tabs a.active {
            border-bottom: 2px solid #004080;
            color: #004080;
            background-color: #f2f2f2;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input[type="password"] {
            position: relative;
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        /* Buttons */
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #004080;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #003366;
        }

        .btn-secondary {
            background-color: #ddd;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #ccc;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-items {
            display: flex;
            gap: 30px;
            margin-right: 20px;
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
            height: auto; /* Maintain aspect ratio */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .tabs a {
                flex: 1;
                text-align: center;
            }

            .form-group {
                flex: 1 1 100%;
            }
        }

/* Ensure form-group is properly spaced and input fields don't touch edges */
.form-group {
    margin-bottom: 15px; /* Space between each form group */
}

.form-group label {
    margin-bottom: 5px;
    display: block;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    box-sizing: border-box; /* Include padding in width calculation */
}

.form-row {
    display: flex;
    gap: 20px; /* Space between horizontal items */
    margin-bottom: 15px; /* Space below each row */
}

.form-row .form-group {
    flex: 1;
    min-width: 0; /* Prevent flex items from growing too large */
}

.form-row .form-group input,
.form-row .form-group select {
    width: 100%;
}

/* Custom length for the Province input */
.input-province {
    width: 100%; /* Ensures input box takes the full width of its container */
    max-width: 500px; /* Adjust this value to control the maximum width of the input box */
    box-sizing: border-box; /* Ensures padding and border are included in the width calculation */
}

/* Make the label text size smaller in the Account tab */
#account .form-group label {
    font-size: 12px; /* Adjust this value to your preferred size */
}

 /* Horizontal alignment of New Password and Confirm New Password */
.security-form .form-row {
    display: flex;
    gap: 20px; /* Space gap between each pair */
    margin-bottom: 15px; /* Space below the row */
}

.security-form .form-group {
    flex: 1; /* Allow form-group to take available space */
    min-width: 0; /* Prevent input from stretching too far */
}

.security-form .form-group input {
    width: 100%; /* Full width of the container */
    max-width: 100%; /* Ensure it doesn't exceed container width */
    box-sizing: border-box; /* Include padding and border in the width */
    padding: 10px; /* Ensure padding inside the input box */
}

.security-form .form-group input.small-input {
    width: 80%; /* Make the current password input box smaller */
    max-width: 510px; /* Maximum width for smaller input box */
}

.security-form .form-group label {
    font-size: 14px; /* Adjust font size of labels if needed */
}

 /* Modal Styles */
 .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
        max-width: 500px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #session-msg {
        background-color: green; /* Green background */
        color: white; /* White text color for contrast */
        padding: 5px; /* Some padding for aesthetics */
        position: fixed; /* Fixed position to cover the whole viewport */
        top: 10%; /* Center vertically */
        left: 50%; /* Center horizontally */
        transform: translate(-50%, -50%); /* Adjust position to center */
        z-index: 1000; /* Ensure it's above other elements */
        transition: opacity 0.5s ease; /* Smooth transition for opacity */
    }

    #billing {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin: 20px 0;
    }

    h3 {
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 12px;
        text-align: left;
        border: 1px solid #ddd;
    }

    th {
        background-color: #333;
        color: white;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #e9ecef;
    }

    img {
        border-radius: 4px;
        max-width: 50px; /* Adjust as necessary */
        max-height: 50px; /* Adjust as necessary */
    }

    .button-group {
        margin-top: 20px;
    }

    .button-group .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
        margin-right: 10px;
        text-decoration: none;
        background-color: #007BFF;
        transition: background-color 0.3s;
    }

    .button-group .btn-secondary {
        background-color: #6c757d;
    }

    .button-group .btn:hover {
        background-color: #0056b3;
    }

    .button-group .btn-secondary:hover {
        background-color: #5a6268;
    }

    .btn-danger {
    background-color: red; /* Red background */
    color: white; /* White text for contrast */
}

.btn-danger:hover {
    background-color: darkred; /* Dark red background on hover */
}

/* Header base styles */
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

/* Brand logo styles */
.navbar-brand {
    flex-shrink: 0;
    padding-left: 0;
    margin-left: 0;
}

.navbar-brand img {
    width: 40px;
    height: 40px;
    border-radius: 40%;
}

/* Navigation styles */
nav {
    display: flex;
    align-items: center;
    margin-left: auto;
    margin-right: 15px;
}

.nav-items {
    display: flex;
    align-items: center;
    gap: 20px;
}

.nav-items a {
    text-decoration: none;
    color: white;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.nav-items a:hover {
    background-color: #87CEFA;
    color: black;
}

/* Mobile menu toggle button */
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

/* Profile dropdown styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
}

.dropbtn img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: #333;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1000;
    border-radius: 4px;
    margin-top: 5px;
}

.dropdown-content a {
    color: white;
    padding: 8px 12px;
    text-decoration: none !important; /* Force remove underline */
    display: block;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 13px; /* Set font size to 13px */
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover,
.dropdown-content a:focus,
.dropdown-content a:active {
    background-color: #87CEFA;
    color: black;
    text-decoration: none !important; /* Force remove underline on all states */
}

.dropdown-content.show {
    display: block;
}

/* Add this to show the dropdown when the show class is present */
.dropdown.show .dropdown-content {
    display: block;
}

/* Mobile styles */
@media (max-width: 768px) {
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
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 1000;
    }

    .nav-items.active {
        display: flex;
        opacity: 1;
        visibility: visible;
    }

    .nav-items a {
        color: white;
        padding: 6px 12px;
        width: 100%;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
        font-size: 13px;
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

/* Responsive table styles */
@media (max-width: 768px) {
    /* Make table container scrollable horizontally */
    #billing {
        overflow-x: auto;
        padding: 10px;
    }

    /* Ensure minimum width for table to prevent squishing */
    table {
        min-width: 500px; /* Minimum width to ensure content readability */
        margin-bottom: 15px;
    }

    /* Adjust table cell padding for mobile */
    th, td {
        padding: 8px;
        font-size: 14px;
    }

    /* Make action buttons more touch-friendly */
    .btn-danger {
        padding: 8px 12px;
        font-size: 14px;
    }

    /* Adjust QR code image size for mobile */
    td img {
        width: 40px;
        height: 40px;
    }
}

/* Optional: Add pull-to-refresh indicator */
@media (max-width: 768px) {
    #billing::before {
        content: "← Scroll horizontally to view more →";
        display: block;
        text-align: center;
        font-size: 12px;
        color: #666;
        padding: 5px 0;
        margin-bottom: 10px;
    }
}

/* Add these CSS rules to hide mobile-only elements in desktop view */
.mobile-only {
    display: none !important; /* Hide by default on desktop */
}

/* Update the mobile media query to show mobile-only elements */
@media (max-width: 768px) {
    .mobile-only {
        display: block !important; /* Show on mobile */
    }
    
    /* Rest of your existing mobile styles... */
}

/* Password input container styles */
.password-container {
    position: relative;
    width: 100%;
    border: 2px solid #ddd;
    border-radius: 5px;
    transition: border-color 0.3s ease;
}

.password-container.small-container {
    max-width: 510px;
}

.password-container input {
    width: 100%;
    padding: 10px 35px 10px 10px;
    border: none;
    outline: none;
    box-sizing: border-box;
    border-radius: 3px;
}

.password-container input:focus {
    border-color: #004080;
}

/* Remove default input borders since we're using container border */
.password-container input {
    border: none !important;
    background: transparent;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
}

.toggle-password:hover {
    color: #333;
}

.password-requirements {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.password-requirements ul {
    margin: 5px 0 0 20px;
    padding: 0;
}

.password-requirements li {
    margin: 2px 0;
}
</style>
<body>
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
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown(event)">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content">
                    <a href="admin-profile.php">View Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <!-- <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section> -->
    </main>

    <div class="main-content">

    <div class="tabs">
        <a href="#account" id="account-tab" class="active" aria-controls="account">Account</a>
        <a href="#security" id="security-tab" aria-controls="security">Security</a>
        <a href="#billing" id="billing-tab" aria-controls="billing">Billing</a> 
    </div>

    <div id="account" class="tab-content active">
    <h3>Account Details</h3>
    <hr>
    <form action="update-account.php" method="POST" class="account-form">
    <!-- Existing form fields -->

    <!-- Horizontal alignment of First Name and Last Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
        </div>
    </div>

    <!-- Contact Number -->
    <div class="form-row">
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" class="input-contact_number" value="<?php echo htmlspecialchars($user_data['contact_number']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
    </div>

    <div class="button-group">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
</div>


<div id="security" class="tab-content">
    <h3>Change Password</h3>
    <hr>
    <form action="update-password.php" method="POST" class="security-form">
        <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <div class="password-container small-container">
                <input type="password" id="currentPassword" name="currentPassword" required
                       onkeyup="validateCurrentPassword()">
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('currentPassword')"></i>
            </div>
        </div>

        <!-- Horizontal alignment of New Password and Confirm New Password -->
        <div class="form-row">
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <div class="password-container">
                    <input type="password" id="newPassword" name="newPassword" required 
                           onkeyup="validateNewPassword()">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('newPassword')"></i>
                </div>
                <small class="password-requirements">
                    Password must contain at least:
                    <ul>
                        <li>8 characters</li>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character (!@#$%^&*(),.?":{}|<>)</li>
                    </ul>
                </small>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" id="confirmPassword" name="confirmPassword" required 
                           onkeyup="validatePassword()">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword')"></i>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>
</div>

<div id="billing" class="tab-content">
    <h3>Billing List</h3>
    <hr>
    <p>Here you can view and manage your list of billing methods.</p>

    <!-- Display billing data -->
    <table>
        <thead>
            <tr>
                <th>Payment Method</th>
                <th>Mobile Number</th>
                <th>Name</th>
                <th>QR Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Database connection details
            $servername = "localhost"; // Your database server
            $username = "root"; // Your database username
            $password = ""; // Your database password
            $dbname = "db_booking_system"; // Your actual database name

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Fetch billing data
            $sql = "SELECT billing_id, payment_method, mobile_number, name, qr_code FROM billing"; 
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Output data of each row
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['payment_method']}</td>
                    <td>{$row['mobile_number']}</td>
                    <td>{$row['name']}</td>
                    <td>
                        " . ($row['qr_code'] ? "<img src='{$row['qr_code']}' alt='QR Code' style='width:50px;height:50px;'>" : 'No QR Code') . "
                    </td>
                    <td>
                        <button onclick=\"deletePaymentMethod({$row['billing_id']})\" class='btn btn-danger'>Delete</button>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No billing methods found.</td></tr>";
    }

            // Close the database connection
            $conn->close();
            ?>
        </tbody>
    </table>

    <div class="button-group">
        <button class="btn btn-primary" onclick="openModal()">Add Payment Method</button>
    </div>
</div>
</div>

<div class="modal" id="addPaymentModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        <h3>Add Payment Method</h3>
        <form action="add-payment-method.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="paymentMethod">Choose Payment Method</label>
                <select id="paymentMethod" name="paymentMethod" required>
                    <option value="" disabled selected>Select Payment Method</option>
                    <option value="paypal">Paypal</option>
                    <option value="maya">Maya</option>
                    <option value="gcash">Gcash</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mobileNumbers">Mobile Number</label>
                <input type="text" id="mobileNumbers" name="mobileNumbers" required>
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="uploadImage">Upload Payment QR Code</label>
                <input type="file" id="uploadImage" name="qr_upload" accept="image/*" required> 
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Add Payment Method</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

    <script>

        function toggleDropdown(event) {
            event.stopPropagation(); // Prevent event from bubbling up
            const dropdown = document.querySelector('.dropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const dropdowns = document.getElementsByClassName('dropdown');
                for (let dropdown of dropdowns) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            }
        }

        // Toggle mobile menu
        function toggleMenu() {
            const navItems = document.querySelector('.nav-items');
            navItems.classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (navItems.classList.contains('active') && 
                !event.target.closest('.nav-items') && 
                !event.target.closest('.menu-toggle')) {
                navItems.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // Toggle tab content
    const tabs = document.querySelectorAll('.tabs a');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            this.classList.add('active');
            document.querySelector(this.getAttribute('href')).classList.add('active');
        });
    });

    function openModal() {
    document.getElementById('addPaymentModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addPaymentModal').style.display = 'none';
}

// Optional: Reset the form fields when closing the modal
document.querySelector('.close').addEventListener('click', function() {
    closeModal();
    document.getElementById('addPaymentModal').querySelector('form').reset();
});

 // Check if the session message is present
 window.onload = function() {
        const msgElement = document.getElementById('session-msg');
        if (msgElement) {
            setTimeout(function() {
                msgElement.style.opacity = 0; // Fade out the message
                setTimeout(function() {
                    msgElement.style.display = 'none'; // Remove from the flow after fade out
                }, 500); // Wait for the fade out transition
            }, 2000); // Wait for 2 seconds
        }
    };

    function deletePaymentMethod(billingId) {
        if (confirm("Are you sure you want to delete this payment method?")) {
            fetch('delete-payment-method.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: billingId }),
            })
            .then(response => {
                if (response.ok) {
                    // If the response is good, refresh the billing list
                    location.reload();
                } else {
                    alert("Failed to delete payment method.");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("An error occurred while deleting the payment method.");
            });
        }
    }

    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = passwordInput.nextElementSibling;
        
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.classList.remove("fa-eye-slash");
            toggleIcon.classList.add("fa-eye");
        } else {
            passwordInput.type = "password";
            toggleIcon.classList.remove("fa-eye");
            toggleIcon.classList.add("fa-eye-slash");
        }
    }

    function validatePassword() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const confirmContainer = confirmPassword.parentElement;
        
        if(confirmPassword.value === '') {
            confirmContainer.style.borderColor = '#ddd';
            confirmPassword.style.borderColor = '#ddd';
            return;
        }
        
        if(newPassword.value === confirmPassword.value) {
            confirmContainer.style.borderColor = '#4CAF50';
            confirmPassword.style.borderColor = '#4CAF50';
        } else {
            confirmContainer.style.borderColor = '#ff6b6b';
            confirmPassword.style.borderColor = '#ff6b6b';
        }
    }

    function validateCurrentPassword() {
        const currentPassword = document.getElementById('currentPassword');
        const currentContainer = currentPassword.parentElement;
        
        if(currentPassword.value === '') {
            currentContainer.style.borderColor = '#ddd';
            return;
        }

        // Send AJAX request to verify password
        fetch('verify-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'current_password=' + encodeURIComponent(currentPassword.value)
        })
        .then(response => response.json())
        .then(data => {
            if(data.valid) {
                currentContainer.style.borderColor = '#4CAF50';
            } else {
                currentContainer.style.borderColor = '#ff6b6b';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            currentContainer.style.borderColor = '#ddd';
        });
    }

    function validateNewPassword() {
        const newPassword = document.getElementById('newPassword');
        const newContainer = newPassword.parentElement;
        const password = newPassword.value;
        
        if(password === '') {
            newContainer.style.borderColor = '#ddd';
            return;
        }

        // Password requirements
        const minLength = 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        // Check if password meets all requirements
        const isValid = password.length >= minLength && 
                       hasUpperCase && 
                       hasLowerCase && 
                       hasNumbers && 
                       hasSpecialChar;

        if(isValid) {
            newContainer.style.borderColor = '#4CAF50';
        } else {
            newContainer.style.borderColor = '#ff6b6b';
        }

        // Also validate confirm password if it has a value
        validatePassword();
    }
    </script>
</body>
</html>
