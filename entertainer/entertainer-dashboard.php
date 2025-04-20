<?php
/* 
 * Entertainer Dashboard Page
 * This page serves as the main interface for entertainers after logging in.
 * It includes a calendar view for scheduling and a form for creating appointments.
 */

// Start the session to access session variables
session_start();

// Check if the user is logged in and redirect if not
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php");
    exit();
}

// Retrieve and sanitize the entertainer's first name from session
$first_name = htmlspecialchars($_SESSION['first_name']);

// Connect to database
require_once 'db_connect.php';

// Check if the user is logged in and is an entertainer
if (!isset($_SESSION['entertainer_id'])) {
    header("Location: login.php");
    exit();
}

$entertainer_id = $_SESSION['entertainer_id'];

// Fetch all schedules for this entertainer
$schedules = array();
$current_month = date('Y-m');
$sql = "SELECT date, status FROM sched_time WHERE entertainer_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $entertainer_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $schedules[$row['date']] = $row['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <!-- External stylesheet for main styling -->
    <link rel="stylesheet" href="style3.css">
    <!-- Calendar functionality JavaScript -->
    <script src="calendar.js" defer></script>
</head>
<style>
/* 
 * Dashboard Layout Styles
 * Implements a responsive layout with left (calendar) and right (appointment form) panels
 */

/* Main container with flex layout for responsive design */
.container {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    margin-top: 20px;
    gap: 20px;
    flex-wrap: wrap;
    align-items: flex-start;
}

/* Left and Right Panels */
.left-panel, .right-panel {
    box-sizing: border-box;
}

.left-panel {
    flex: 1 1 60%;
    max-width: 60%;
    margin-left: 10px;
}

.right-panel {
    flex: 1 1 20%;
    width: 300px;
    max-width: 300px;
    min-width: 300px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f8f9fa;
    margin-right: 10px;
    height: fit-content;
}

/* Mobile Responsive Styles */
@media screen and (max-width: 768px) {
    .container {
        flex-direction: column;
        padding: 5px;
    }
    
    .left-panel {
        flex: 1 1 100%;
        max-width: 100%;
        margin: 0 5px;
        order: 1;
    }
    
    .right-panel {
        flex: 1 1 100%;
        max-width: 100%;
        width: auto;
        min-width: auto;
        margin: 0 5px;
        order: 2;
    }
    
    .calendar {
        padding: 10px;
    }

    .calendar-grid {
        gap: 5px;
        width: 100%;
    }

    .calendar-grid .date-box {
        height: 60px;
        padding: 2px;
        min-width: 0;
        width: 100%;
        background-color: #fff;
    }

    .calendar-grid .date-box .day-number {
        font-size: 14px;
        margin-bottom: 2px;
    }

    .calendar-grid .date-box .book-now {
        padding: 3px 6px;
        font-size: 10px;
    }

    .calendar-header h2 {
        font-size: 20px;
    }

    .calendar-header button {
        padding: 8px;
        font-size: 12px;
    }
}

/* Calendar Section */
.calendar {
    background-color: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.calendar-header button {
    padding: 10px;
    background-color: green;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.calendar-header h2 {
    font-size: 24px;
    margin: 0;
    text-align: center;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* 7 columns for each day of the week */
    gap: 10px;
}

.calendar-grid .date-box {
    height: 80px;
    padding: 5px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    text-align: center;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
}

.calendar-grid .date-box.available {
    border-color: #4CAF50;
    background-color: #E8F5E9;
}

.calendar-grid .date-box.booked {
    border-color: #F44336;
    background-color: #FFEBEE;
}

.calendar-grid .date-box.unavailable {
    border-color: #9E9E9E;
    background-color: #F5F5F5;
}

.status-badge {
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-top: 4px;
}

.status-badge.available {
    background-color: #4CAF50;
    color: white;
}

.status-badge.booked {
    background-color: #F44336;
    color: white;
}

.status-badge.unavailable {
    background-color: #9E9E9E;
    color: white;
}

/* Appointment Form Section */
.right-panel h2 {
    font-size: 22px;
    text-align: center;
    color: #007bff;
}

.form-group {
    margin-bottom: 15px;
    width: 100%;
}

.form-group label {
    font-size: 14px;
    display: block;
    margin-bottom: 5px;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group select {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

.schedule-type-content {
    display: none;
    margin-top: 15px;
}

.schedule-type-content.active {
    display: block;
}

.weekday-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.weekday-checkbox {
    display: none;
}

.weekday-label {
    display: inline-block;
    padding: 8px 12px;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.weekday-checkbox:checked + .weekday-label {
    background-color: #007bff;
    color: white;
    border-color: #0056b3;
}

.form-group button {
    width: 100%;
    padding: 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    box-sizing: border-box;
}

.form-group button:hover {
    background-color: #0056b3;
}

/* Media Query for Mobile Devices */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
        padding: 5px;
        align-items: center;
    }

    .left-panel {
        width: 95%;
        max-width: 95%;
        margin: 10px auto;
    }

    .right-panel {
        width: 300px;
        max-width: 300px;
        min-width: 300px;
        margin: 20px auto;
        padding: 15px;
    }

    /* Ensure form elements maintain proper width */
    .form-group input[type="date"],
    .form-group input[type="time"] {
        width: 100%; /* Full width */
        box-sizing: border-box;
    }
}

/* Additional styles for very small screens */
@media (max-width: 320px) {
    .right-panel {
        width: 280px;
        max-width: 280px;
        min-width: 280px;
        padding: 10px;
    }
}

.nav-items {
            display: flex;
            gap: 30px;
            margin-right: 120px;
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
            width: 40px; /* Adjust image size */
            height: auto;
            position: relative;
            top: -4px; /* Maintain aspect ratio */
        }

        .navbar-brand img {
                    width: 40px; /* Adjust size as needed */
                    height: 40px; /* Adjust size as needed */
                    border-radius: 40%; /* Make the image circular */
                }


/* Add these styles to your existing CSS */
.date-box.booked .book-now {
    background-color: #007bff; /* Changed to blue */
    cursor: pointer;
}

.date-box.pending .book-now {
    background-color: #ffa500;
    cursor: not-allowed;
}

.date-box.available .book-now {
    background-color: #4CAF50;
    cursor: pointer;
}

.date-box.disabled .book-now {
    background-color: #cccccc;
    cursor: not-allowed;
}

.book-now {
    padding: 5px 10px;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 12px;
    margin-top: 5px;
}

.book-now:disabled {
    opacity: 0.7;
}



/* Adjust main content to prevent it from hiding behind fixed header */
main {
    margin-top: 80px; /* Adjust this value based on your header height */
}

/* Adjust container positioning */
.container {
    margin-top: 20px; /* Reduced from previous value since we added margin to main */
}

/* Update welcome-message section to prevent overlap */
.welcome-message {
    padding-top: 20px;
}

        /* Header and Navigation Styles */
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

        .nav-items {
            display: flex;
            gap: 30px;
            margin-right: 80px;
            position: relative;
            top: -4px;
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

        .dropdown-content a:hover {
            background-color: #87CEFA;
            color: black;
        }

        .dropdown.show .dropdown-content {
            display: block;
        }

        .mobile-only {
            display: none;
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

            .navbar-brand {
                position: relative;
                left: 0;
                padding-left: 0;
                top: 0;
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

/* Update the body background style */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.date-box.past {
    opacity: 0.7;
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.date-box.past .status-badge {
    background-color: #999;
}
</style>
<body>
    <!-- Fixed Header with Navigation -->
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
                <!-- Mobile-only links -->
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

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Left Panel with Calendar -->
            <div class="left-panel">
                <div class="calendar">
                    <div class="calendar-header">
                        <button id="prevMonth">&lt;</button>
                        <h2 id="currentMonth">January 2025</h2>
                        <button id="nextMonth">&gt;</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Calendar will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Right Panel with Schedule Form -->
            <div class="right-panel">
                <h2>Set Schedule</h2>
                <form id="scheduleForm" method="POST" action="save_schedule.php">
                    <div class="form-group">
                        <label for="scheduleType">Schedule Type</label>
                        <select id="scheduleType" name="scheduleType" required>
                            <option value="">Select Schedule Type</option>
                            <option value="bulk">Bulk Schedule</option>
                            <option value="custom">Custom Bulk Schedule</option>
                        </select>
                    </div>

                    <!-- Bulk Schedule Options -->
                    <div id="bulkSchedule" class="schedule-type-content">
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="startDate" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" name="endDate"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <!-- Custom Schedule Options -->
                    <div id="customSchedule" class="schedule-type-content">
                        <div class="form-group">
                            <label for="scheduleMonth">Select Month</label>
                            <input type="month" id="scheduleMonth" name="scheduleMonth" 
                                   min="<?php echo date('Y-m'); ?>" 
                                   value="<?php echo date('Y-m'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Select Days</label>
                            <div class="weekday-selector">
                                <input type="checkbox" id="sunday" name="weekdays[]" value="0" class="weekday-checkbox">
                                <label for="sunday" class="weekday-label">Su</label>

                                <input type="checkbox" id="monday" name="weekdays[]" value="1" class="weekday-checkbox">
                                <label for="monday" class="weekday-label">M</label>

                                <input type="checkbox" id="tuesday" name="weekdays[]" value="2" class="weekday-checkbox">
                                <label for="tuesday" class="weekday-label">T</label>

                                <input type="checkbox" id="wednesday" name="weekdays[]" value="3" class="weekday-checkbox">
                                <label for="wednesday" class="weekday-label">W</label>

                                <input type="checkbox" id="thursday" name="weekdays[]" value="4" class="weekday-checkbox">
                                <label for="thursday" class="weekday-label">Th</label>

                                <input type="checkbox" id="friday" name="weekdays[]" value="5" class="weekday-checkbox">
                                <label for="friday" class="weekday-label">F</label>

                                <input type="checkbox" id="saturday" name="weekdays[]" value="6" class="weekday-checkbox">
                                <label for="saturday" class="weekday-label">S</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Initialize calendar data
        const schedules = <?php echo json_encode($schedules); ?>;
        
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Set to start of day for comparison
        
        function generateCalendar(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startingDay = firstDay.getDay();
            const monthLength = lastDay.getDate();
            
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            // Add day headers
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-header-cell';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });

            // Add blank cells for days before the first of the month
            for (let i = 0; i < startingDay; i++) {
                const blankDay = document.createElement('div');
                blankDay.className = 'date-box empty';
                calendarGrid.appendChild(blankDay);
            }

            // Add days of the month
            for (let day = 1; day <= monthLength; day++) {
                const date = new Date(year, month, day);
                const dateString = date.toISOString().split('T')[0];
                
                // Check if date is in the past
                const isPastDate = date < today;
                let status = schedules[dateString];
                
                // Force past dates to be unavailable
                if (isPastDate) {
                    status = 'Unavailable';
                } else if (!status) {
                    status = 'Unavailable';
                }

                const dayBox = document.createElement('div');
                dayBox.className = `date-box ${status.toLowerCase()} ${isPastDate ? 'past' : ''}`;
                
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = day;
                
                const statusBadge = document.createElement('div');
                statusBadge.className = `status-badge ${status.toLowerCase()}`;
                statusBadge.textContent = status;

                dayBox.appendChild(dayNumber);
                dayBox.appendChild(statusBadge);
                calendarGrid.appendChild(dayBox);
            }
        }

        // Initialize calendar
        const initialDate = new Date();
        let currentMonth = initialDate.getMonth();
        let currentYear = initialDate.getFullYear();

        // Update calendar header
        function updateCalendarHeader() {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonth').textContent = `${months[currentMonth]} ${currentYear}`;
        }

        // Generate initial calendar
        updateCalendarHeader();
        generateCalendar(currentYear, currentMonth);

        // Handle month navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            // Fetch schedules for the new month
            const monthStr = String(currentMonth + 1).padStart(2, '0');
            fetch(`get_schedules.php?year=${currentYear}&month=${monthStr}`)
                .then(response => response.json())
                .then(newSchedules => {
                    Object.assign(schedules, newSchedules);
                    updateCalendarHeader();
                    generateCalendar(currentYear, currentMonth);
                });
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            // Fetch schedules for the new month
            const monthStr = String(currentMonth + 1).padStart(2, '0');
            fetch(`get_schedules.php?year=${currentYear}&month=${monthStr}`)
                .then(response => response.json())
                .then(newSchedules => {
                    Object.assign(schedules, newSchedules);
                    updateCalendarHeader();
                    generateCalendar(currentYear, currentMonth);
                });
        });

        // Handle schedule type selection
        document.getElementById('scheduleType').addEventListener('change', function() {
            document.querySelectorAll('.schedule-type-content').forEach(content => {
                content.classList.remove('active');
                
                // Remove required attribute from all inputs in this content
                content.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            });

            const selectedType = this.value;
            if (selectedType) {
                const selectedContent = document.getElementById(selectedType + 'Schedule');
                selectedContent.classList.add('active');
                
                // Add required attribute to visible inputs based on type
                if (selectedType === 'bulk') {
                    selectedContent.querySelector('#startDate').required = true;
                    selectedContent.querySelector('#endDate').required = true;
                } else if (selectedType === 'custom') {
                    selectedContent.querySelector('#scheduleMonth').required = true;
                }
            }
        });

        // Handle form submission
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const scheduleType = formData.get('scheduleType');
            
            if (!scheduleType) {
                alert('Please select a schedule type.');
                return;
            }
            
            // Validate form based on schedule type
            if (scheduleType === 'bulk') {
                const startDate = formData.get('startDate');
                const endDate = formData.get('endDate');
                
                if (!startDate || !endDate) {
                    alert('Please select both start and end dates for bulk scheduling.');
                    return;
                }
                
                if (new Date(startDate) > new Date(endDate)) {
                    alert('End date must be after start date.');
                    return;
                }
            } else if (scheduleType === 'custom') {
                const scheduleMonth = formData.get('scheduleMonth');
                const weekdays = formData.getAll('weekdays[]');
                
                if (!scheduleMonth) {
                    alert('Please select a month for custom scheduling.');
                    return;
                }
                
                if (weekdays.length === 0) {
                    alert('Please select at least one day of the week for custom scheduling.');
                    return;
                }
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;
            
            // Submit form data using fetch
            fetch('save_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Schedule saved successfully!');
                    // Fetch updated schedules for current month
                    const monthStr = String(currentMonth + 1).padStart(2, '0');
                    fetch(`get_schedules.php?year=${currentYear}&month=${monthStr}`)
                        .then(response => response.json())
                        .then(newSchedules => {
                            Object.assign(schedules, newSchedules);
                            generateCalendar(currentYear, currentMonth);
                        });
                } else {
                    alert(data.message || 'Failed to save schedule. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Restore button state
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });

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
