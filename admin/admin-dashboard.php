<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php");
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']);

// Database connection
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12777569';
$username = 'sql12777569';
$password = 'QlgHSeuU1n';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get total appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM booking_report");
    $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get approved appointments
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM booking_report WHERE status = 'Approved'");
    $approvedAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
    
    // Get declined appointments
    $stmt = $pdo->query("SELECT COUNT(*) as declined FROM booking_report WHERE status = 'Declined'");
    $declinedAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['declined'];
    
    // Get cancelled appointments
    $stmt = $pdo->query("SELECT COUNT(*) as cancelled FROM booking_report WHERE status = 'Cancelled'");
    $cancelledAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['cancelled'];
    
    // Get completed appointments
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM booking_report WHERE status = 'Approved' AND remarks = 'Complete'");
    $completedAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
    
    // Get total number of active entertainers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM entertainer_account WHERE status = 'Active'");
    $totalEntertainers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get top entertainers by booking count with their details
    $stmt = $pdo->query("
        SELECT 
            ea.first_name,
            ea.last_name,
            ea.title,
            COUNT(br.book_id) as booking_count
        FROM entertainer_account ea
        LEFT JOIN booking_report br ON FIND_IN_SET(CONCAT(ea.first_name, ' ', ea.last_name), br.entertainer_name)
            AND br.status = 'Approved'
        WHERE ea.status = 'Active'
        GROUP BY ea.entertainer_id, ea.first_name, ea.last_name, ea.title
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $topEntertainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 1em;
        }

        /* Color schemes for different stats */
        .total-appointments {
            border-left: 4px solid #4CAF50;
        }
        .total-appointments .stat-icon {
            color: #4CAF50;
        }

        .approved-appointments {
            border-left: 4px solid #2196F3;
        }
        .approved-appointments .stat-icon {
            color: #2196F3;
        }

        .declined-appointments {
            border-left: 4px solid #f44336;
        }
        .declined-appointments .stat-icon {
            color: #f44336;
        }

        .cancelled-appointments {
            border-left: 4px solid #ff9800;
        }
        .cancelled-appointments .stat-icon {
            color: #ff9800;
        }

        .completed-appointments {
            border-left: 4px solid #9c27b0;
        }
        .completed-appointments .stat-icon {
            color: #9c27b0;
        }

        .entertainer-stats {
            border-left: 4px solid #673ab7;
        }
        .entertainer-stats .stat-icon {
            color: #673ab7;
        }

        .top-entertainers {
            border-left: 4px solid #2196F3;
            grid-column: 1 / -1;
            max-width: 500px;
            justify-self: start;
            margin: 20px;
            background: linear-gradient(to right, #ffffff, #f8fbff);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .top-entertainers .card-header {
            background: linear-gradient(135deg, #2196F3, #1976d2);
            padding: 12px 15px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-entertainers .card-header .stat-icon {
            color: white;
            font-size: 1.5em;
            margin: 0;
        }

        .top-entertainers .card-header .stat-label {
            color: white;
            font-size: 1.2em;
            font-weight: 500;
            margin: 0;
        }

        .top-list {
            margin-top: 15px;
            text-align: left;
            padding: 0 15px 15px;
        }

        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            margin: 8px 0;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .top-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .entertainer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .entertainer-info .name {
            font-weight: 500;
            color: #333;
            font-size: 1em;
            letter-spacing: 0.3px;
        }

        .entertainer-info .title {
            color: #666;
            font-size: 0.85em;
            margin-top: 2px;
            color: #2196F3;
            font-weight: 500;
        }

        .top-item .count {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.1);
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 30px 0;
            font-style: italic;
            background: #f8fbff;
            border-radius: 8px;
            margin: 10px 0;
        }

        /* Header and Navigation Styles */
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
            margin-right: 20px;
            margin-left: auto;
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

        .nav-items a:last-child {
            border-bottom: none;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .dropbtn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 4px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #333;
            min-width: 160px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border-radius: 4px;
            z-index: 1000;
            margin-top: 4px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .dropdown-content.show {
            display: block;
            opacity: 1;
            visibility: visible;
        }

        .dropdown-content a {
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background-color: #87CEFA;
            color: black;
            text-decoration: none;
        }

        /* Remove underline from dropdown links */
        .dropdown-content a,
        .dropdown-content a:hover,
        .dropdown-content a:focus,
        .dropdown-content a:active {
            text-decoration: none !important;
        }

        /* Hide mobile-only items in desktop view */
        .mobile-only {
            display: none !important;
        }

        /* Hide menu toggle in desktop view */
        .menu-toggle {
            display: none;
        }

        /* Navigation items styling for desktop */
        .nav-items {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-items a:hover {
            background-color: #87CEFA;
            color: black;
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

        @media (max-width: 480px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
        }

        .entertainer-info {
            display: flex;
            flex-direction: column;
        }

        .entertainer-info .name {
            font-weight: 500;
            color: #333;
            font-size: 1em;
        }

        .entertainer-info .title {
            color: #666;
            font-size: 0.85em;
            margin-top: 2px;
        }

        .top-item .count {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 20px 0;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .entertainer-info .name {
                font-size: 0.9em;
            }
            
            .entertainer-info .title {
                font-size: 0.75em;
            }
            
            .top-item .count {
                font-size: 0.75em;
                padding: 3px 6px;
            }
        }

        @media (max-width: 1200px) {
            .dashboard-stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .content {
            padding-top: 80px;
            margin: 0 auto;
            max-width: 1400px;
            padding-left: 20px;
            padding-right: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .top-entertainers {
                max-width: none;
                margin: 15px 0;
            }

            .content {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
    </style>
</head>
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
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown(event)">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdownContent">
                    <a href="admin-profile.php">View Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="content">
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>Here's an overview of your appointment statistics.</p>
        </section>

        <div class="dashboard-stats">
            <div class="stat-card total-appointments">
                <i class="fas fa-calendar-check stat-icon"></i>
                <div class="stat-number"><?php echo $totalAppointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>

            <div class="stat-card approved-appointments">
                <i class="fas fa-thumbs-up stat-icon"></i>
                <div class="stat-number"><?php echo $approvedAppointments; ?></div>
                <div class="stat-label">Approved Appointments</div>
            </div>

            <div class="stat-card declined-appointments">
                <i class="fas fa-thumbs-down stat-icon"></i>
                <div class="stat-number"><?php echo $declinedAppointments; ?></div>
                <div class="stat-label">Declined Appointments</div>
            </div>

            <div class="stat-card cancelled-appointments">
                <i class="fas fa-ban stat-icon"></i>
                <div class="stat-number"><?php echo $cancelledAppointments; ?></div>
                <div class="stat-label">Cancelled Appointments</div>
            </div>

            <div class="stat-card completed-appointments">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-number"><?php echo $completedAppointments; ?></div>
                <div class="stat-label">Completed Appointments</div>
            </div>

            <div class="stat-card entertainer-stats">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-number"><?php echo $totalEntertainers; ?></div>
                <div class="stat-label">Total Entertainers</div>
            </div>

            <div class="stat-card top-entertainers">
                <div class="card-header">
                    <i class="fas fa-star stat-icon"></i>
                    <div class="stat-label">Top Entertainers</div>
                </div>
                <div class="top-list">
                    <?php if (!empty($topEntertainers)): ?>
                        <?php foreach ($topEntertainers as $entertainer): ?>
                            <div class="top-item">
                                <div class="entertainer-info">
                                    <span class="name">
                                        <?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']); ?>
                                    </span>
                                    <span class="title"><?php echo htmlspecialchars($entertainer['title']); ?></span>
                                </div>
                                <span class="count"><?php echo $entertainer['booking_count']; ?> bookings</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">No booking data available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        function toggleMenu() {
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            navItems.classList.toggle('active');
            menuToggle.classList.toggle('active');
        }

        // Toggle dropdown menu for profile
        function toggleDropdown(event) {
            event.preventDefault();
            event.stopPropagation();
            const dropdownContent = document.getElementById('dropdownContent');
            dropdownContent.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // Close mobile menu when clicking outside
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (navItems.classList.contains('active') && 
                !event.target.closest('.nav-items') && 
                !event.target.closest('.menu-toggle')) {
                navItems.classList.remove('active');
                menuToggle.classList.remove('active');
            }

            // Close dropdown when clicking outside
            if (!event.target.matches('.dropbtn') && 
                !event.target.matches('.dropbtn img') && 
                !event.target.closest('.dropdown-content')) {
                const dropdowns = document.getElementsByClassName('dropdown-content');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });
    </script>
</body>
</html>
