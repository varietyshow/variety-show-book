<?php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

if (isset($_SESSION['entertainer_id'])) {
    $entertainer_id = $_SESSION['entertainer_id'];
} else {
    die("User not logged in.");
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database connection
$servername = "sql12.freesqldatabase.com"; // Your database server name
$username = "sql12777569";        // Your database username
$password = "QlgHSeuU1n";            // Your database password
$dbname = "sql12777569"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add this code after the database connection check
// Update past dates to unavailable
$current_date = date('Y-m-d');
$update_past_dates = $conn->prepare("
    UPDATE sched_time 
    SET status = 'Unavailable' 
    WHERE date < ? 
    AND status = 'Available'
");
$update_past_dates->bind_param("s", $current_date);
$update_past_dates->execute();

// Modify the select query to handle the status display
$stmt = $conn->prepare("
    SELECT sched_id, date, 
    CASE 
        WHEN date < CURDATE() AND status = 'Available' THEN 'Unavailable'
        ELSE status 
    END as status 
    FROM sched_time 
    WHERE entertainer_id = ?
    ORDER BY date ASC
");

if ($stmt) {
    $stmt->bind_param("i", $entertainer_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "Error preparing statement: " . $conn->error;
}

// Check if the statement was prepared correctly
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Check if the query was successful
if ($result === false) {
    die("Error executing query: " . $stmt->error);
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
</head>
<style>
         body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow: auto;
        }

        .content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-left: 30px;
            padding: 20px;
            padding-top: 10px;
            background-color: transparent;
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
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 95%;
            max-width: 1200px;
            margin-left: 0;
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
            gap: 8px;
            margin-left: 8px;
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
            padding: 8px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .nav-items {
            display: flex;
            gap: 30px;
            margin-right: 60px;
            position: relative;
            top: -5px;
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
            width: 35px; /* Slightly reduced from 40px */
            height: 35px; /* Make it square */
            position: relative;
            top: 1px; /* Fine-tune vertical position */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

                .button-group {
    display: flex;
    gap: 5px; /* Adjust this value for the desired spacing */
}

.button-group button {
    padding: 5px 10px; /* Add some padding for better appearance */
    cursor: pointer; /* Change cursor on hover */
}

.edit-btn {
    background-color: blue; /* Blue background */
    color: white; /* White text */
    border: none; /* Remove default border */
    padding: 5px 10px; /* Padding */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s; /* Transition for hover effect */
}

.edit-btn:hover {
    background-color: darkblue; /* Darker blue on hover */
}

.delete-btn {
    background-color: red; /* Red background */
    color: white; /* White text */
    border: none; /* Remove default border */
    padding: 5px 10px; /* Padding */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s; /* Transition for hover effect */
}

.delete-btn:hover {
    background-color: darkred; /* Darker red on hover */
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
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    opacity: 1;
}

.modal-content {
    background: linear-gradient(145deg, #ffffff, #f5f5f5);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    width: 50%;
    max-width: 500px;
    position: relative;
    margin: 0 auto;
    transform: translateY(20px);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show .modal-content {
    transform: translateY(0);
    opacity: 1;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 18px;
    color: #666;
}

.close:hover {
    background: #e0e0e0;
    transform: rotate(90deg);
}

.modal h2 {
    color: #333;
    font-size: 24px;
    margin-bottom: 25px;
    font-weight: 600;
}

#editForm {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    position: relative;
}

#editForm label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
    font-weight: 500;
    transition: color 0.3s ease;
}

#editForm input[type="date"],
#editForm input[type="time"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 16px;
    color: #333;
    background-color: #fff;
    transition: all 0.3s ease;
    outline: none;
    height: 48px;
    box-sizing: border-box;
}

#editForm input[type="date"]:focus,
#editForm input[type="time"]:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

#editForm button[type="submit"] {
    background: linear-gradient(145deg, #4CAF50, #45a049);
    color: white;
    padding: 14px 16px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    margin-top: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
    height: 48px;
    width: 100%;
    box-sizing: border-box;
}

#editForm button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
}

#editForm button[type="submit"]:active {
    transform: translateY(0);
}

/* Mobile styles for edit modal */
@media (max-width: 768px) {
    .modal-content {
        width: 85%;
        max-width: 320px;
        padding: 20px;
        border-radius: 12px;
    }

    .modal h2 {
        font-size: 20px;
        margin-bottom: 20px;
    }

    #editForm {
        gap: 15px;
    }

    #editForm label {
        font-size: 13px;
    }

    #editForm input[type="date"],
    #editForm input[type="time"] {
        padding: 12px 14px;
        font-size: 14px;
        border-radius: 8px;
        height: 44px;
    }

    #editForm button[type="submit"] {
        padding: 12px 14px;
        font-size: 15px;
        border-radius: 8px;
        height: 44px;
    }

    .close {
        width: 28px;
        height: 28px;
        font-size: 16px;
        right: 15px;
        top: 15px;
    }
}

        .search-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: -8px;
        }

        #date-search {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 200px;
        }

        .search-btn {
            padding: 8px;
            width: 36px;
            height: 36px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-btn:hover {
            background-color: #45a049;
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 20px;
        }

        .left-controls {
            flex: 0 0 auto;
        }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .refresh-btn {
            width: 36px;
            height: 36px;
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
            margin-left: 4px;
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

        .navbar-brand {
            margin-right: auto;
            padding: 10px 0;
            position: relative;
            top: -4px;
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }

                .nav-items {
                    display: flex;
                    gap: 30px;
                    margin-right: 80px;
                    position: relative;
                    top: -5px;
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
                    text-decoration: none;
                    color: black;
                }

                .dropbtn {
                    background: none;
                    border: none;
                    cursor: pointer;
                }

                .dropbtn img {
                    width: 40px;
                    height: auto;
                }

                .dropdown {
                    position: relative;
                    display: inline-block;
                    margin-right: 15px;
                    top: -5px; /* Add this to move it higher */
                }

                .dropdown-content {
                    display: none;
                    position: absolute;
                    top: calc(100% + 5px); /* Adjust to account for new dropdown position */
                    right: 15px;
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

                .dropdown-content a:hover {
                    background-color: #87CEFA;
                    color: black;
                }

                .dropdown.show .dropdown-content {
                    display: block;
                }

                .mobile-only {
                    display: none; /* Remove !important to allow override in mobile view */
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
                        right: -70px;
                        left: auto;
                        width: 190px;
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

                    .mobile-only {
                        display: block !important;
                    }
                }

                /* Menu toggle animation */
                .menu-toggle.active span:nth-child(1) {
                    transform: rotate(-45deg) translate(-5px, 6px);
                }

                .menu-toggle.active span:nth-child(2) {
                    opacity: 0;
                }

                .menu-toggle.active span:nth-child(3) {
                    transform: rotate(45deg) translate(-5px, -6px);
                }

                .mobile-only {
                    display: none; /* Remove !important to allow override in mobile view */
                }

                /* Header base styles */
                header {
                    display: flex;
                    justify-content: flex-start;
                    align-items: center;
                    padding: 0 15px;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 60px;
                    background-color: #333;
                    z-index: 1000;
                }

                /* Update navbar-brand styles */
                .navbar-brand {
                    margin-right: auto;
                    padding: 10px 0;
                    position: relative;
                }

                /* Update mobile styles */
                @media (max-width: 768px) {
                    .navbar-brand {
                        position: relative;
                        left: 0;
                        padding-left: 0;
                    }

                    .menu-toggle {
                        position: relative;
                        margin-left: 15px;
                    }

                    nav {
                        display: flex;
                        align-items: center;
                    }
                }

                /* Navigation container styles */
                nav {
                    display: flex;
                    align-items: center;
                    margin-left: auto;
                    margin-right: 15px;
                }

.left-controls select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
    font-size: 16px;
    height: 40px;
}

.left-controls select:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
}

.right-controls .search-container {
    display: flex;
    gap: 5px;
}

.search-container input {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    height: 20px;
    width: 150px;
    font-size: 14px;
}

.search-container button {
    padding: 5px 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    height: 35px;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-container button:hover {
    background-color: #45a049;
}
</style>
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
                <a href="entertainer-dashboard.php">Dashboard</a>
                <a href="entertainer-mysched.php">View Schedule</a>
                <a href="entertainer-myAppointment.php">My Appointment</a>
                <a href="entertainer-profile.php" class="mobile-only">View Profile</a>
                <a href="logout.php" class="mobile-only">Logout</a>
            </div>
            
            <!-- Profile dropdown - hidden on mobile -->
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
    </main>

    <div class="content">
        <div class="schedule-container">
            <h2>Schedule List</h2>
            <div class="schedule-header">
                <div class="left-controls">
                    <select id="status-filter">
                        <option value="">All Status</option>
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="right-controls">
                    <div class="search-container">
                        <input type="date" id="date-search" placeholder="Search date...">
                        <button class="search-btn" onclick="filterByDate()">🔍</button>
                    </div>
                </div>
            </div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body">
                    <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                // Store the raw date in a data attribute for comparison
                                echo "<td data-date='" . $row['date'] . "'>" . date('F d, Y', strtotime($row['date'])) . "</td>";
                                echo "<td>" . $row['status'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='2'>No schedules found</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
            <div class="pagination">
                <div>
                    <label for="items-per-page">Items per page:</label>
                    <select id="items-per-page">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                    </select>
                </div>
                <div class="page-controls">
                    <button aria-label="Previous Page">◀</button>
                    <span id="pagination-info">0-0 of 0</span>
                    <button aria-label="Next Page">▶</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close">&times;</button>
        <h2>Edit Schedule</h2>
        <form id="editForm">
            <input type="hidden" id="scheduleId" value="">
            <div class="form-group">
                <label for="editDate">Date</label>
                <input type="date" id="editDate" required>
            </div>
            <div class="form-group">
                <label for="editStartTime">Start Time</label>
                <input type="time" id="editStartTime" required>
            </div>
            <div class="form-group">
                <label for="editEndTime">End Time</label>
                <input type="time" id="editEndTime" required>
            </div>
            <button type="submit">Update Schedule</button>
        </form>
    </div>
</div>

    <script>
        // Toggle mobile menu visibility and animation
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

        // Toggle dropdown menu for profile
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

        // JavaScript to handle the modal
    const modal = document.getElementById("editModal");
    const span = document.getElementsByClassName("close")[0];

    // Add event listeners to all edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const dateStr = row.cells[0].innerText;
            const rowDate = new Date(dateStr);
            const today = new Date();
            
            // Set both dates to start of day for proper comparison
            rowDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);
            
            if (rowDate < today) {
                alert('Cannot edit past dates');
                return; // Stop here - don't show the modal
            }

            // Only proceed with showing modal if date is not in the past
            const scheduleId = row.dataset.sched_id;
            const startTime = row.cells[1].innerText;
            const endTime = row.cells[2].innerText;
            
            document.getElementById('editDate').value = dateStr;
            document.getElementById('editStartTime').value = startTime;
            document.getElementById('editEndTime').value = endTime;
            document.getElementById('scheduleId').value = scheduleId;

            showModal(modal);
        });
    });

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        hideModal(modal);
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            hideModal(modal);
        }
    }

   // Handle the form submission (for demonstration)
document.getElementById('editForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const scheduleId = document.getElementById('scheduleId').value;
    const date = document.getElementById('editDate').value;
    let startTime = document.getElementById('editStartTime').value;
    let endTime = document.getElementById('editEndTime').value;

    // Ensure the times are in the correct format
    startTime = startTime.length === 5 ? startTime + ':00' : startTime;
    endTime = endTime.length === 5 ? endTime + ':00' : endTime;

    const data = {
        scheduleId: scheduleId,
        date: date,
        startTime: startTime,
        endTime: endTime
    };

    fetch('update_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Schedule updated successfully!');
            hideModal(modal);
            location.reload();
        } else {
            alert('Error updating schedule: ' + (data.error || 'Unknown error'));
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('Error updating schedule: ' + error);
    });
});

// Add event listeners to all delete buttons
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        const row = this.closest('tr');
        const scheduleId = row.dataset.sched_id;

        if (confirm('Are you sure you want to delete this schedule?')) {
            fetch('delete_schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ scheduleId: scheduleId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule deleted successfully!');
                    row.remove();
                } else {
                    alert('Error deleting schedule: ' + data.error);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('Error deleting schedule: ' + error);
            });
        }
    });
});

// Add event listener for the status dropdown
document.getElementById('status-filter').addEventListener('change', filterByStatus);

// Separate functions for date and status filtering
function filterByStatus() {
    const statusFilter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('#schedule-table-body tr');
    let visibleRows = 0;

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        if (cells.length >= 2) {
            const rowStatus = cells[1].textContent.trim();
            if (!statusFilter || rowStatus === statusFilter) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        }
    });

    updateNoResultsMessage(visibleRows);
}

function filterByDate() {
    const dateSearch = document.getElementById('date-search').value;
    const rows = document.querySelectorAll('#schedule-table-body tr');
    let visibleRows = 0;

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        if (cells.length >= 2) {
            const dateCell = cells[0];
            const rowDate = dateCell.getAttribute('data-date'); // Get the raw date from data attribute
            
            if (dateSearch) {
                // Format the search date to match the database format (YYYY-MM-DD)
                const searchDate = new Date(dateSearch);
                const formattedSearchDate = searchDate.toISOString().split('T')[0];
                
                if (rowDate === formattedSearchDate) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            } else {
                // If no date selected, show all rows
                row.style.display = '';
                visibleRows++;
            }
        }
    });

    updateNoResultsMessage(visibleRows);
}

function updateNoResultsMessage(visibleRows) {
    const tbody = document.getElementById('schedule-table-body');
    const existingNoResults = tbody.querySelector('.no-results');
    
    if (visibleRows === 0) {
        if (!existingNoResults) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = '<td colspan="2">No schedules found matching the filters</td>';
            tbody.appendChild(noResultsRow);
        }
    } else {
        if (existingNoResults) {
            existingNoResults.remove();
        }
    }
}

// Initialize the filter when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for search button
    const searchBtn = document.querySelector('.search-btn');
    searchBtn.addEventListener('click', filterByDate);
});

// Add this function after your existing JavaScript code
function showModal(modal) {
    modal.style.display = 'flex';
    // Trigger reflow
    modal.offsetHeight;
    modal.classList.add('show');
}

function hideModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}
    </script>
</body>
</html>
