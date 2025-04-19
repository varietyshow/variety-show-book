<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Database configuration
$host = 'localhost'; // replace with your host
$db = 'db_booking_system'; // your database name
$user = 'root'; // your database username
$pass = ''; // your database password
$charset = 'utf8mb4';

// Set up a DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Initially fetch all bookings with any of these statuses
    $stmt = $pdo->prepare("
        SELECT * FROM booking_report 
        WHERE status IN ('Approved', 'Declined', 'Cancelled')
        ORDER BY date_schedule DESC
    ");
    $stmt->execute(); 
    
    // Fetch all results
    $bookings = $stmt->fetchAll();

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
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
        }

        .content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-left: 30px;
            padding: 20px;
            padding-top: 100px;
            min-height: 100vh;
        }

        /* Show scrollbar when content overflows */
        .content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        /* Schedule List Styles */
        .schedule-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
        }

        .left-side {
            margin-right: auto;
        }

        #status-select {
            padding: 10px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .schedule-header .refresh-btn {
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
            margin-left: 10px;
        }

        .schedule-header .refresh-btn:hover {
            background-color: #f0fff0;
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
                padding: 10px;
                padding-top: 80px;
            }

            .schedule-container {
                width: 100%;
                padding: 10px;
                margin: 0;
                box-shadow: none;
            }

            .schedule-header {
                flex-wrap: nowrap;
            }

            #status-select {
                width: 150px;
                min-width: 0;
            }

            /* Make table responsive */
            .schedule-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .schedule-table thead {
                display: none; /* Hide table headers on mobile */
            }

            .schedule-table tbody tr {
                display: block;
                margin-bottom: 15px;
                background: #fff;
                padding: 10px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .schedule-table tbody td {
                display: block;
                text-align: left;
                padding: 8px 5px;
                border: none;
                position: relative;
                padding-left: 130px;
                white-space: normal;
                min-height: 40px;
            }

            .schedule-table tbody td:before {
                content: attr(data-label);
                position: absolute;
                left: 5px;
                width: 120px;
                font-weight: bold;
                color: #666;
            }

            .button-group {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                justify-content: flex-start;
            }

            .button-group button {
                flex: 1;
                min-width: 120px;
                margin: 0;
            }

            .pagination {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }

            .pagination select {
                width: 100%;
                max-width: 200px;
            }

            .pagination-controls {
                width: 100%;
                justify-content: center;
                gap: 10px;
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

        .schedule-table td[colspan] {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }

        /* Modal Styles */
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: scale(0.7);
            opacity: 0;
            animation: modalOpen 0.3s forwards;
        }

        @keyframes modalOpen {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-content h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 1.5em;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .modal-content p {
            color: #666;
            line-height: 1.6;
            margin: 0;
            font-size: 1.1em;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            color: #333;
            background-color: #f0f0f0;
        }

        /* View Reason Button Style */
        .view-reason-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-reason-btn:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .view-reason-btn:active {
            transform: translateY(0);
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

    <main>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>
    </main>

    <div class="content">
        <div class="schedule-container">
            <h2>Appointments</h2>
            <div class="schedule-header">
                <div class="left-side">
                    <select id="status-select">
                        <option value="">Select Status</option>
                        <option value="Approved">Approved</option>
                        <option value="Declined">Declined</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <button class="refresh-btn" onclick="refreshTable()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time Start</th>
                        <th>Time End</th>
                        <th>Customer Name</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Reason</th> <!-- New column added -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body">
                    <?php if ($bookings): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td data-label="Date"><?php echo date('M. j, Y', strtotime($booking['date_schedule'])); ?></td>
                                <td data-label="Time Start"><?php echo date('h:i A', strtotime($booking['time_start'])); ?></td>
                                <td data-label="Time End"><?php echo date('h:i A', strtotime($booking['time_end'])); ?></td>
                                <td data-label="Customer"><?php echo ucwords(strtolower($booking['first_name'] . ' ' . $booking['last_name'])); ?></td>
                                <td data-label="Venue"><?php echo ucwords(strtolower($booking['street'] . ', ' . $booking['barangay'] . ', ' . $booking['municipality'] . ', ' . $booking['province'])); ?></td>
                                <td data-label="Status">
                                    <?php 
                                    $status = $booking['status'];
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
                                    echo "<span style='$statusColor'>" . htmlspecialchars($status) . "</span>";
                                ?>
                                </td>
                                <td data-label="Remarks">
                                    <?php 
                                    $status = strtolower($booking['status']);
                                    switch ($status) {
                                        case 'approved':
                                            echo isset($booking['remarks']) ? htmlspecialchars($booking['remarks']) : 'Event Pending';
                                            break;
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
                                    ?>
                                </td>
                                <td data-label="Reason">
                                    <?php if (!empty($booking['reason'])): ?>
                                        <button class="view-reason-btn" onclick="showReason('<?php echo htmlspecialchars($booking['reason'], ENT_QUOTES); ?>')">
                                            View Reason
                                        </button>
                                    <?php else: ?>
                                        No reason
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action">
                                    <button class="view-details-btn" onclick="viewDetails(<?php echo $booking['book_id']; ?>)" style="
                                        padding: 6px 12px;
                                        background-color: #4CAF50;
                                        color: white;
                                        border: none;
                                        border-radius: 4px;
                                        cursor: pointer;
                                        font-size: 14px;
                                    ">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No appointments found.</td> <!-- Updated colspan -->
                        </tr>
                    <?php endif; ?>
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
                    <button disabled aria-label="Previous Page">◀</button>
                    <span id="pagination-info">0-0 of 0</span>
                    <button disabled aria-label="Next Page">▶</button>
                </div>
            </div>
        </div>
    </div>

    <div id="reasonModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Manager Remarks</h3>
            <p id="modalReasonText"></p>
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

        function viewDetails(bookId) {
            window.location.href = 'entertainer-view-appointment.php?id=' + bookId;
        }

        document.getElementById('status-select').addEventListener('change', function() {
            const selectedStatus = this.value;
            const tableBody = document.getElementById('schedule-table-body');
            const rows = tableBody.getElementsByTagName('tr');
            let visibleCount = 0;

            // Loop through all rows
            Array.from(rows).forEach(row => {
                const statusCell = row.cells[5]; // Status is in the 6th column (index 5)
                if (statusCell) {
                    if (selectedStatus === '' || statusCell.textContent === selectedStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });

            // Show "no records" message if no matching records
            if (visibleCount === 0) {
                const noRecordsRow = document.createElement('tr');
                noRecordsRow.innerHTML = `<td colspan="9">No ${selectedStatus ? selectedStatus.toLowerCase() : ''} appointments found.</td>`;
                
                // Remove any existing "no records" message
                Array.from(rows).forEach(row => {
                    if (row.cells[0].getAttribute('colspan') === '9') {
                        row.remove();
                    }
                });
                
                tableBody.appendChild(noRecordsRow);
            }
        });

        // Add refresh functionality
        document.querySelector('.refresh-btn').addEventListener('click', function() {
            location.reload();
        });

        // Modal functionality
        const modal = document.getElementById('reasonModal');
        const modalText = document.getElementById('modalReasonText');
        const closeModal = document.querySelector('.close-modal');

        function showReason(reason) {
            modalText.textContent = reason || 'No reason provided';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        }

        closeModal.onclick = function() {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        }

        // Add escape key support for closing modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

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