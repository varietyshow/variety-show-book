<?php

session_start();

// Add this after session_start()
if (isset($_SESSION['active_tab'])) {
    $active_tab = $_SESSION['active_tab'];
    unset($_SESSION['active_tab']); // Clear it after use
} else {
    $active_tab = '';
}

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
$sql = "SELECT * FROM entertainer_account WHERE first_name = '$first_name'";
$result = $conn->query($sql);


// Check if user was found
if ($result->num_rows > 0) {
    // Fetch user data
    $user_data = $result->fetch_assoc();
} else {
    echo "No user data found.";
    exit();
}

$message = ''; // Initialize the message variable
if (isset($_SESSION['update_message'])) {
    $message = $_SESSION['update_message']; // Get message from session
    unset($_SESSION['update_message']); // Clear message from session
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="calendarstyle.css">
    <script src="calendar.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
/* Main Content Styles */
.main-content {
            margin-left: 50px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            margin-top: 1px; /* Add a top margin to create space between the header and the content */
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
            /* Add these properties for centering */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Remove margin since we're using flex centering */
            margin: 0;
            /* Optional: Add animation */
            animation: modalFadeIn 0.3s ease-in-out;
        }

        /* Add animation keyframes */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

/* Custom length for the Province input */
.input-contact_number {
    width: 100%; /* Ensures input box takes the full width of its container */
    max-width: 620px; /* Adjust this value to control the maximum width of the input box */
    box-sizing: border-box; /* Ensures padding and border are included in the width calculation */
}

.role-container {
    display: inline-block; /* Keep the roles inline */
    padding: 5px 10px;
    background-color: #f0f0f0; /* Light grey background, change if needed */
    border-radius: 5px;
    margin: 5px; /* Margin between role containers */
    font-size: 14px; /* Adjust font size as needed */
}

        /* Add styles for the message */
        .update-message {
            background-color: #dff0d8;
            color: black;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

/* Add these styles in the existing <style> section */
.uploads-form {
    max-width: 800px;
    margin: 0 auto;
}

.uploads-form input[type="file"] {
    padding: 10px;
    border: 2px dashed #ddd;
    border-radius: 5px;
    width: 100%;
    margin-bottom: 10px;
}

.uploads-form small {
    color: #666;
    display: block;
    margin-bottom: 20px;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.file-item {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
}

.file-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 3px;
}

.file-item .delete-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-item .delete-btn:hover {
    background: rgba(255, 0, 0, 0.9);
}

/* Add to your existing style section */
.files-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.file-preview-item {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    text-align: center;
}

.file-preview-item img,
.file-preview-item video {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
}

.file-preview-item p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
    word-break: break-all;
}

.uploaded-files-container {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.uploaded-files-container h4 {
    margin-bottom: 20px;
    color: #333;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.file-item {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    background: #fff;
}

.file-item img,
.file-item video {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 3px;
}

.delete-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.delete-btn:hover {
    background: rgba(255, 0, 0, 0.9);
}

/* Add to your existing style section */
.uploads-form .button-group {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    width: 100%;
}

.uploads-form .button-group button {
    margin: 0 auto;
}

/* Add this CSS rule or modify if it exists */
.tab-content h3 {
    margin-bottom: 5px;
    padding-bottom: 5px;
}

.nav-items {
            display: flex;
            gap: 30px; /* Space between items */
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
            height: auto; /* Maintain aspect ratio */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

/* Add these styles in the <style> section, after the existing styles */
header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background-color: #333;
}

/* Adjust main content to prevent it from hiding behind fixed header */
main {
    margin-top: 60px;
}

/* Update main-content section positioning */
.main-content {
    margin-top: 10px;
    padding-top: 10px !important;
    margin-left: 50px;
}

/* Update welcome-message section to prevent overlap */
.welcome-message {
    padding-top: 1px;
    margin-top: 10px;
    margin-left: 50px;
}

/* Update the update-message positioning */
.update-message {
    margin-top: 20px;
}

/* Ensure content stays below fixed header */
body {
    padding-top: 40px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
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
    max-width: 600px;
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

.password-requirements {
    margin-top: 10px;
    font-size: 12px;
    color: #666;
}

.password-requirements ul {
    list-style: none;
    padding-left: 0;
    margin-top: 5px;
}

.password-requirements li {
    margin: 3px 0;
}

.requirement-met {
    color: #4CAF50;
}

.requirement-not-met {
    color: #ff6b6b;
}

#confirm-message {
    font-weight: bold;
}

#confirm-message.match {
    color: #4CAF50;
}

#confirm-message.no-match {
    color: #ff6b6b;
}

header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background-color: #333;
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 15px;
}

@media (max-width: 768px) {
    header {
        height: 60px;
    }

    .navbar-brand {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        height: 100%;
        display: flex;
        align-items: center;
    }
}

/* Mobile menu toggle button */
.menu-toggle {
    display: none;
    cursor: pointer;
    padding: 10px;
    background: none;
    border: none;
    position: absolute;
    right: 10px;
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
    transition: all 0.4s ease-in-out;
}

/* Mobile styles */
@media (max-width: 768px) {
    .menu-toggle {
        display: block;
        margin-left: auto;
        margin-right: 10px;
    }

    .dropdown {
        display: none !important;
    }

    .nav-items {
        display: none;
        position: absolute;
        top: 60px;
        right: -80px; /* Return to original position */
        left: auto;
        width: 160px;
        background-color: #333;
        flex-direction: column;
        padding: 3px 0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        gap: 0;
    }

    .nav-items.active {
        display: flex;
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

    /* Style for mobile-only items */
    .nav-items a.mobile-only {
        display: block !important;
    }

    /* Add separator before mobile-only items */
    .nav-items a.mobile-only:first-of-type {
        border-top: 2px solid rgba(255, 255, 255, 0.2);
    }

    .navbar-brand {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
    }
    
    .navbar-brand img {
        width: 40px; /* Slightly smaller in mobile view */
        height: 40px;
    }
}

/* Ensure menu appears above other content */
.nav-items.active {
    z-index: 1002;
}

/* Hide mobile-only items in desktop view */
.nav-items a.mobile-only {
    display: none; /* Hide by default on desktop */
}

/* Mobile styles */
@media (max-width: 768px) {
    /* ... other mobile styles ... */

    /* Show mobile-only items in mobile view */
    .nav-items a.mobile-only {
        display: block !important;
    }

    /* Add separator before mobile-only items */
    .nav-items a.mobile-only:first-of-type {
        border-top: 2px solid rgba(255, 255, 255, 0.2);
    }
}

/* Add/update dropdown styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    width: 160px;
    background-color: #333;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 1002;
}

.dropdown-content a {
    color: white;
    padding: 6px 12px;
    text-decoration: none !important;
    display: block;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    white-space: nowrap;
    font-size: 13px;
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover {
    background-color: #87CEFA;
    color: black;
    text-decoration: none !important;
}

.dropdown.show .dropdown-content {
    display: block;
}

/* Update existing mobile nav-items styles to match */
@media (max-width: 768px) {
    .nav-items {
        display: none;
        position: absolute;
        top: 60px;
        right: -80px;
        left: auto;
        width: 160px;
        background-color: #333;
        flex-direction: column;
        padding: 3px 0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        gap: 0;
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

    .nav-items a:last-child {
        border-bottom: none;
    }
}

/* Update the body background style */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}
</style>
<body>
    <!-- Fixed Header with Navigation -->
    <header>
        <!-- Brand Logo Section -->
        <a class="navbar-brand" href="#">
            <img src="../images/logo.jpg" alt="Brand Logo">
        </a>
        
        <!-- Main Navigation Section -->
        <nav>
            <!-- Mobile Menu Toggle (Hamburger) Button -->
            <button class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="nav-items">
                <a href="entertainer-dashboard.php">Dashboard</a>
                <a href="entertainer-mysched.php">View Schedule</a>
                <a href="entertainer-myAppointment.php">My Appointment</a>
                <!-- Mobile-only navigation items -->
                <a href="entertainer-profile.php" class="mobile-only">View Profile</a>
                <a href="logout.php" class="mobile-only">Logout</a>
            </div>
            
            <!-- Profile Dropdown (Desktop View) -->
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdown-content">
                    <a href="entertainer-profile.php">View Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>

        <?php if ($message): ?>
            <div class="update-message" id="updateMessage">
                <?php echo $message; ?>
            </div>
            <script>
                // Auto-hide message after 2 seconds
                document.addEventListener('DOMContentLoaded', function() {
                    const messageElement = document.getElementById('updateMessage');
                    if (messageElement) {
                        setTimeout(function() {
                            messageElement.style.opacity = '0';
                            setTimeout(function() {
                                messageElement.style.display = 'none';
                            }, 300); // Wait for fade out animation to complete
                        }, 2000);
                    }
                });
            </script>
        <?php endif; ?>
    </main>

    <div class="main-content">

    <div class="tabs">
        <a href="#account" id="account-tab" class="active" aria-controls="account">Account</a>
        <a href="#security" id="security-tab" aria-controls="security">Security</a>
        <a href="#uploads" id="uploads-tab" aria-controls="uploads">Uploads</a>
    </div>

    <div id="account" class="tab-content active">
    <h3>Account Details</h3>
    <hr>
    <form action="update_account.php" method="POST" class="account-form">
    <!-- Existing form fields -->

    <!-- Horizontal alignment of First Name and Last Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" value="<?php echo ucwords(strtolower(htmlspecialchars($user_data['first_name']))); ?>" required>
        </div>
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" value="<?php echo ucwords(strtolower(htmlspecialchars($user_data['last_name']))); ?>" required>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user_data['contact_number']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
    </div>

<!-- New Content Section for Role Display and Add Button -->
<div class="form-group">
    <label for="role">Role</label>
    <div id="role-display">
        <?php
        // Assuming 'role' is a comma-separated string
        $roles = explode(',', htmlspecialchars($user_data['roles'])); // Split roles into an array
        foreach ($roles as $role) {
            echo '<span class="role-container">' . trim($role) . '</span>'; // Create a container for each role
        }
        ?>
    </div>
</div>

    <h4 style="color: #D3D3D3;">Address</h4>
    <!-- Horizontal alignment of Block Number and Street -->
    <div class="form-row">
    <div class="form-group">
            <label for="street">Street</label>
            <input type="text" id="street" name="street" value="<?php echo ucwords(strtolower(htmlspecialchars($user_data['street']))); ?>" required>
        </div>
        <div class="form-group">
            <label for="barangay">Barangay</label>
            <input type="text" id="barangay" name="barangay" value="<?php echo ucwords(strtolower(htmlspecialchars($user_data['barangay']))); ?>" required>
        </div>
    </div>

    <!-- Horizontal alignment of Barangay and City -->
    <div class="form-row">
    <div class="form-group">
    <label for="municipality">Municipality</label>
            <input type="text" id="municipality" name="municipality" value="<?php echo ucwords(strtolower(htmlspecialchars($user_data['municipality']))); ?>" required>
        </div>
        <div class="form-group">
        <label for="province">Province</label>
        <select id="province" name="province" required>
            <option value="" disabled>Select Province</option>
            <option value="abu" <?php echo (strtolower($user_data['province']) == 'abu') ? 'selected' : ''; ?>>Abu</option>
            <option value="agusan del norte" <?php echo (strtolower($user_data['province']) == 'agusan del norte') ? 'selected' : ''; ?>>Agusan del Norte</option>
            <option value="agusan del sur" <?php echo (strtolower($user_data['province']) == 'agusan del sur') ? 'selected' : ''; ?>>Agusan del Sur</option>
            <option value="aklan" <?php echo (strtolower($user_data['province']) == 'aklan') ? 'selected' : ''; ?>>Aklan</option>
            <option value="albay" <?php echo (strtolower($user_data['province']) == 'albay') ? 'selected' : ''; ?>>Albay</option>
            <option value="antique" <?php echo (strtolower($user_data['province']) == 'antique') ? 'selected' : ''; ?>>Antique</option>
            <option value="apayao" <?php echo (strtolower($user_data['province']) == 'apayao') ? 'selected' : ''; ?>>Apayao</option>
            <option value="aurora" <?php echo (strtolower($user_data['province']) == 'aurora') ? 'selected' : ''; ?>>Aurora</option>
            <option value="basilan" <?php echo (strtolower($user_data['province']) == 'basilan') ? 'selected' : ''; ?>>Basilan</option>
            <option value="bataan" <?php echo (strtolower($user_data['province']) == 'bataan') ? 'selected' : ''; ?>>Bataan</option>
            <option value="batanes" <?php echo (strtolower($user_data['province']) == 'batanes') ? 'selected' : ''; ?>>Batanes</option>
            <option value="batangas" <?php echo (strtolower($user_data['province']) == 'batangas') ? 'selected' : ''; ?>>Batangas</option>
            <option value="benguet" <?php echo (strtolower($user_data['province']) == 'benguet') ? 'selected' : ''; ?>>Benguet</option>
            <option value="biliran" <?php echo (strtolower($user_data['province']) == 'biliran') ? 'selected' : ''; ?>>Biliran</option>
            <option value="bohol" <?php echo (strtolower($user_data['province']) == 'bohol') ? 'selected' : ''; ?>>Bohol</option>
            <option value="bukidnon" <?php echo (strtolower($user_data['province']) == 'bukidnon') ? 'selected' : ''; ?>>Bukidnon</option>
            <option value="bulacan" <?php echo (strtolower($user_data['province']) == 'bulacan') ? 'selected' : ''; ?>>Bulacan</option>
            <option value="cagayan" <?php echo (strtolower($user_data['province']) == 'cagayan') ? 'selected' : ''; ?>>Cagayan</option>
            <option value="camarines norte" <?php echo (strtolower($user_data['province']) == 'camarines norte') ? 'selected' : ''; ?>>Camarines Norte</option>
            <option value="camarines sur" <?php echo (strtolower($user_data['province']) == 'camarines sur') ? 'selected' : ''; ?>>Camarines Sur</option>
            <option value="camiguin" <?php echo (strtolower($user_data['province']) == 'camiguin') ? 'selected' : ''; ?>>Camiguin</option>
            <option value="capiz" <?php echo (strtolower($user_data['province']) == 'capiz') ? 'selected' : ''; ?>>Capiz</option>
            <option value="catanduanes" <?php echo (strtolower($user_data['province']) == 'catanduanes') ? 'selected' : ''; ?>>Catanduanes</option>
            <option value="cavite" <?php echo (strtolower($user_data['province']) == 'cavite') ? 'selected' : ''; ?>>Cavite</option>
            <option value="cebu" <?php echo (strtolower($user_data['province']) == 'cebu') ? 'selected' : ''; ?>>Cebu</option>
            <option value="cotabato" <?php echo (strtolower($user_data['province']) == 'cotabato') ? 'selected' : ''; ?>>Cotabato</option>
            <option value="davao de oro" <?php echo (strtolower($user_data['province']) == 'davao de oro') ? 'selected' : ''; ?>>Davao de Oro</option>
            <option value="davao del norte" <?php echo (strtolower($user_data['province']) == 'davao del norte') ? 'selected' : ''; ?>>Davao del Norte</option>
            <option value="davao del sur" <?php echo (strtolower($user_data['province']) == 'davao del sur') ? 'selected' : ''; ?>>Davao del Sur</option>
            <option value="davao occidental" <?php echo (strtolower($user_data['province']) == 'davao occidental') ? 'selected' : ''; ?>>Davao Occidental</option>
            <option value="davao oriental" <?php echo (strtolower($user_data['province']) == 'davao oriental') ? 'selected' : ''; ?>>Davao Oriental</option>
            <option value="dinagat islands" <?php echo (strtolower($user_data['province']) == 'dinagat islands') ? 'selected' : ''; ?>>Dinagat Islands</option>
            <option value="eastern samar" <?php echo (strtolower($user_data['province']) == 'eastern samar') ? 'selected' : ''; ?>>Eastern Samar</option>
            <option value="guimaras" <?php echo (strtolower($user_data['province']) == 'guimaras') ? 'selected' : ''; ?>>Guimaras</option>
            <option value="ifugao" <?php echo (strtolower($user_data['province']) == 'ifugao') ? 'selected' : ''; ?>>Ifugao</option>
            <option value="ilocos norte" <?php echo (strtolower($user_data['province']) == 'ilocos norte') ? 'selected' : ''; ?>>Ilocos Norte</option>
            <option value="ilocos sur" <?php echo (strtolower($user_data['province']) == 'ilocos sur') ? 'selected' : ''; ?>>Ilocos Sur</option>
            <option value="iloilo" <?php echo (strtolower($user_data['province']) == 'iloilo') ? 'selected' : ''; ?>>Iloilo</option>
            <option value="isabela" <?php echo (strtolower($user_data['province']) == 'isabela') ? 'selected' : ''; ?>>Isabela</option>
            <option value="kalinga" <?php echo (strtolower($user_data['province']) == 'kalinga') ? 'selected' : ''; ?>>Kalinga</option>
            <option value="la union" <?php echo (strtolower($user_data['province']) == 'la union') ? 'selected' : ''; ?>>La Union</option>
            <option value="laguna" <?php echo (strtolower($user_data['province']) == 'laguna') ? 'selected' : ''; ?>>Laguna</option>
            <option value="lanao del norte" <?php echo (strtolower($user_data['province']) == 'lanao del norte') ? 'selected' : ''; ?>>Lanao del Norte</option>
            <option value="lanao del sur" <?php echo (strtolower($user_data['province']) == 'lanao del sur') ? 'selected' : ''; ?>>Lanao del Sur</option>
            <option value="leyte" <?php echo (strtolower($user_data['province']) == 'leyte') ? 'selected' : ''; ?>>Leyte</option>
            <option value="maguindanao" <?php echo (strtolower($user_data['province']) == 'maguindanao') ? 'selected' : ''; ?>>Maguindanao</option>
            <option value="marinduque" <?php echo (strtolower($user_data['province']) == 'marinduque') ? 'selected' : ''; ?>>Marinduque</option>
            <option value="masbate" <?php echo (strtolower($user_data['province']) == 'masbate') ? 'selected' : ''; ?>>Masbate</option>
            <option value="metro manila" <?php echo (strtolower($user_data['province']) == 'metro manila') ? 'selected' : ''; ?>>Metro Manila</option>
            <option value="misamis occidental" <?php echo (strtolower($user_data['province']) == 'misamis occidental') ? 'selected' : ''; ?>>Misamis Occidental</option>
            <option value="misamis oriental" <?php echo (strtolower($user_data['province']) == 'misamis oriental') ? 'selected' : ''; ?>>Misamis Oriental</option>
            <option value="mountain province" <?php echo (strtolower($user_data['province']) == 'mountain province') ? 'selected' : ''; ?>>Mountain Province</option>
            <option value="negros occidental" <?php echo (strtolower($user_data['province']) == 'negros occidental') ? 'selected' : ''; ?>>Negros Occidental</option>
            <option value="negros oriental" <?php echo (strtolower($user_data['province']) == 'negros oriental') ? 'selected' : ''; ?>>Negros Oriental</option>
            <option value="northern samar" <?php echo (strtolower($user_data['province']) == 'northern samar') ? 'selected' : ''; ?>>Northern Samar</option>
            <option value="nueva ecija" <?php echo (strtolower($user_data['province']) == 'nueva ecija') ? 'selected' : ''; ?>>Nueva Ecija</option>
            <option value="nueva vizcaya" <?php echo (strtolower($user_data['province']) == 'nueva vizcaya') ? 'selected' : ''; ?>>Nueva Vizcaya</option>
            <option value="occidental mindoro" <?php echo (strtolower($user_data['province']) == 'occidental mindoro') ? 'selected' : ''; ?>>Occidental Mindoro</option>
            <option value="oriental mindoro" <?php echo (strtolower($user_data['province']) == 'oriental mindoro') ? 'selected' : ''; ?>>Oriental Mindoro</option>
            <option value="palawan" <?php echo (strtolower($user_data['province']) == 'palawan') ? 'selected' : ''; ?>>Palawan</option>
            <option value="pampanga" <?php echo (strtolower($user_data['province']) == 'pampanga') ? 'selected' : ''; ?>>Pampanga</option>
            <option value="pangasinan" <?php echo (strtolower($user_data['province']) == 'pangasinan') ? 'selected' : ''; ?>>Pangasinan</option>
            <option value="quezon" <?php echo (strtolower($user_data['province']) == 'quezon') ? 'selected' : ''; ?>>Quezon</option>
            <option value="quirino" <?php echo (strtolower($user_data['province']) == 'quirino') ? 'selected' : ''; ?>>Quirino</option>
            <option value="rizal" <?php echo (strtolower($user_data['province']) == 'rizal') ? 'selected' : ''; ?>>Rizal</option>
            <option value="romblon" <?php echo (strtolower($user_data['province']) == 'romblon') ? 'selected' : ''; ?>>Romblon</option>
            <option value="samar" <?php echo (strtolower($user_data['province']) == 'samar') ? 'selected' : ''; ?>>Samar</option>
            <option value="sarangani" <?php echo (strtolower($user_data['province']) == 'sarangani') ? 'selected' : ''; ?>>Sarangani</option>
            <option value="siquijor" <?php echo (strtolower($user_data['province']) == 'siquijor') ? 'selected' : ''; ?>>Siquijor</option>
            <option value="sorsogon" <?php echo (strtolower($user_data['province']) == 'sorsogon') ? 'selected' : ''; ?>>Sorsogon</option>
            <option value="south cotabato" <?php echo (strtolower($user_data['province']) == 'south cotabato') ? 'selected' : ''; ?>>South Cotabato</option>
            <option value="southern leyte" <?php echo (strtolower($user_data['province']) == 'southern leyte') ? 'selected' : ''; ?>>Southern Leyte</option>
            <option value="sultan kudarat" <?php echo (strtolower($user_data['province']) == 'sultan kudarat') ? 'selected' : ''; ?>>Sultan Kudarat</option>
            <option value="sulu" <?php echo (strtolower($user_data['province']) == 'sulu') ? 'selected' : ''; ?>>Sulu</option>
            <option value="surigao del norte" <?php echo (strtolower($user_data['province']) == 'surigao del norte') ? 'selected' : ''; ?>>Surigao del Norte</option>
            <option value="surigao del sur" <?php echo (strtolower($user_data['province']) == 'surigao del sur') ? 'selected' : ''; ?>>Surigao del Sur</option>
            <option value="tarlac" <?php echo (strtolower($user_data['province']) == 'tarlac') ? 'selected' : ''; ?>>Tarlac</option>
            <option value="tawi-tawi" <?php echo (strtolower($user_data['province']) == 'tawi-tawi') ? 'selected' : ''; ?>>Tawi-Tawi</option>
            <option value="zambales" <?php echo (strtolower($user_data['province']) == 'zambales') ? 'selected' : ''; ?>>Zambales</option>
            <option value="zamboanga del norte" <?php echo (strtolower($user_data['province']) == 'zamboanga del norte') ? 'selected' : ''; ?>>Zamboanga del Norte</option>
            <option value="zamboanga del sur" <?php echo (strtolower($user_data['province']) == 'zamboanga del sur') ? 'selected' : ''; ?>>Zamboanga del Sur</option>
            <option value="zamboanga sibugay" <?php echo (strtolower($user_data['province']) == 'zamboanga sibugay') ? 'selected' : ''; ?>>Zamboanga Sibugay</option>
        </select>
    </div>
    </div>

<!-- Additional section for Social Media Accounts -->
<h4 style="color: #D3D3D3;">Social Media Accounts</h4>
<div class="form-row">
    <div class="form-group">
        <label for="facebook">Facebook Profile URL</label>
        <input type="url" id="facebook" name="facebook" placeholder="https://facebook.com/yourprofile" value="<?php echo htmlspecialchars($user_data['facebook_acc']); ?>">
    </div>
    <div class="form-group">
        <label for="instagram">Instagram Profile URL</label>
        <input type="url" id="instagram" name="instagram" placeholder="https://instagram.com/yourprofile"  value="<?php echo htmlspecialchars($user_data['instagram_acc']); ?>">
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
                <div class="password-requirements">
                    Password must contain:
                    <ul>
                        <li id="length-check">❌ At least 8 characters</li>
                        <li id="uppercase-check">❌ One uppercase letter</li>
                        <li id="lowercase-check">❌ One lowercase letter</li>
                        <li id="number-check">❌ One number</li>
                        <li id="special-check">❌ One special character (!@#$%^&*(),.?":{}|<>)</li>
                    </ul>
                </div>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" id="confirmPassword" name="confirmPassword" required 
                           onkeyup="validatePassword()">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword')"></i>
                </div>
                <div id="confirm-message" class="password-requirements" style="margin-top: 10px;"></div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Update Password</button>
        </div>
    </form>
</div>

<div id="uploads" class="tab-content">
    <h3>My Uploads</h3>
    <hr style="margin-top: 0px; margin-bottom: 20px;">
    
    <div class="uploaded-files-container">
        <div class="files-grid">
            <?php
            // Fetch uploaded files for this entertainer
            $sql = "SELECT * FROM uploads WHERE entertainer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_data['entertainer_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($file = $result->fetch_assoc()) {
                $file_path = "../uploads/" . $file['filename'];
                $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                
                // Only display video files
                if (in_array($file_extension, ['mp4', 'mov', 'mkv', 'webm'])) {
                    echo '<div class="file-item">';
                    echo '<video controls>
                            <source src="' . htmlspecialchars($file_path) . '" type="video/' . $file_extension . '">
                            Your browser does not support the video tag.
                          </video>';
                    echo '<button class="delete-btn" onclick="deleteFile(' . $file['upload_id'] . ')">×</button>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>

    <form action="upload_files.php" method="POST" enctype="multipart/form-data" class="uploads-form" id="uploadForm" onsubmit="localStorage.setItem('activeTab', 'uploads');">
        <input type="file" id="fileInput" name="files[]" multiple accept="video/*" style="display: none;" onchange="handleFileSelect(this)">
        <div class="button-group">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">Upload Your Video</button>
        </div>
        
        <div class="files-preview" id="filesPreview">
            <!-- Preview of files to be uploaded will be displayed here -->
        </div>
    </form>
</div>
</div>

    <script>

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            const dropdownContent = document.getElementById('dropdown-content');

            // Toggle the visibility of the dropdown content
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            } else {
                // Close any other open dropdowns
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });

                dropdown.classList.add('show');
            }
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });
            }
        }

        const tabs = document.querySelectorAll('.tabs a');
        const tabContents = document.querySelectorAll('.tab-content');

        // Function to set active tab
        function setActiveTab(tabId) {
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            // Add active class to selected tab and content
            const selectedTab = document.getElementById(tabId + '-tab');
            const selectedContent = document.getElementById(tabId);
            
            if (selectedTab && selectedContent) {
                selectedTab.classList.add('active');
                selectedContent.classList.add('active');
                // Save active tab to localStorage
                localStorage.setItem('activeTab', tabId);
            }
        }

        // Add click handlers to tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('href').substring(1);
                setActiveTab(tabId);
            });
        });

        // Modify the deleteFile function
        function deleteFile(uploadId) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'upload_id=' + uploadId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store the active tab before reload
                        localStorage.setItem('activeTab', 'uploads');
                        // Reload the page
                        window.location.reload();
                    } else {
                        alert('Error deleting file: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting file');
                });
            }
        }

        // Function to restore active tab on page load
        function restoreActiveTab() {
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                setActiveTab(activeTab);
            }
        }

        // Call restoreActiveTab when the page loads
        document.addEventListener('DOMContentLoaded', restoreActiveTab);

        function handleFileSelect(input) {
            const preview = document.getElementById('filesPreview');
            preview.innerHTML = ''; // Clear existing preview
            
            const allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-matroska', 'video/webm'];
            const maxFileSize = 100 * 1024 * 1024; // 100MB max file size
            let hasInvalidFile = false;

            if (input.files.length > 0) {
                Array.from(input.files).forEach(file => {
                    if (!allowedTypes.includes(file.type)) {
                        alert(`File "${file.name}" is not a valid video file. Please upload only video files.`);
                        hasInvalidFile = true;
                        return;
                    }

                    if (file.size > maxFileSize) {
                        alert(`File "${file.name}" is too large. Maximum file size is 100MB.`);
                        hasInvalidFile = true;
                        return;
                    }

                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file-preview-item';
                    
                    const video = document.createElement('video');
                    video.src = URL.createObjectURL(file);
                    video.controls = true;
                    fileDiv.appendChild(video);
                    
                    const fileName = document.createElement('p');
                    fileName.textContent = file.name;
                    fileDiv.appendChild(fileName);
                    
                    const fileSize = document.createElement('p');
                    fileSize.textContent = `Size: ${(file.size / (1024 * 1024)).toFixed(2)} MB`;
                    fileDiv.appendChild(fileSize);
                    
                    preview.appendChild(fileDiv);
                });

                if (!hasInvalidFile) {
                    // Set active tab to uploads before submitting
                    localStorage.setItem('activeTab', 'uploads');
                    
                    // Create and append a hidden input for the active tab
                    const activeTabInput = document.createElement('input');
                    activeTabInput.type = 'hidden';
                    activeTabInput.name = 'active_tab';
                    activeTabInput.value = 'uploads';
                    document.getElementById('uploadForm').appendChild(activeTabInput);

                    // Submit the form
                    document.getElementById('uploadForm').submit();
                } else {
                    // Clear the file input if there were invalid files
                    input.value = '';
                    preview.innerHTML = '';
                }
            }
        }

        // Make sure this code runs when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we should activate the uploads tab
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab === 'uploads') {
                setActiveTab('uploads');
            }
        });

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
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword');
            const confirmContainer = confirmPassword.parentElement;
            const confirmMessage = document.getElementById('confirm-message');
            
            if(confirmPassword.value === '') {
                confirmContainer.style.borderColor = '#ddd';
                confirmMessage.textContent = '';
                confirmMessage.className = 'password-requirements';
            } else if(newPassword === confirmPassword.value) {
                confirmContainer.style.borderColor = '#4CAF50';
                confirmMessage.textContent = '✅ Passwords match';
                confirmMessage.className = 'password-requirements match';
            } else {
                confirmContainer.style.borderColor = '#ff6b6b';
                confirmMessage.textContent = '❌ Passwords do not match';
                confirmMessage.className = 'password-requirements no-match';
            }

            checkAllRequirements();
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
            const password = document.getElementById('newPassword').value;
            const newPasswordContainer = document.getElementById('newPassword').parentElement;
            const lengthCheck = document.getElementById('length-check');
            const uppercaseCheck = document.getElementById('uppercase-check');
            const lowercaseCheck = document.getElementById('lowercase-check');
            const numberCheck = document.getElementById('number-check');
            const specialCheck = document.getElementById('special-check');
            const submitBtn = document.getElementById('submit-btn');

            // Check all requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            // Update container border color based on all requirements being met
            if (password === '') {
                newPasswordContainer.style.borderColor = '#ddd';
            } else if (hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial) {
                newPasswordContainer.style.borderColor = '#4CAF50'; // Green for success
            } else {
                newPasswordContainer.style.borderColor = '#ff6b6b'; // Red for incomplete requirements
            }

            // Check length
            if(hasLength) {
                lengthCheck.innerHTML = '✅ At least 8 characters';
                lengthCheck.classList.add('requirement-met');
            } else {
                lengthCheck.innerHTML = '❌ At least 8 characters';
                lengthCheck.classList.remove('requirement-met');
            }

            // Check uppercase
            if(hasUppercase) {
                uppercaseCheck.innerHTML = '✅ One uppercase letter';
                uppercaseCheck.classList.add('requirement-met');
            } else {
                uppercaseCheck.innerHTML = '❌ One uppercase letter';
                uppercaseCheck.classList.remove('requirement-met');
            }

            // Check lowercase
            if(hasLowercase) {
                lowercaseCheck.innerHTML = '✅ One lowercase letter';
                lowercaseCheck.classList.add('requirement-met');
            } else {
                lowercaseCheck.innerHTML = '❌ One lowercase letter';
                lowercaseCheck.classList.remove('requirement-met');
            }

            // Check numbers
            if(hasNumber) {
                numberCheck.innerHTML = '✅ One number';
                numberCheck.classList.add('requirement-met');
            } else {
                numberCheck.innerHTML = '❌ One number';
                numberCheck.classList.remove('requirement-met');
            }

            // Check special characters
            if(hasSpecial) {
                specialCheck.innerHTML = '✅ One special character';
                specialCheck.classList.add('requirement-met');
            } else {
                specialCheck.innerHTML = '❌ One special character';
                specialCheck.classList.remove('requirement-met');
            }

            validatePassword();
            checkAllRequirements();
        }

        function checkAllRequirements() {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submit-btn');
            const currentPassword = document.getElementById('currentPassword').value;

            const meetsAllRequirements = 
                password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password) &&
                /[!@#$%^&*(),.?":{}|<>]/.test(password) &&
                password === confirmPassword &&
                currentPassword.length > 0;

            submitBtn.disabled = !meetsAllRequirements;
        }

          // Function to toggle the mobile menu
          function toggleMenu() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navItems = document.querySelector('.nav-items');
            
            // Toggle active class for animation
            menuToggle.classList.toggle('active');
            navItems.classList.toggle('active');

            // Optional: Add aria-expanded attribute for accessibility
            const isExpanded = menuToggle.classList.contains('active');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        }

        // Function to toggle the dropdown menu for profile
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('show');
        }

        // Handle clicks outside mobile menu to close it
        document.addEventListener('click', function(event) {
            const menuToggle = document.querySelector('.menu-toggle');
            const navItems = document.querySelector('.nav-items');
            
            if (!event.target.closest('.menu-toggle') && 
                !event.target.closest('.nav-items') && 
                navItems.classList.contains('active')) {
                // Remove active classes to close menu and animate X back to hamburger
                menuToggle.classList.remove('active');
                navItems.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }

            // Close dropdown when clicking outside
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const dropdowns = document.getElementsByClassName('dropdown');
                for (let i = 0; i < dropdowns.length; i++) {
                    let openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });

        // Close mobile menu when window is resized above mobile breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const menuToggle = document.querySelector('.menu-toggle');
                const navItems = document.querySelector('.nav-items');
                
                // Remove active classes when screen size changes
                menuToggle.classList.remove('active');
                navItems.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>
