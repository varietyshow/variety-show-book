<?php
// Start the session to maintain user state
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

// Retrieve and sanitize the user's first name from session
$first_name = htmlspecialchars($_SESSION['first_name']);

// Database connection configuration
$servername = "sql12.freesqldatabase.com";
$username = "sql12775634";
$password = "kPZFb8pXsU";
$dbname = "sql12775634";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch all available roles
$rolesQuery = "SELECT DISTINCT role_name FROM roles ORDER BY role_name";
$rolesResult = $conn->query($rolesQuery);

// SQL query to fetch active entertainers with their details
// Joins with uploads table to get video files
// Creates a complete address by concatenating address components
$sql = "SELECT ea.*, 
    CONCAT(
        COALESCE(ea.street, ''), 
        CASE WHEN ea.street IS NOT NULL THEN ', ' ELSE '' END,
        COALESCE(ea.barangay, ''),
        CASE WHEN ea.barangay IS NOT NULL THEN ', ' ELSE '' END,
        COALESCE(ea.municipality, ''),
        CASE WHEN ea.municipality IS NOT NULL THEN ', ' ELSE '' END,
        COALESCE(ea.province, '')
    ) as complete_address,
    GROUP_CONCAT(DISTINCT u.filename) as video_files,
    ea.est_price,
    SUBSTRING_INDEX(ea.est_price, ' - ', 1) as min_price,
    SUBSTRING_INDEX(ea.est_price, ' - ', -1) as max_price
    FROM entertainer_account ea
    LEFT JOIN uploads u ON ea.entertainer_id = u.entertainer_id
    WHERE ea.status = 'Active'
    GROUP BY ea.entertainer_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<style>
         body {
            overflow: auto;
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            margin-left: 0;
            padding: 20px;
            padding-top: 10px;
            min-height: 100vh;
            background: transparent;
            gap: 20px;
        }

        /* Show scrollbar when content overflows */
        .content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .content {
            overflow-y: auto;
        }

            /* Schedule List Styles */
        .content {
            display: flex;
            justify-content: flex-start; /* Center horizontally */
            align-items: flex-start; /* Align items at the top */
            margin-left: 0;
            padding: 20px;
            padding-top: 10px; /* Give some space below the header */
            min-height: 100vh;
        }

        .schedule-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%; /* Ensure it takes full width of the parent */
            max-width: 1200px; /* Set a maximum width for larger screens */
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

        /* Add new styles for the entertainer cards */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .entertainer-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }

        .entertainer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card-image {
            width: 100%;
            aspect-ratio: 1;
            height: auto;
            object-fit: cover;
        }

        .card-content {
            padding: 15px;
            flex-grow: 1;
            background-color: #333;
        }

        .card-title {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #fff;
        }

        .card-text {
            margin: 0;
            font-size: 0.9rem;
            color: #fff;
            line-height: 1.6;
        }

        .card-text strong {
            color: #87CEFA;
            display: inline-block;
            width: 80px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                padding: 10px;
                gap: 15px;
            }

            .entertainer-card {
                margin-bottom: 15px;
            }

            .card-image {
                height: auto;
            }

            .card-content {
                padding: 12px;
            }

            .card-title {
                font-size: 1.1rem;
            }

            .card-text {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .cards-container {
                padding: 8px;
                gap: 12px;
            }

            .card-content {
                padding: 10px;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            margin: auto;
            width: 90%;
            max-width: 1200px;
            display: flex;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            padding: 0;
            max-height: 90vh;
        }

        /* Main modal close button */
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            background-color: #ff4444;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 20px;
            transition: all 0.2s ease;
            z-index: 1001;
            padding: 0;
            line-height: 1;
        }

        /* Fullscreen modal close button */
        .fullscreen-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            background-color: #ff4444;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 20px;
            transition: all 0.2s ease;
            z-index: 1001;
            padding: 0;
            line-height: 1;
        }

        .close-modal:hover,
        .fullscreen-close:hover {
            background-color: #ff0000;
            transform: scale(1.1);
        }

        .modal-slideshow {
            flex: 2;
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            overflow: hidden;
        }

        .slideshow-container {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .modal-video {
            max-width: 100%;
            max-height: 90vh;
            width: auto;
            height: auto;
        }

        .prev-slide, .next-slide {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 16px;
            border: none;
            cursor: pointer;
            font-size: 18px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 1002;
        }

        .prev-slide {
            left: 10px;
        }

        .next-slide {
            right: 10px;
        }

        .prev-slide:hover, .next-slide:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .slide-counter {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        .modal-details {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f8f9fa;
            max-height: 90vh;
            min-width: 300px;
            max-width: 400px;
            border-left: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .modal-content {
                flex-direction: column;
                width: 95%;
                max-height: 95vh;
            }

            .modal-slideshow {
                flex: none;
                height: 60vh;
                min-height: 300px;
            }

            .modal-details {
                flex: none;
                max-width: none;
                max-height: 40vh;
                border-left: none;
                border-top: 1px solid #eee;
            }

            .close-modal {
                top: 10px;
                right: 10px;
            }
        }

        .modal-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-text {
            font-size: 1rem;
            color: #4a5568;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .modal-text strong {
            color: #2d3436;
            font-weight: 600;
            display: inline-block;
            width: 100px;
            margin-right: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-details {
                padding: 20px;
                margin: 0;
                border-radius: 0;
            }

            .modal-title {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }

            .details-section {
                padding: 15px;
                margin: 15px 0;
            }

            .social-links {
                flex-wrap: wrap;
                gap: 10px;
            }

            .social-links a {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            #noVideosMessage {
                font-size: 0.9rem;
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 100%;
                margin: 10px auto;
                padding: 10px;
            }

            .modal-slideshow {
                max-height: 40vh;
            }

            .modal-slideshow img {
                max-height: 40vh;
            }

            .modal-slideshow video {
                max-height: 40vh;
            }

            .modal-info {
                padding: 10px 8px;
            }

            .modal-info h2 {
                font-size: 1.2rem;
            }

            .modal-info p {
                font-size: 0.85rem;
                margin-bottom: 6px;
            }

            .social-links {
                justify-content: center;
            }

            .social-links a {
                padding: 6px 10px;
                font-size: 0.85rem;
                flex: 1 1 auto;
                text-align: center;
                min-width: 120px;
            }
        }

        .role-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }

        .role-badge {
            background-color: #87CEFA;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            text-transform: capitalize;
        }

        @media (max-width: 768px) {
            .role-badge {
                font-size: 0.85rem;
                padding: 3px 10px;
            }
        }

        .social-links a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin: 5px;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .social-links a i {
            font-size: 1.2rem;
        }

        .social-links a.facebook {
            background-color: #1877F2;
        }

        .social-links a.instagram {
            background-color: #E4405F;
        }

        .social-links a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .book-now-btn {
            background-color: #28a745;
        }
        
        .book-now-btn:hover {
            background-color: #45a049 !important;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Add styles for role filter */
        .filters {
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .role-filter-container {
            position: relative;
            min-width: 200px;
        }

        .role-filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
        }

        .role-dropdown-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0 5px;
        }

        .role-checkboxes {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            padding: 8px;
        }

        .role-checkboxes.show {
            display: block;
        }

        .role-checkbox {
            display: block;
            padding: 5px;
            cursor: pointer;
        }

        .role-checkbox:hover {
            background: #f5f5f5;
        }

        .price-filter {
            display: flex;
            gap: 10px;
        }

        .price-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .peso-sign {
            position: absolute;
            left: 8px;
            color: #666;
            z-index: 1;
        }

        .price-filter input {
            padding: 8px 8px 8px 25px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 120px;
            font-size: 14px;
        }

        .price-filter input::placeholder {
            color: #999;
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

        .entertainer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            width: 100%;
        }

        @media screen and (max-width: 768px) {
            .content {
                flex-direction: column;
            }

            .role-filter-container {
                width: 100%;
                position: static;
                margin-bottom: 20px;
            }

            .main-content {
                width: 100%;
            }
        }

        /* Fullscreen modal styles */
        .fullscreen-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1002;
        }

        .fullscreen-modal .modal-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
            margin: 0;
            padding: 0;
            border: none;
        }

        .fullscreen-modal img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
        }

        .fullscreen-close {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background-color: #ff4444;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 24px;
            transition: all 0.2s ease;
            z-index: 1003;
        }

        .fullscreen-close:hover {
            background-color: #ff0000;
            transform: scale(1.1);
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
            <p>Browse through our talented entertainers and book your next event!</p>
        </section>

        <section class="filters">
            <div class="filter-container">
                <div class="role-filter-container">
                    <div class="role-filter-header">
                        <span>Select Roles</span>
                        <button type="button" onclick="toggleRoleDropdown()" class="role-dropdown-btn">▼</button>
                    </div>
                    <div id="role-checkboxes" class="role-checkboxes">
                        <?php 
                        // Reset the roles result pointer
                        $rolesResult->data_seek(0);
                        while($role = $rolesResult->fetch_assoc()): 
                        ?>
                            <label class="role-checkbox">
                                <input type="checkbox" value="<?php echo htmlspecialchars($role['role_name']); ?>" onchange="filterEntertainers()">
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="price-filter">
                    <div class="price-input-container">
                        <span class="peso-sign">₱</span>
                        <input type="text" id="min-price" placeholder="Min Price" onkeyup="formatPrice(this)" onchange="filterEntertainers()">
                    </div>
                    <div class="price-input-container">
                        <span class="peso-sign">₱</span>
                        <input type="text" id="max-price" placeholder="Max Price" onkeyup="formatPrice(this)" onchange="filterEntertainers()">
                    </div>
                </div>
            </div>
        </section>

        <div class="content">
            <div class="entertainer-grid">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $profile_pic = !empty($row['profile_image']) 
                            ? "../images/" . $row['profile_image'] 
                            : "../images/default-profile.jpg";
                        $roles = !empty($row['roles']) ? explode(',', $row['roles']) : array();
                        $capitalizedRoles = array_map(function($role) {
                            return ucfirst(strtolower(trim($role)));
                        }, $roles);

                        // Process video files - make sure to handle null/empty cases
                        $videoFiles = [];
                        if (!empty($row['video_files'])) {
                            $fileNames = explode(',', $row['video_files']);
                            foreach ($fileNames as $fileName) {
                                if (!empty(trim($fileName))) {
                                    $videoFiles[] = "../uploads/" . trim($fileName);
                                }
                            }
                        }
                        $videoFilesJson = json_encode($videoFiles);
                        $minPrice = intval(trim($row['min_price']));
                        $maxPrice = intval(trim($row['max_price']));
                        ?>
                        <div class="entertainer-card" data-role="<?php echo htmlspecialchars($row['roles']); ?>" 
                             data-min-price="<?php echo htmlspecialchars($minPrice); ?>" 
                             data-max-price="<?php echo htmlspecialchars($maxPrice); ?>" 
                             onclick='openModal(
                                "<?php echo htmlspecialchars($profile_pic); ?>",
                                "<?php echo htmlspecialchars($row['title']); ?>",
                                "<?php echo htmlspecialchars(ucfirst(strtolower($row['first_name'])) . ' ' . ucfirst(strtolower($row['last_name']))); ?>",
                                "<?php echo !empty($capitalizedRoles) ? htmlspecialchars(implode(', ', $capitalizedRoles)) : ""; ?>",
                                "<?php echo htmlspecialchars($row['complete_address'] ?? 'No address provided'); ?>",
                                "<?php echo htmlspecialchars($row['contact_number'] ?? ''); ?>",
                                "<?php echo htmlspecialchars($row['facebook_acc'] ?? ''); ?>",
                                "<?php echo htmlspecialchars($row['instagram_acc'] ?? ''); ?>",
                                <?php echo $videoFilesJson; ?>,
                                "<?php 
                                    $price_range = explode(' - ', $row['est_price']);
                                    if (count($price_range) == 2) {
                                        $min_price = intval(trim($price_range[0]));
                                        $max_price = intval(trim($price_range[1]));
                                        echo '₱' . number_format($min_price) . ' - ₱' . number_format($max_price);
                                    } else {
                                        echo '₱' . htmlspecialchars($row['est_price']);
                                    }
                                ?>"
                             )'>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                                 alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                 class="card-image"
                                 onerror="this.src='../images/default-profile.jpg'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="card-text">
                                    <strong>Name:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($row['first_name'])) . ' ' . ucfirst(strtolower($row['last_name']))); ?><br>
                                    <?php if (!empty($row['roles'])): ?>
                                        <strong>Roles:</strong> <?php 
                                            echo htmlspecialchars(implode(', ', $capitalizedRoles)); 
                                        ?><br>
                                    <?php endif; ?>
                                    <strong>Est. Price:</strong> <?php 
                                        $price_range = explode(' - ', $row['est_price']);
                                        if (count($price_range) == 2) {
                                            $min_price = intval(trim($price_range[0]));
                                            $max_price = intval(trim($price_range[1]));
                                            echo '₱' . number_format($min_price) . ' - ₱' . number_format($max_price);
                                        } else {
                                            echo '₱' . htmlspecialchars($row['est_price']);
                                        }
                                    ?><br>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No entertainers found.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Replace the existing modal structure -->
        <div id="entertainerModal" class="modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeModal()" aria-label="Close modal">×</button>
                
                <div class="modal-slideshow">
                    <div class="slideshow-container">
                        <div class="video-wrapper">
                            <video id="modalVideo" class="modal-video" controls playsinline>
                                <source src="" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div id="noVideosMessage" style="display: none;">
                            No videos available for this entertainer.
                        </div>
                        <button class="prev-slide" onclick="changeSlide(-1)">❮</button>
                        <button class="next-slide" onclick="changeSlide(1)">❯</button>
                        <div class="slide-counter">
                            <span id="currentSlide">1</span>/<span id="totalSlides">1</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-details">
                    <h2 id="modalTitle" class="modal-title"></h2>
                    
                    <div class="details-section">
                        <p id="modalName" class="modal-text"></p>
                        <div class="role-badges" id="modalRoles">
                            <!-- Roles will be dynamically added here -->
                        </div>
                        <p id="modalAddress" class="modal-text"></p>
                        <p id="modalContact" class="modal-text"></p>
                        <p id="modalPrice" class="modal-text"></p>
                    </div>

                    <div class="social-media-section">
                        <h3>Connect With Me</h3>
                        <div id="modalSocial" class="social-links"></div>
                    </div>

                    <div class="book-now-container" style="text-align: center; margin-top: 70px; padding: 20px 10px 30px;">
                        <button onclick="bookNow()" class="book-now-btn" style="background-color: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; transition: all 0.3s;">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add a new modal for fullscreen view -->
        <div id="fullscreenViewModal" class="fullscreen-modal">
            <span class="fullscreen-close">&times;</span>
            <div class="modal-content">
                <img id="fullscreenImage" src="" alt="Fullscreen view" style="display: none;">
                <video id="fullscreenVideo" controls style="display: none;">
                    <source src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>

    </main>


    <script>
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('show');
        }

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

        // ESC key handler
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        // Modal functions
        let currentVideoIndex = 0;
        let videos = [];

        function capitalizeWords(str) {
            return str.replace(/\b\w/g, function(txt) { return txt.toUpperCase(); });
        }

        function openModal(image, title, name, roles, address, contact, facebook, instagram, videoUrls, price) {
            const modal = document.getElementById('entertainerModal');
            const modalVideo = document.getElementById('modalVideo');
            const noVideosMessage = document.getElementById('noVideosMessage');
            const prevButton = document.querySelector('.prev-slide');
            const nextButton = document.querySelector('.next-slide');
            const slideCounter = document.querySelector('.slide-counter');

            // Store videos globally
            videos = videoUrls;
            currentVideoIndex = 0;

            // Handle video display
            if (videos && videos.length > 0) {
                modalVideo.style.display = 'block';
                noVideosMessage.style.display = 'none';
                prevButton.style.display = videos.length > 1 ? 'block' : 'none';
                nextButton.style.display = videos.length > 1 ? 'block' : 'none';
                slideCounter.style.display = videos.length > 1 ? 'block' : 'none';

                // Store current volume before changing source
                const currentVolume = modalVideo.volume;

                // Update video source and play
                modalVideo.src = videos[currentVideoIndex];
                modalVideo.load();
                
                // Restore volume after loading new source
                modalVideo.volume = currentVolume;
                
                modalVideo.play().catch(function(error) {
                    console.log("Video play failed:", error);
                    noVideosMessage.style.display = 'block';
                    modalVideo.style.display = 'none';
                });

                // Update counter
                document.getElementById('currentSlide').textContent = currentVideoIndex + 1;
                document.getElementById('totalSlides').textContent = videos.length;
            } else {
                modalVideo.style.display = 'none';
                noVideosMessage.style.display = 'block';
                prevButton.style.display = 'none';
                nextButton.style.display = 'none';
                slideCounter.style.display = 'none';
            }

            // Update other modal content with capitalized text
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalName').innerHTML = `<strong>Name:</strong> ${name}`;
            
            // Update roles with badges
            const rolesContainer = document.getElementById('modalRoles');
            rolesContainer.innerHTML = '';
            if (roles) {
                const rolesList = roles.split(',').map(role => role.trim());
                rolesList.forEach(role => {
                    const badge = document.createElement('span');
                    badge.className = 'role-badge';
                    badge.textContent = role;
                    rolesContainer.appendChild(badge);
                });
            }
            
            // Update other details
            document.getElementById('modalAddress').innerHTML = `<strong>Address:</strong> ${address}`;
            document.getElementById('modalContact').innerHTML = `<strong>Contact:</strong> ${contact}`;
            document.getElementById('modalPrice').innerHTML = `<strong>Est. Price:</strong> ${price.includes('Contact') ? price : '₱' + price}`;
            
            // Update social media links
            const socialContainer = document.getElementById('modalSocial');
            socialContainer.innerHTML = '';
            
            if (facebook) {
                const fbLink = document.createElement('a');
                fbLink.href = facebook;
                fbLink.target = '_blank';
                fbLink.className = 'social-link facebook';
                fbLink.innerHTML = '<i class="fab fa-facebook-f"></i>';
                socialContainer.appendChild(fbLink);
            }
            
            if (instagram) {
                const igLink = document.createElement('a');
                igLink.href = instagram;
                igLink.target = '_blank';
                igLink.className = 'social-link instagram';
                igLink.innerHTML = '<i class="fab fa-instagram"></i>';
                socialContainer.appendChild(igLink);
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function changeSlide(direction) {
            if (!videos || videos.length <= 1) return;
            
            const modalVideo = document.getElementById('modalVideo');
            // Store current volume before changing source
            const currentVolume = modalVideo.volume;
            
            currentVideoIndex = (currentVideoIndex + direction + videos.length) % videos.length;
            modalVideo.src = videos[currentVideoIndex];
            modalVideo.load();
            
            // Restore volume after loading new source
            modalVideo.volume = currentVolume;
            
            modalVideo.play().catch(function(error) {
                console.log("Video play failed:", error);
            });
            
            // Update counter
            document.getElementById('currentSlide').textContent = currentVideoIndex + 1;
        }

        function closeModal() {
            const modal = document.getElementById('entertainerModal');
            const fullscreenModal = document.getElementById('fullscreenViewModal');
            
            modal.style.display = 'none';
            if (fullscreenModal) {
                fullscreenModal.style.display = 'none';
            }
            
            // Reset video if it exists
            const video = document.getElementById('fullscreenVideo');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            
            document.body.style.overflow = 'auto';
        }

        function bookNow() {
            // Get the entertainer's name from the modal
            const entertainerName = document.getElementById('modalName').textContent;
            // Redirect to the booking page with the entertainer's name as a parameter
            window.location.href = 'customer-booking.php?entertainer=' + encodeURIComponent(entertainerName);
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Close button click handler
            const closeButtons = document.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', closeModal);
            });

            // Click outside modal handler
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('entertainerModal');
                const fullscreenModal = document.getElementById('fullscreenViewModal');
                
                if (event.target === modal || event.target === fullscreenModal) {
                    closeModal();
                }

                // Dropdown handling
                if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                    const openDropdowns = document.querySelectorAll('.dropdown.show');
                    openDropdowns.forEach(function(dropdown) {
                        dropdown.classList.remove('show');
                    });
                }
            });

            // ESC key handler
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        });

        function formatPrice(input) {
            // Remove any non-digit characters except commas
            let value = input.value.replace(/[^\d,]/g, '');
            
            // Remove all commas
            value = value.replace(/,/g, '');
            
            // Add commas for thousands
            if (value.length > 0) {
                value = Number(value).toLocaleString('en-US');
            }
            
            input.value = value;
        }

        function unformatPrice(value) {
            // Remove peso sign, commas and spaces
            return parseFloat(value.replace(/[₱,\s]/g, '')) || 0;
        }

        function filterEntertainers() {
            const selectedRoles = Array.from(document.querySelectorAll('.role-checkbox input:checked'))
                                     .map(checkbox => checkbox.value);
            const minPriceFilter = unformatPrice(document.getElementById('min-price').value);
            const maxPriceFilter = unformatPrice(document.getElementById('max-price').value) || Infinity;
            
            const entertainers = document.querySelectorAll('.entertainer-card');
            
            entertainers.forEach(entertainer => {
                const entertainerRoles = entertainer.getAttribute('data-role').split(',').map(role => role.trim());
                const entertainerMinPrice = parseFloat(entertainer.getAttribute('data-min-price')) || 0;
                const entertainerMaxPrice = parseFloat(entertainer.getAttribute('data-max-price')) || entertainerMinPrice;
                
                const roleMatch = selectedRoles.length === 0 || 
                                entertainerRoles.some(role => selectedRoles.includes(role));
                
                // Price match if the ranges overlap
                const priceMatch = (
                    // If no max filter, check only against min filter
                    (maxPriceFilter === Infinity && entertainerMinPrice >= minPriceFilter) ||
                    // If both min and max filters set, check for range overlap
                    (entertainerMaxPrice >= minPriceFilter && entertainerMinPrice <= maxPriceFilter)
                );
                
                entertainer.style.display = (roleMatch && priceMatch) ? 'block' : 'none';
            });
        }

        function toggleRoleDropdown() {
            const checkboxes = document.getElementById('role-checkboxes');
            checkboxes.classList.toggle('show');
        }

        // Close the dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.closest('.role-filter-container')) {
                const checkboxes = document.getElementById('role-checkboxes');
                checkboxes.classList.remove('show');
            }
        }
    </script>
</body>
</html>
