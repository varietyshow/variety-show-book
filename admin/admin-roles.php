<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database connection
$servername = "sql12.freesqldatabase.com"; // Change this to your database host
$username = "sql12774230";        // Change this to your database username
$password = "ytPEFx33BF";            // Change this to your database password
$dbname = "sql12774230"; // Use your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Modified query to join roles with packages
$sql = "SELECT r.role_id, r.role_name, r.rate, r.duration, r.duration_unit
        FROM roles r";
$result = $conn->query($sql);

// Handle adding new role with packages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_role') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert the role first
        $roleName = htmlspecialchars($_POST['roleName']);
        $rate = htmlspecialchars($_POST['rate']);
        $duration = htmlspecialchars($_POST['duration']);
        $durationUnit = htmlspecialchars($_POST['durationUnit']);
        
        $stmtRole = $conn->prepare("INSERT INTO roles (role_name, rate, duration, duration_unit) VALUES (?, ?, ?, ?)");
        $stmtRole->bind_param("sdss", $roleName, $rate, $duration, $durationUnit);
        $stmtRole->execute();
        
        $roleId = $conn->insert_id;
        
        // Insert packages
        $packageNames = $_POST['packageNames'];
        $packagePrices = $_POST['packagePrices'];
        
        $stmtPackage = $conn->prepare("INSERT INTO role_packages (role_id, package_name, package_price) VALUES (?, ?, ?)");
        
        for ($i = 0; $i < count($packageNames); $i++) {
            if (!empty($packageNames[$i]) && isset($packagePrices[$i])) {
                $stmtPackage->bind_param("isd", $roleId, $packageNames[$i], $packagePrices[$i]);
                $stmtPackage->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Role and packages added successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    exit;
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 800px;
        }
        h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #333;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .add-button {
            padding: 10px 15px; /* Adjust based on your design */
            background-color: #28a745; /* Green color */
            color: white; /* Text color */
            border: none; /* No border */
            border-radius: 4px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor on hover */
        }

        .add-button:hover {
            background-color: #218838; /* Darker green on hover */
        }
        .move-up {
            margin-top: 0;
        }

        .header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: -20px; /* Adding margin to separate from the button */
}

.button-container {
    text-align: right; /* Aligned to the right */
    margin-bottom: 20px; /* Space before the table */
}

.centered {
    text-align: center; /* Center the text */
    font-weight: bold; /* Optional: make it bold to stand out */
    color: #999; /* Optional: change color for visibility */
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow-y: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 5px;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 10px;
    top: 5px;
    font-size: 24px;
    cursor: pointer;
}

.close:hover {
    color: #f00;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.submit-btn {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-btn:hover {
    background-color: #45a049;
}

.role-entertainer-pair {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    position: relative;
}

.duration-pair {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    position: relative;
}

.remove-duration {
    position: absolute;
    right: 10px;
    top: 10px;
    background: none;
    border: none;
    color: #f00;
    cursor: pointer;
}

/* Modern close button */
.close {
    position: absolute;
    right: 20px;
    top: 15px;
    color: #666;
    font-size: 24px;
    transition: all 0.3s ease;
}

.close:hover {
    color: #333;
    transform: rotate(90deg);
}

/* Modern form styles */
.modal h2 {
    color: #333;
    margin-bottom: 25px;
    font-size: 24px;
}

form label {
    display: block;
    margin: 15px 0 8px;
    font-weight: 500;
    color: #555;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

form input[type="text"],
form input[type="number"],
form select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    margin-bottom: 20px;
    box-sizing: border-box;
    font-size: 15px;
    transition: all 0.3s ease;
}

form input[type="text"]:focus,
form input[type="number"]:focus,
form select:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

form select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 12px;
    padding-right: 40px;
}

/* Modern button styles */
form button[type="submit"] {
    background-color: #007bff;
    color: white;
    padding: 14px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-top: 15px;
}

form button[type="submit"]:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

form button[type="submit"]:active {
    transform: translateY(0);
}

/* Add subtle animations */
.modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-content {
        margin: 10% auto;
        padding: 20px;
    }

    form button[type="submit"] {
        padding: 12px 16px;
    }
}

.nav-items {
            display: flex;
            gap: 20px; /* Reduced from 30px */
            margin-right: 20px; /* Reduced from 80px to bring items closer to profile image */
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

        /* Brand logo container styles */
        .navbar-brand {
            flex-shrink: 0;
            padding-left: 0;
            margin-left: 0;
        }

        /* Brand logo image styles */
        .navbar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 40%;
        }

        /* Navigation container styles */
        nav {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 15px;
        }

        /* Navigation items container */
        .nav-items {
            display: flex;
            align-items: center;
            gap: 20px;
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
            transition: all 0.4s ease-in-out; /* Smooth transition for animation */
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
                margin-left: auto;
                margin-right: 10px;
            }

            .dropdown, 
            .dropdown-content {
                display: none !important;
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
            }

            .mobile-only {
                display: block !important;
            }

            .nav-items a.mobile-only {
                border-top: 1px solid rgba(255, 255, 255, 0.2);
            }
        }

        /* Add margin to main content */
        main {
            margin-top: 60px;
            padding: 20px;
        }

        .mobile-only {
            display: none !important; /* Hide by default on desktop */
        }

        /* Update the mobile media query */
        @media (max-width: 768px) {
            /* ... other mobile styles ... */

            .mobile-only {
                display: block !important; /* Show on mobile */
                border-top: 1px solid rgba(255, 255, 255, 0.1); /* Optional: adds a separator line */
            }

            .dropdown {
                display: none !important; /* Hide dropdown on mobile */
            }

            .nav-items a.mobile-only {
                color: white;
                padding: 6px 12px;
                width: 100%;
                text-align: left;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                white-space: nowrap;
                font-size: 13px;
            }
        }

        /* Add these styles for menu toggle animation */
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        /* Update the menu-toggle span styles */
        .menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 5px 0;
            transition: all 0.4s ease-in-out; /* Smooth transition for animation */
        }

        /* Add transition to nav-items */
        .nav-items {
            transition: all 0.3s ease-in-out;
        }

        /* Find the existing dropdown-content styles and update/add these styles */

        /* Update the dropdown-content styles */
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #333;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }

        /* Update the dropdown-content a styles */
        .dropdown-content a {
            color: white;
            padding: 8px 12px;
            display: block;
            font-size: 13px;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Update hover effect to match mobile menu */
        .dropdown-content a:hover,
        .dropdown-content a:focus,
        .dropdown-content a:active {
            background-color: #87CEFA;
            color: black;
            text-decoration: none !important; /* Force remove underline on all states */
        }

        /* Add this to show the dropdown when the show class is present */
        .dropdown.show .dropdown-content {
            display: block;
        }

        /* Add these styles for the action buttons */
        .edit-button, .delete-button {
            width: 35px;
            height: 35px;
            padding: 0;
            margin: 0 5px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .edit-button {
            background-color: #4CAF50;
            color: white;
        }

        .edit-button:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 123, 255, 0.3);
        }

        .delete-button {
            background-color: #dc3545;
            color: white;
        }

        .delete-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(220, 53, 69, 0.3);
        }

        .button-icon {
            font-size: 14px;
        }

        /* Tooltip styles */
        .edit-button::after, .delete-button::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            background-color: #333;
            color: white;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .edit-button:hover::after, .delete-button:hover::after {
            opacity: 1;
            visibility: visible;
            bottom: calc(100% + 10px);
        }

        /* Update the modal-content styles for edit modal */
        #editModal .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 25px;
            border: none;
            border-radius: 12px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
        }

        /* Adjust form elements in edit modal for better fit */
        #editModal form input[type="text"],
        #editModal form input[type="number"],
        #editModal form select {
            padding: 10px 12px;
            margin-bottom: 15px;
        }

        #editModal form button[type="submit"] {
            padding: 12px 16px;
        }

        #editModal h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        /* Update the modal-content styles for the add roles modal */
        #myModal .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 25px;
            border: none;
            border-radius: 12px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
        }

        /* Adjust form elements in add modal for better fit */
        #myModal form input[type="text"],
        #myModal form input[type="number"],
        #myModal form select {
            padding: 10px 12px;
            margin-bottom: 15px;
        }

        #myModal form button[type="submit"] {
            padding: 12px 16px;
        }

        #myModal h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .dropdown {
            position: relative;
            display: inline-block;
            margin-top: 4px;
        }


        .package-entry {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
}

.package-entry label {
    display: block;
    margin-bottom: 5px;
}

.package-entry input {
    margin-bottom: 10px;
}

.remove-package {
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    cursor: pointer;
}

.remove-package:hover {
    background-color: #c82333;
}

.package-duration {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.package-duration input {
    width: 100px;
}

.package-duration select {
    width: 120px;
}

.package-entry {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.package-entry input,
.package-entry select {
    margin-bottom: 10px;
}

#roleSelect {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

small {
    color: #666;
    font-size: 0.8em;
    margin-bottom: 15px;
    display: block;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

#roleSelect, #entertainerSelect {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.role-entertainer-pair {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    align-items: flex-end;
}

.role-entertainer-pair .form-group {
    flex: 1;
    margin-bottom: 0;
}

.remove-pair {
    background-color: #ff4444;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
}

.remove-pair:hover {
    background-color: #cc0000;
}

.add-pair-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 15px;
}

.add-pair-btn:hover {
    background-color: #45a049;
}

.add-pair-btn, .add-custom-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 15px;
    width: 100%;
    display: block;
    text-align: center;
}

.add-pair-btn:hover, .add-custom-btn:hover {
    background-color: #45a049;
}

.add-custom-btn {
    background-color: #2196F3; /* Different color for distinction */
}

.add-custom-btn:hover {
    background-color: #1976D2;
}

.button-group {
    margin: 15px 0;
}

.duration-pair {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    position: relative;
}

.duration-pair .form-group {
    flex: 1;
    margin-bottom: 0;
}

.duration-pair .remove-duration {
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: absolute;
    right: -10px;
    top: -10px;
}

.duration-pair .remove-duration:hover {
    background-color: #c82333;
}

#durationPairs {
    margin-top: 15px;
}

.content-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.content-list li {
    margin-bottom: 5px;
}

/* New styles for edit package modal */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="number"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.role-checkboxes {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 4px;
}

.checkbox-wrapper {
    margin-bottom: 8px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.save-button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.cancel-button {
    background-color: #f44336;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.save-button:hover {
    background-color: #45a049;
}

.cancel-button:hover {
    background-color: #da190b;
}
    </style>
</head>
<body>
    <header>
        <!-- Brand logo -->
        <a class="navbar-brand" href="#">
            <img src="../images/logo.jpg" alt="Brand Logo">
        </a>
        
        <!-- Navigation section -->
        <nav>
            <!-- Mobile menu toggle button -->
            <button class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Navigation items/links -->
            <div class="nav-items">
                <a href="admin-dashboard.php">Dashboard</a>
                <a href="admin-appointments.php">Appointment List</a>
                <a href="admin-entertainer.php">Entertainer List</a>
                <a href="admin-roles.php">Talent List</a>
                <!-- Mobile-only links -->
                <a href="admin-profile.php" class="mobile-only">View Profile</a>
                <a href="logout.php" class="mobile-only">Logout</a>
            </div>
            
            <!-- Profile dropdown - hidden on mobile -->
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdown-content">
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

        <section class="container">
            <div class="header-container">
            <h2 class="move-up">Entertainer Talents</h2>
        </div>
        <div class="button-container">
                <button class="add-button" id="addButton">Add Talent</button>
        </div>
        <table>
    <thead>
        <tr>
            <th>Role Name</th>
            <th>Rate</th>
            <th>Duration</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Check if there are results and output them
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
                echo "<td>₱" . htmlspecialchars($row['rate']) . "</td>";
                echo "<td>" . htmlspecialchars($row['duration']) . " " . htmlspecialchars($row['duration_unit']) . "</td>";
                echo "<td>";
                echo "<button class='talent-edit-button edit-button' data-id='" . $row['role_id'] . "' 
                        data-name='" . htmlspecialchars($row['role_name']) . "' 
                        data-rate='" . htmlspecialchars($row['rate']) . "'
                        data-duration='" . htmlspecialchars($row['duration']) . "'
                        data-duration-unit='" . htmlspecialchars($row['duration_unit']) . "'>
                    <i class='fas fa-edit'></i></button>";
                echo "<button class='delete-button' data-id='" . $row['role_id'] . "'><i class='fas fa-trash'></i></button>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='centered'>No roles found</td></tr>";
        }
        ?>
    </tbody>
</table>
        </section>

         <!-- New container added here -->
         <section class="container">

            <!-- Rest of the package deal container content -->
            <div class="header-container">
            <h2 class="move-up">Package Deal</h2>
        </div>
        <div class="button-container">
                <button class="add-button" id="addPackageBtn">
                    <i class="fas fa-plus"></i> Add New Package
                </button>
            </div>
        <table>
            <thead>
                <tr>
                    <th>Package Name</th>
                    <th>Content</th>
                    <th>Perform Duration</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    // Updated query to include entertainer names
                    $comboQuery = "
                        SELECT 
                            cp.combo_id,
                            cp.package_name,
                            cp.price,
                            GROUP_CONCAT(
                                CONCAT(r.role_name, ' - ', ea.first_name, ' ', ea.last_name) 
                                ORDER BY r.role_name 
                                SEPARATOR '||'
                            ) as role_combination,
                            GROUP_CONCAT(DISTINCT CONCAT(pd.duration, ' ', pd.duration_unit) SEPARATOR ', ') as duration_info
                        FROM combo_packages cp
                        LEFT JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
                        LEFT JOIN roles r ON cpr.role_id = r.role_id
                        LEFT JOIN entertainer_account ea ON cpr.entertainer_id = ea.entertainer_id
                        LEFT JOIN package_durations pd ON cp.combo_id = pd.package_id
                        GROUP BY cp.combo_id, cp.package_name, cp.price
                        ORDER BY cp.created_at DESC";

                    $comboResult = $conn->query($comboQuery);

                    // Check if there are results and output them
                    if ($comboResult && $comboResult->num_rows > 0) {
                        while($row = $comboResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['package_name']) . "</td>";
                            
                            // Format content column with line breaks
                            echo "<td><ul class='content-list'>";
                            $roles = explode('||', $row['role_combination']);
                            foreach ($roles as $role) {
                                if (!empty($role)) {
                                    echo "<li>" . htmlspecialchars($role) . "</li>";
                                }
                            }
                            echo "</ul></td>";
                            
                            echo "<td>" . ($row['duration_info'] ? htmlspecialchars($row['duration_info']) : 'No duration set') . "</td>";
                            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                            echo '<td>
                                <button class="package-edit-button edit-button" 
                                    data-tooltip="Edit Package"
                                    data-combo-id="' . htmlspecialchars($row['combo_id']) . '">
                                    <i class="fas fa-edit button-icon"></i>
                                </button>
                                <button class="delete-button" 
                                    data-tooltip="Delete Package"
                                    data-combo-id="' . htmlspecialchars($row['combo_id']) . '">
                                    <i class="fas fa-trash-alt button-icon"></i>
                                </button>
                            </td>';
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='centered'>No combo packages found</td></tr>";
                    }
                ?>
            </tbody>
        </table>
</section>
    </main>

<!-- Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Add Entertainer Talent</h2>
        <form id="addRoleForm">
            <input type="hidden" name="action" value="add_role">
            
            <label for="roleName">Talent Name:</label>
            <input type="text" id="roleName" name="roleName" required>
            
            <label for="rate">Price Rate:</label>
            <input type="number" id="rate" name="rate" required>
            
            <label for="duration">Duration:</label>
            <input type="number" id="duration" name="duration" required value="1">
            
            <label for="durationUnit">Duration Unit:</label>
            <select id="durationUnit" name="durationUnit">
                <option value="hour">Hour</option>
                <option value="song">Song</option>
                <option value="dance">Dance</option>
                <option value="appearance">Appearance</option>
                <option value="show">Show</option>
            </select>
            
            <button type="submit">Submit</button>
        </form>
    </div>
</div>


<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Edit Entertainer Talent</h2>
        <form id="editRoleForm">
            <input type="hidden" name="action" value="edit_role">
            <input type="hidden" id="editRoleId" name="roleId">
            
            <label for="editRoleName">Talent Name:</label>
            <input type="text" id="editRoleName" name="roleName" required>

            <label for="editRate">Price Rate:</label>
            <input type="number" id="editRate" name="rate" required>

            <label for="editDuration">Duration:</label>
            <input type="number" id="editDuration" name="duration" required>

            <label for="editDurationUnit">Duration Unit:</label>
            <select id="editDurationUnit" name="durationUnit">
                <option value="hour">Hour</option>
                <option value="song">Song</option>
                <option value="dance">Dance</option>
                <option value="appearance">Appearance</option>
                <option value="show">Show</option>
            </select>

            <button type="submit">Update</button>
        </form>
    </div>
</div>



<!-- Package Modal -->
<div id="packageModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closePackageModal">&times;</span>
        <h2>Add New Package</h2>
        <form id="addPackageForm">
            <div class="form-group">
                <label for="packageName">Package Name:</label>
                <input type="text" id="packageName" name="packageName" required>
            </div>
            <div id="roleEntertainerPairs">
                <div class="role-entertainer-pair" data-pair-id="1">
                    <div class="form-group">
                        <label for="roleSelect1">Select Role:</label>
                        <select id="roleSelect1" name="roleSelect1" class="role-select" required>
                            <option value="">Select a role</option>
                            <?php
                            // Fetch roles from the database
                            $roleQuery = "SELECT role_id, role_name FROM roles ORDER BY role_name";
                            $roleResult = $conn->query($roleQuery);
                            
                            if ($roleResult && $roleResult->num_rows > 0) {
                                while($role = $roleResult->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($role['role_id']) . '">' 
                                        . htmlspecialchars($role['role_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="entertainerSelect1">Select Entertainer:</label>
                        <select id="entertainerSelect1" name="entertainerSelect1" class="entertainer-select" required disabled>
                            <option value="">Select a role first</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="button-group">
                <button type="button" class="add-pair-btn" id="addPairButton">
                    <i class="fas fa-plus"></i> Add Another Role & Entertainer
                </button>
                <button type="button" class="add-custom-btn" id="customButton" onclick="event.preventDefault(); return false;">
                    <i class="fas fa-plus"></i> Add Perform Duration
                </button>
            </div>
            <div id="durationPairs">
                <!-- Duration pairs will be added here -->
            </div>
            <div class="form-group">
                <label for="packagePrice">Package Price:</label>
                <input type="number" id="packagePrice" name="packagePrice" step="0.01" required>
            </div>
            <div class="form-group">
                <button type="submit" class="submit-btn">Add Package</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for editing package -->
<div id="editPackageModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditPackageModal">&times;</span>
        <h2>Edit Package Details</h2>
        <form id="editPackageForm">
            <input type="hidden" id="editComboId" name="comboId">
            
            <div class="form-group">
                <label for="editPackageName">Package Name:</label>
                <input type="text" id="editPackageName" name="packageName" required>
            </div>

            <div id="editRoleEntertainerPairs">
                <div class="role-entertainer-pair" data-pair-id="1">
                    <div class="form-group">
                        <label for="editRoleSelect1">Select Role:</label>
                        <select id="editRoleSelect1" name="editRoleSelect1" class="role-select" required>
                            <option value="">Select a role</option>
                            <?php
                            // Fetch roles from the database
                            $roleQuery = "SELECT role_id, role_name FROM roles ORDER BY role_name";
                            $roleResult = $conn->query($roleQuery);
                            
                            if ($roleResult && $roleResult->num_rows > 0) {
                                while($role = $roleResult->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($role['role_id']) . '">' 
                                        . htmlspecialchars($role['role_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editEntertainerSelect1">Select Entertainer:</label>
                        <select id="editEntertainerSelect1" name="editEntertainerSelect1" class="entertainer-select" required disabled>
                            <option value="">Select a role first</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="add-pair-btn" id="editAddPairButton">
                    <i class="fas fa-plus"></i> Add Another Role & Entertainer
                </button>
                <button type="button" class="add-custom-btn" id="editCustomButton">
                    <i class="fas fa-plus"></i> Add Perform Duration
                </button>
            </div>

            <div id="editDurationPairs">
                <!-- Duration pairs will be added here -->
            </div>

            <div class="form-group">
                <label for="editPackagePrice">Package Price:</label>
                <input type="number" id="editPackagePrice" name="packagePrice" step="0.01" required>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-btn">Update Package</button>
            </div>
        </form>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal elements
            const modal = document.getElementById("myModal");
            const editModal = document.getElementById("editModal");
            const addButton = document.getElementById("addButton");
            const span = document.getElementsByClassName("close")[0];
            const packageModal = document.getElementById("packageModal");
            const addPackageBtn = document.getElementById("addPackageBtn");
            const closePackageModal = document.getElementById("closePackageModal");
            const closeEditModal = document.getElementById("closeEditModal");
            const closeEditPackageModal = document.getElementById("closeEditPackageModal");
            const customButton = document.getElementById("customButton");

            // Handle custom button click (Add Perform Duration)
            if(customButton) {
                customButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Check if duration pair already exists
                    if (document.querySelector('.duration-pair')) {
                        alert('You can only add one duration for this package.');
                        return false;
                    }

                    const durationPairs = document.getElementById('durationPairs');
                    const newDuration = document.createElement('div');
                    newDuration.className = 'duration-pair';
                    newDuration.dataset.durationId = '1';
                    
                    newDuration.innerHTML = `
                        <div class="form-group">
                            <label for="duration1">Duration:</label>
                            <input type="number" id="duration1" name="duration1" required min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label for="durationUnit1">Duration Unit:</label>
                            <select id="durationUnit1" name="durationUnit1" required>
                                <option value="hour">Hour</option>
                                <option value="song">Song</option>
                                <option value="dance">Dance</option>
                                <option value="appearance">Appearance</option>
                                <option value="show">Show</option>
                            </select>
                        </div>
                        <button type="button" class="remove-duration" onclick="removeDuration('1')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    durationPairs.appendChild(newDuration);
                    
                    // Disable the Add Perform Duration button
                    customButton.disabled = true;
                    customButton.style.opacity = '0.5';
                    customButton.style.cursor = 'not-allowed';
                    
                    return false;
                });
            }

            // Function to remove duration pair
            window.removeDuration = function(durationId) {
                const pair = document.querySelector(`.duration-pair[data-duration-id="${durationId}"]`);
                if (pair) {
                    pair.remove();
                    // Re-enable the Add Perform Duration button
                    const customButton = document.getElementById("customButton");
                    if (customButton) {
                        customButton.disabled = false;
                        customButton.style.opacity = '1';
                        customButton.style.cursor = 'pointer';
                    }
                }
            };

// Handle edit buttons for talents
document.querySelectorAll('.talent-edit-button').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const rate = this.dataset.rate;
        const duration = this.dataset.duration;
        const durationUnit = this.dataset.durationUnit;

        // Populate the edit form
        document.getElementById('editRoleId').value = id;
        document.getElementById('editRoleName').value = name;
        document.getElementById('editRate').value = rate;
        document.getElementById('editDuration').value = duration;
        document.getElementById('editDurationUnit').value = durationUnit;

        // Show the edit modal
        document.getElementById('editModal').style.display = "block";
    });
});



// Handle edit buttons for packages
document.addEventListener('click', function(e) {
    if (e.target.closest('.package-edit-button')) {
        const button = e.target.closest('.package-edit-button');
        const comboId = button.getAttribute('data-combo-id');
        const editPackageModal = document.getElementById('editPackageModal');
        
        // Ensure the modal exists
        if (!editPackageModal) {
            console.error('Edit package modal not found');
            return;
        }

        // Show the modal first to ensure elements are rendered
        editPackageModal.style.display = "block";

        // Wait for modal to be visible before proceeding
        setTimeout(() => {
            // Initialize containers
            const pairsContainer = document.getElementById('editRoleEntertainerPairs');
            const durationContainer = document.getElementById('editDurationPairs');
            
            if (!pairsContainer || !durationContainer) {
                console.error('Required containers not found');
                return;
            }

            // Clear existing content
            pairsContainer.innerHTML = '';
            durationContainer.innerHTML = '';
            
            // Fetch package details
            fetch('get-package-details.php?combo_id=' + comboId)
                .then(response => response.json())
                .then(data => {
                    if (!data) {
                        throw new Error('No data received');
                    }

                    // Set basic package details
                    const editComboId = document.getElementById('editComboId');
                    const editPackageName = document.getElementById('editPackageName');
                    const editPackagePrice = document.getElementById('editPackagePrice');

                    if (editComboId) editComboId.value = comboId;
                    if (editPackageName) editPackageName.value = data.package_name || '';
                    if (editPackagePrice) editPackagePrice.value = data.price || '';

                    // Add pairs one by one with delay
                    if (data.pairs && Array.isArray(data.pairs)) {
                        data.pairs.forEach((pair, index) => {
                            setTimeout(() => {
                                try {
                                    addEditRoleEntertainerPair(pair.role_id, pair.entertainer_id);
                                } catch (error) {
                                    console.error('Error adding pair:', error);
                                }
                            }, index * 300);
                        });
                    }

                    // Add duration after all pairs are added
                    if (data.duration && data.duration_unit) {
                        setTimeout(() => {
                            try {
                                addEditDurationPair(data.duration, data.duration_unit);
                            } catch (error) {
                                console.error('Error adding duration:', error);
                            }
                        }, (data.pairs?.length || 0) * 300 + 300);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load package details: ' + error.message);
                });
        }, 100); // Wait for modal to be visible
    }
});

            // Handle delete buttons for packages
            document.querySelectorAll('.delete-button').forEach(button => {
                button.addEventListener('click', function() {
                    const comboId = this.getAttribute('data-combo-id');
                    
                    if (confirm('Are you sure you want to delete this package?')) {
                        fetch('delete-package.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                comboId: comboId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Package deleted successfully!');
                                // Remove the row from the table
                                this.closest('tr').remove();
                            } else {
                                alert('Error deleting package: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the package');
                        });
                    }
                });
            });

// Handle role selection change for dynamic pairs
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('role-select')) {
        const pairId = e.target.id.replace('roleSelect', '').replace('editRoleSelect', '');
        const isEdit = e.target.id.startsWith('editRoleSelect');
        const entertainerSelect = document.getElementById((isEdit ? 'editEntertainerSelect' : 'entertainerSelect') + pairId);
        const roleId = e.target.value;
        
        if (!roleId || !entertainerSelect) {
            if (entertainerSelect) {
                entertainerSelect.disabled = true;
                entertainerSelect.innerHTML = '<option value="">Select a role first</option>';
            }
            return;
        }
        
        // Fetch entertainers for the selected role
        fetch(`get-entertainers.php?roleId=${roleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    entertainerSelect.innerHTML = '<option value="">Select an entertainer</option>';
                    data.entertainers.forEach(entertainer => {
                        entertainerSelect.innerHTML += `<option value="${entertainer.id}">${entertainer.name}</option>`;
                    });
                    entertainerSelect.disabled = false;
                } else {
                    alert('Error loading entertainers: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading entertainers');
            });
    }
});

            // Handle adding new role-entertainer pairs
            let pairCounter = 1;
            const addPairButton = document.getElementById('addPairButton');
            if (addPairButton) {
                addPairButton.addEventListener('click', function() {
                    pairCounter++;
                    const newPair = document.createElement('div');
                    newPair.className = 'role-entertainer-pair';
                    newPair.dataset.pairId = pairCounter;
                    
                    newPair.innerHTML = `
                        <div class="form-group">
                            <label for="roleSelect${pairCounter}">Select Role:</label>
                            <select id="roleSelect${pairCounter}" name="roleSelect${pairCounter}" class="role-select" required>
                                <option value="">Select a role</option>
                                ${Array.from(document.querySelector('#roleSelect1').options)
                                    .map(opt => `<option value="${opt.value}">${opt.text}</option>`)
                                    .join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="entertainerSelect${pairCounter}">Select Entertainer:</label>
                            <select id="entertainerSelect${pairCounter}" name="entertainerSelect${pairCounter}" class="entertainer-select" required disabled>
                                <option value="">Select a role first</option>
                            </select>
                        </div>
                        <button type="button" class="remove-pair" onclick="removePair(${pairCounter})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    document.getElementById('roleEntertainerPairs').appendChild(newPair);
                });
            }

            // Function to remove a role-entertainer pair
            window.removePair = function(pairId) {
                const pair = document.querySelector(`.role-entertainer-pair[data-pair-id="${pairId}"]`);
                if (pair) {
                    pair.remove();
                }
            };

// Function to add role-entertainer pair in edit form
function addEditRoleEntertainerPair(selectedRoleId = '', selectedEntertainerId = '') {
    const container = document.getElementById('editRoleEntertainerPairs');
    if (!container) {
        console.error('Role-entertainer container not found');
        return;
    }

    const pairCount = container.children.length + 1;
    const pairDiv = document.createElement('div');
    pairDiv.className = 'role-entertainer-pair';
    pairDiv.dataset.pairId = pairCount;

    // Get all role options from the first role select in the form
    const firstRoleSelect = document.querySelector('#editRoleSelect1');
    let roleOptionsHtml = '<option value="">Select a role</option>';
    
    if (firstRoleSelect) {
        Array.from(firstRoleSelect.options).forEach(option => {
            if (option.value) {
                roleOptionsHtml += `<option value="${option.value}" ${option.value === selectedRoleId ? 'selected' : ''}>${option.textContent}</option>`;
            }
        });
    } else {
        // Fallback to fetch roles from the server
        fetch('get-roles.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.roles) {
                    roleOptionsHtml = '<option value="">Select a role</option>' + 
                        data.roles.map(role => 
                            `<option value="${role.role_id}" ${role.role_id === selectedRoleId ? 'selected' : ''}>${role.role_name}</option>`
                        ).join('');
                    updatePairContent();
                }
            })
            .catch(error => {
                console.error('Error fetching roles:', error);
                alert('Failed to load roles');
            });
    }

    function updatePairContent() {
        pairDiv.innerHTML = `
            <div class="form-group">
                <label for="editRoleSelect${pairCount}">Select Role:</label>
                <select id="editRoleSelect${pairCount}" name="editRoleSelect${pairCount}" class="role-select" required>
                    ${roleOptionsHtml}
                </select>
            </div>
            <div class="form-group">
                <label for="editEntertainerSelect${pairCount}">Select Entertainer:</label>
                <select id="editEntertainerSelect${pairCount}" name="editEntertainerSelect${pairCount}" class="entertainer-select" required disabled>
                    <option value="">Select a role first</option>
                </select>
            </div>
            <button type="button" class="remove-pair" onclick="removePair(${pairCount})">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(pairDiv);

        // Set up role selection and load entertainers
        if (selectedRoleId) {
            const roleSelect = pairDiv.querySelector(`#editRoleSelect${pairCount}`);
            if (roleSelect) {
                roleSelect.value = selectedRoleId;
                
                // Fetch entertainers for the selected role
                fetch(`get-entertainers.php?roleId=${selectedRoleId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const entertainerSelect = pairDiv.querySelector(`#editEntertainerSelect${pairCount}`);
                            if (entertainerSelect) {
                                entertainerSelect.innerHTML = '<option value="">Select an entertainer</option>';
                                data.entertainers.forEach(entertainer => {
                                    entertainerSelect.innerHTML += `<option value="${entertainer.id}" ${entertainer.id == selectedEntertainerId ? 'selected' : ''}>${entertainer.name}</option>`;
                                });
                                entertainerSelect.disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading entertainers:', error);
                    });
            }
        }
    }

    // If we already have role options, update content immediately
    if (firstRoleSelect) {
        updatePairContent();
    }
}

// Function to add duration pair in edit form
function addEditDurationPair(duration = '1', unit = 'hour') {
    const container = document.getElementById('editDurationPairs');
    if (!container) {
        console.error('Duration container not found');
        return;
    }

    // Check if duration pair already exists
    if (container.children.length > 0) {
        return; // Only one duration pair allowed
    }
    
    const durationDiv = document.createElement('div');
    durationDiv.className = 'duration-pair';
    durationDiv.dataset.durationId = '1';
    
    durationDiv.innerHTML = `
        <div class="form-group">
            <label for="editDuration1">Duration:</label>
            <input type="number" id="editDuration1" name="editDuration1" required min="1" value="${duration}">
        </div>
        <div class="form-group">
            <label for="editDurationUnit1">Duration Unit:</label>
            <select id="editDurationUnit1" name="editDurationUnit1" required>
                <option value="hour" ${unit === 'hour' ? 'selected' : ''}>Hour</option>
                <option value="song" ${unit === 'song' ? 'selected' : ''}>Song</option>
                <option value="dance" ${unit === 'dance' ? 'selected' : ''}>Dance</option>
                <option value="appearance" ${unit === 'appearance' ? 'selected' : ''}>Appearance</option>
                <option value="show" ${unit === 'show' ? 'selected' : ''}>Show</option>
            </select>
        </div>
        <button type="button" class="remove-duration" onclick="removeEditDuration('1')">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(durationDiv);

    // Disable the Add Perform Duration button
    const editCustomButton = document.getElementById('editCustomButton');
    if (editCustomButton) {
        editCustomButton.disabled = true;
        editCustomButton.style.opacity = '0.5';
        editCustomButton.style.cursor = 'not-allowed';
    }
}

// Function to remove duration pair in edit form
window.removeEditDuration = function(durationId) {
    const pair = document.querySelector(`#editDurationPairs .duration-pair[data-duration-id="${durationId}"]`);
    if (pair) {
        pair.remove();
        // Re-enable the Add Perform Duration button
        const editCustomButton = document.getElementById('editCustomButton');
        if (editCustomButton) {
            editCustomButton.disabled = false;
            editCustomButton.style.opacity = '1';
            editCustomButton.style.cursor = 'pointer';
        }
    }
};

            // Handle talent edit form submission
            const editRoleForm = document.getElementById('editRoleForm');
            if (editRoleForm) {
                editRoleForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = {
                        roleId: document.getElementById('editRoleId').value,
                        roleName: document.getElementById('editRoleName').value,
                        rate: document.getElementById('editRate').value,
                        duration: document.getElementById('editDuration').value,
                        durationUnit: document.getElementById('editDurationUnit').value
                    };

                    fetch('update-role.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Talent updated successfully!');
                            editModal.style.display = "none";
                            location.reload();
                        } else {
                            alert('Error updating talent: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the talent');
                    });
                });
            }

            // Handle package form submission
            const addPackageForm = document.getElementById("addPackageForm");
            if (addPackageForm) {
                addPackageForm.addEventListener("submit", function(e) {
                    e.preventDefault();
                    
                    const packageName = document.getElementById("packageName").value;
                    const packagePrice = document.getElementById("packagePrice").value;
                    
                    // Collect all role-entertainer pairs
                    const pairs = [];
                    document.querySelectorAll('.role-entertainer-pair').forEach(pair => {
                        const pairId = pair.dataset.pairId;
                        const roleSelect = document.getElementById(`roleSelect${pairId}`);
                        const entertainerSelect = document.getElementById(`entertainerSelect${pairId}`);
                        
                        if (roleSelect.value && entertainerSelect.value) {
                            pairs.push({
                                roleId: roleSelect.value,
                                entertainerId: entertainerSelect.value
                            });
                        }
                    });

                    // Collect all duration pairs
                    const durations = [];
                    document.querySelectorAll('.duration-pair').forEach(pair => {
                        const durationId = pair.dataset.durationId;
                        const duration = document.getElementById(`duration${durationId}`).value;
                        const durationUnit = document.getElementById(`durationUnit${durationId}`).value;
                        
                        durations.push({
                            duration: duration,
                            durationUnit: durationUnit
                        });
                    });
                    
                    if (pairs.length === 0) {
                        alert('Please add at least one role and entertainer');
                        return;
                    }
                    
                    // Add AJAX call to save the package with durations
                    fetch('save-package.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            packageName: packageName,
                            packagePrice: packagePrice,
                            pairs: pairs,
                            durations: durations
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Package added successfully!');
                            packageModal.style.display = "none";
                            location.reload();
                        } else {
                            alert('Error adding package: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding the package');
                    });
                });
            }

            // When the user clicks the button, open the modal
            if(addButton) {
                addButton.onclick = function() {
                    modal.style.display = "block";
                }
            }

            if(addPackageBtn) {
                addPackageBtn.onclick = function() {
                    packageModal.style.display = "block";
                }
            }

            // When the user clicks on <span> (x), close the modal
            if(span) {
                span.onclick = function() {
                    modal.style.display = "none";
                }
            }

            if(closePackageModal) {
                closePackageModal.onclick = function() {
                    packageModal.style.display = "none";
                }
            }

            if(closeEditModal) {
                closeEditModal.onclick = function() {
                    editModal.style.display = "none";
                }
            }

            if(closeEditPackageModal) {
                closeEditPackageModal.addEventListener('click', function() {
                    const editPackageModal = document.getElementById('editPackageModal');
                    if (editPackageModal) {
                        editPackageModal.style.display = "none";
                    }
                });
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
                if (event.target == editModal) {
                    editModal.style.display = "none";
                }
                if (event.target == packageModal) {
                    packageModal.style.display = "none";
                }
            }
        });
    </script>
</body>
</html>
