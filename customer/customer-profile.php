<?php

session_start();

// Add this function at the top of the file after session_start()
function ucwordsCustom($str) {
    return ucwords(strtolower($str));
}

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

// Database connection
$host = "localhost";
$dbname = "db_booking_system";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM customer_account WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);  
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user data was found
    if (!$user) {
        // No user found with that username
        $_SESSION = array(); // Clear all session variables
        session_destroy();
        header("Location: customer-loginpage.php?error=invalid_session");
        exit();
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Now we can safely use $user since we know it exists
$first_name = htmlspecialchars($user['first_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style2.css">
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

/* Update the body background style */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.alert {
    padding: 15px;
    margin: 20px 50px;  /* Add margin to align with the rest of the content */
    border: 1px solid transparent;
    border-radius: 4px;
    opacity: 1;
    transition: opacity 0.5s ease-in-out;
    text-align: center;  /* Center the message text */
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.fade-out {
    opacity: 0;
}

.password-container.half-width {
    width: 602px;  /* Reduced from 50% to 40% to make it smaller */
}

/* For mobile responsiveness */
@media (max-width: 768px) {
    .password-container.half-width {
        width: 100%;  /* Full width on mobile */
    }
}

/* Password input container styles */
.password-container {
    position: relative;
    width: 100%;
}

.password-container input {
    width: 100%;
    padding-right: 40px; /* Space only for eye icon */
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

/* Password validation styles */
.password-container input.valid-password {
    border-color: #28a745 !important;
    background-color: #f8fff8 !important;
}

.password-container input.invalid-password {
    border-color: #dc3545 !important;
    background-color: #fff8f8 !important;
}

/* Optional: Add some spacing between the icons */
.password-validation-icon i {
    margin-right: 2px;
}

.password-container {
    position: relative;
    width: 100%;
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
        <?php
        // Display success/error messages
        if (isset($_SESSION['update_message'])) {
            $message = $_SESSION['update_message'];
            echo '<div id="alertMessage" class="alert alert-' . 
                ($message['type'] === 'success' ? 'success' : 'danger') . 
                '">' . htmlspecialchars($message['text']) . '</div>';
            
            // Remove the message from session after displaying it
            unset($_SESSION['update_message']);
        }
        ?>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We're glad to have you here. Let's get started!</p>
        </section>
    </main>

    <div class="main-content">

    <div class="tabs">
        <a href="#account" id="account-tab" class="active" aria-controls="account">Account</a>
        <a href="#security" id="security-tab" aria-controls="security">Security</a>
    </div>

    <div id="account" class="tab-content active">
    <h3>Account Details</h3>
    <hr>
    <form action="update-account.php" method="POST" class="account-form">
    <!-- Horizontal alignment of First Name and Last Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" 
                value="<?php echo ucwordsCustom($user['first_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" 
                value="<?php echo ucwordsCustom($user['last_name']); ?>" required>
        </div>
    </div>

    <!-- Horizontal alignment of Contact Number and Email -->
    <div class="form-row">
        <div class="form-group">
            <label for="contactNumber">Contact Number</label>
            <input type="tel" id="contactNumber" name="contactNumber" pattern="[0-9]{11}" placeholder="09123456789" 
                value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="example@email.com" 
                value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
    </div>

    <h4 style="color: #D3D3D3;">Address</h4>
    <!-- Horizontal alignment of Street and Barangay -->
    <div class="form-row">
        <div class="form-group">
            <label for="street">Street</label>
            <input type="text" id="street" name="street" 
                value="<?php echo ucwordsCustom($user['street']); ?>" required>
        </div>
        <div class="form-group">
            <label for="barangay">Barangay</label>
            <input type="text" id="barangay" name="barangay" 
                value="<?php echo ucwordsCustom($user['barangay']); ?>" required>
        </div>
    </div>

    <!-- Horizontal alignment of Municipality and Province -->
    <div class="form-row">
        <div class="form-group">
            <label for="municipality">Municipality</label>
            <input type="text" id="municipality" name="municipality" 
                value="<?php echo ucwordsCustom($user['municipality']); ?>" required>
        </div>
        <div class="form-group">
            <label for="province">Province</label>
            <select id="province" name="province" required>
                <option value="" disabled>Select Province</option>
                <?php
                $provinces = [
                    "Abra",
                    "Agusan del Norte",
                    "Agusan del Sur",
                    "Aklan",
                    "Albay",
                    "Antique",
                    "Apayao",
                    "Aurora",
                    "Basilan",
                    "Bataan",
                    "Batanes",
                    "Batangas",
                    "Benguet",
                    "Biliran",
                    "Bohol",
                    "Bukidnon",
                    "Bulacan",
                    "Cagayan",
                    "Camarines Norte",
                    "Camarines Sur",
                    "Camiguin",
                    "Capiz",
                    "Catanduanes",
                    "Cavite",
                    "Cebu",
                    "Cotabato",
                    "Davao de Oro",
                    "Davao del Norte",
                    "Davao del Sur",
                    "Davao Occidental",
                    "Davao Oriental",
                    "Dinagat Islands",
                    "Eastern Samar",
                    "Guimaras",
                    "Ifugao",
                    "Ilocos Norte",
                    "Ilocos Sur",
                    "Iloilo",
                    "Isabela",
                    "Kalinga",
                    "La Union",
                    "Laguna",
                    "Lanao del Norte",
                    "Lanao del Sur",
                    "Leyte",
                    "Maguindanao",
                    "Marinduque",
                    "Masbate",
                    "Metro Manila",
                    "Misamis Occidental",
                    "Misamis Oriental",
                    "Mountain Province",
                    "Negros Occidental",
                    "Negros Oriental",
                    "Northern Samar",
                    "Nueva Ecija",
                    "Nueva Vizcaya",
                    "Occidental Mindoro",
                    "Oriental Mindoro",
                    "Palawan",
                    "Pampanga",
                    "Pangasinan",
                    "Quezon",
                    "Quirino",
                    "Rizal",
                    "Romblon",
                    "Samar",
                    "Sarangani",
                    "Siquijor",
                    "Sorsogon",
                    "South Cotabato",
                    "Southern Leyte",
                    "Sultan Kudarat",
                    "Sulu",
                    "Surigao del Norte",
                    "Surigao del Sur",
                    "Tarlac",
                    "Tawi-Tawi",
                    "Zambales",
                    "Zamboanga del Norte",
                    "Zamboanga del Sur",
                    "Zamboanga Sibugay"
                ];
                foreach ($provinces as $p) {
                    $selected = (strtolower($user['province']) == strtolower($p)) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars(strtolower($p)) . '" ' . $selected . '>' 
                        . htmlspecialchars($p) . '</option>';
                }
                ?>
            </select>
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
            <div class="password-container half-width">
                <input type="password" 
                       id="currentPassword" 
                       name="currentPassword" 
                       required 
                       onkeyup="validateCurrentPassword(this)">
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('currentPassword')"></i>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <div class="password-container">
                    <input type="password" id="newPassword" name="newPassword" required>
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
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword')"></i>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">Update Password</button>
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

    // Function to handle alert messages
    document.addEventListener('DOMContentLoaded', function() {
        const alertMessage = document.getElementById('alertMessage');
        if (alertMessage) {
            // Add fade out effect after 2 seconds
            setTimeout(function() {
                alertMessage.classList.add('fade-out');
                // Remove the element after fade out animation completes
                setTimeout(function() {
                    alertMessage.remove();
                }, 500);
            }, 2000);
        }
    });

    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const icon = passwordInput.nextElementSibling;
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    let validateTimeout;

    function validateCurrentPassword(input) {
        clearTimeout(validateTimeout);
        
        input.classList.remove('valid-password', 'invalid-password');
        
        if (!input.value) {
            return;
        }

        validateTimeout = setTimeout(() => {
            const formData = new FormData();
            formData.append('current_password', input.value);

            fetch('validate-password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                if (result === 'valid') {
                    input.classList.add('valid-password');
                    input.classList.remove('invalid-password');
                } else {
                    input.classList.add('invalid-password');
                    input.classList.remove('valid-password');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }, 300);
    }

    document.querySelector('.security-form').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Check if passwords match
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match!');
            return false;
        }

        // Check password strength
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(newPassword)) {
            e.preventDefault();
            alert('Password must contain at least 8 characters, including uppercase and lowercase letters, numbers, and special characters.');
            return false;
        }
    });

    document.getElementById('newPassword').addEventListener('input', function() {
        const password = this.value;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChar = /[@$!%*?&]/.test(password);
        const isLongEnough = password.length >= 8;

        const requirements = document.querySelectorAll('.password-requirements li');
        
        requirements[0].style.color = isLongEnough ? 'green' : 'red';
        requirements[1].style.color = hasUpperCase ? 'green' : 'red';
        requirements[2].style.color = hasLowerCase ? 'green' : 'red';
        requirements[3].style.color = hasNumbers ? 'green' : 'red';
        requirements[4].style.color = hasSpecialChar ? 'green' : 'red';

        // Add color indicator for the password field
        if (isLongEnough && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar) {
            this.classList.add('valid-password');
            this.classList.remove('invalid-password');
        } else {
            this.classList.add('invalid-password');
            this.classList.remove('valid-password');
        }
    });

    // Add validation for confirm password field
    document.getElementById('confirmPassword').addEventListener('input', function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = this.value;

        if (confirmPassword === newPassword && confirmPassword !== '') {
            this.classList.add('valid-password');
            this.classList.remove('invalid-password');
        } else {
            this.classList.add('invalid-password');
            this.classList.remove('valid-password');
        }
    });

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