<?php
session_start();
include 'db_connect.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Fetch data from the database
$entertainers = [];
$items_per_page = isset($_GET['items-per-page']) ? intval($_GET['items-per-page']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

$sql = "SELECT * FROM entertainer_account LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $entertainers[] = $row;
}
$stmt->close();

// Count total records for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM entertainer_account");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $items_per_page);

// Fetch roles from the database
$roles = [];
$roles_sql = "SELECT * FROM roles";
$roles_result = $conn->query($roles_sql);
while ($role = $roles_result->fetch_assoc()) {
    $roles[] = $role;
}

$conn->close();

// Check for success or error messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $msg_type = $_SESSION['msg_type'];
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['msg_type']);
}

// Add this function at the top of your PHP code
function formatPhoneNumberForDisplay($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // If the number starts with +63, convert it to 0
    if (substr($phone, 0, 2) === '63') {
        $phone = '0' . substr($phone, 2);
    }
    
    // If the number doesn't start with 0, add it
    if (substr($phone, 0, 1) !== '0') {
        $phone = '0' . $phone;
    }
    
    // Format as 09XXXXXXXXX
    return substr($phone, 0, 11);
}

function capitalizeWords($string) {
    // Split the string into words and capitalize each word
    return ucwords(strtolower($string));
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
</head>
<style>
         body {
            overflow: auto; /* Only show scrollbar when necessary */
        }

        .content {
            overflow: hidden; /* Hide scrollbar initially */
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
            justify-content: center; /* Center horizontally */
            align-items: flex-start; /* Align items at the top */
            margin-left: 20px;
            padding: 120px 40px;
            margin-top: 0;
            min-height: calc(100vh - 60px);
            background-color: #f5f5f5;
            transition: 0.3s;
        }

        .schedule-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: -20px;
        }

        .search-container {
            display: flex;
            justify-content: space-between; /* Space between input and buttons */
            align-items: center; /* Align items vertically */
            margin-bottom: 20px;
            width: 100%;
        }

        .search-container input[type="text"] {
            
            margin-right: 20px; /* Space between input and buttons */
            padding: 10px;
            width: 30%;
            margin-left: 10px;
        }

        .button-group {
            display: flex;
            gap: 10px; /* Space between buttons */
        }

        .refresh-btn, .add-btn {
    background: #f0f0f0; /* Same background color */
    border: none; /* Remove default border */
    padding: 10px; /* Add padding for both buttons */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    text-align: center; /* Center the text */
    text-decoration: none; /* Remove underline for the anchor */
    color: black; /* Text color */
    font-size: 18px; /* Font size */
    transition: background-color 0.3s; /* Smooth transition on hover */
}

.add-btn:hover, .refresh-btn:hover {
    background-color: #87CEFA; /* Light blue background on hover */
    color: black; /* Text color on hover */
}

        .button-group button {
            background: #f0f0f0;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
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
                padding: 100px 15px 20px;
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

        .nav-items {
            display: flex;
            align-items: center;
        }

        @media (max-width: 768px) {
            .nav-items {
                display: none;
                position: absolute;
                top: 100%;
                right: 0;
                left: auto;
                width: 180px;
                background-color: #333;
                padding: 8px 0;
                z-index: 1000;
            }

            .nav-items.active {
                display: flex;
                flex-direction: column;
            }

            .nav-items a {
                color: white;
                padding: 10px 15px;
                text-align: left;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                font-size: 14px;
            }

            .nav-items a:last-child {
                border-bottom: none;
                border-radius: 0 0 0 8px;
            }

            /* Hide desktop dropdown on mobile */
            .dropdown {
                display: none;
            }
        }

        .dropdown-content {
            display: none;
        }

        .dropdown-content.show {
            display: block;
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

/* Modal Base */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
}

.modal.active {
    opacity: 1;
    visibility: visible;
}

/* Modal Content */
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
    width: 90%;
    max-width: 600px;
    margin: 20px auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 90vh;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        padding: 15px;
        margin: 10px auto;
        max-height: 85vh;
    }
    
    .modal-content form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-content input[type="text"],
    .modal-content input[type="email"],
    .modal-content input[type="tel"],
    .modal-content input[type="password"],
    .modal-content select {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
    }
    
    .modal-content button {
        width: 100%;
        margin: 5px 0;
    }
}

@media (max-width: 480px) {
    .modal-content {
        width: 98%;
        padding: 10px;
        max-height: 80vh;
    }
    
    .modal-content h2 {
        font-size: 1.2rem;
        margin-bottom: 15px;
    }
}

/* Close Button */
.close-btn {
    position: absolute;
    right: 16px;
    top: 16px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f1f1f1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 16px;
    color: #666;
}

.close-btn:hover {
    background: #e0e0e0;
    transform: rotate(90deg);
}

/* Form Styles */
.form-group {
    margin-bottom: 16px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.3s ease;
    background: #f8f8f8;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #007bff;
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Profile Image Section */
.profile-image-container {
    text-align: center;
    margin-bottom: 20px;
    position: relative;
    display: inline-block;
}

#currentProfileImage {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
    border: 3px solid #f0f0f0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

.profile-image-label {
    display: inline-block;
    padding: 6px 12px;
    background-color: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    margin-top: 8px;
}

.profile-image-label:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Roles Section */
#roles {
    background: #f8f8f8;
    padding: 12px;
    border-radius: 8px;
    max-height: 150px;
    overflow-y: auto;
}

#roles label {
    display: flex;
    align-items: center;
    padding: 6px;
    cursor: pointer;
    transition: background 0.3s ease;
    font-size: 13px;
}

#roles label:hover {
    background: #eef2ff;
}

#roles input[type="checkbox"] {
    margin-right: 8px;
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.submit-button,
.cancel-button {
    flex: 1;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 13px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.submit-button {
    background: #007bff;
    color: white;
}

.submit-button:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.cancel-button {
    background: #f1f1f1;
    color: #333;
}

.cancel-button:hover {
    background: #e0e0e0;
}

/* Modal Title */
.modal-content h2 {
    margin: 0 0 16px 0;
    color: #333;
    font-size: 20px;
    font-weight: 600;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .modal-content {
        padding: 20px;
        margin: 12px;
        width: 95%;
    }

    .form-actions {
        flex-direction: column;
    }

    .submit-button,
    .cancel-button {
        width: 100%;
    }
}

/* Add these new styles for mobile menu */
.menu-toggle {
    display: none; /* Hidden by default on desktop */
    cursor: pointer;
    padding: 10px;
    background: none;
    border: none;
    position: absolute;
    right: 20px; /* Adjust right position */
    top: 50%;
    transform: translateY(-50%);
    z-index: 1001; /* Ensure it's above other elements */
}

.menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 5px 0;
    transition: 0.4s;
}

/* Update the media query for mobile devices */
@media (max-width: 768px) {
    header {
        padding: 10px 20px;
    }

    nav {
        margin-left: auto;
        margin-right: 10px; /* Adjust margin for mobile */
    }

    /* Hide the profile dropdown completely in mobile */
    .dropdown, 
    .dropdown-content {
        display: none !important; /* Use !important to override any other styles */
    }

    .menu-toggle {
        display: block;
        position: relative;
        right: 10px; /* Adjust position for mobile */
        transform: none;
        top: auto;
        margin-left: auto;
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
        right: 0;
        left: auto;
        width: 200px;
        background-color: #333;
        flex-direction: column;
        padding: 0;
        border-radius: 0 0 0 8px;
        box-shadow: -2px 2px 5px rgba(0,0,0,0.2);
    }

    .nav-items.active {
        display: flex;
    }

    .nav-items a {
        color: white;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .nav-items a:hover {
        background-color: #444;
    }

    .nav-items a:last-child {
        border-bottom: none;
        border-radius: 0 0 0 8px;
    }

    /* Hamburger menu animation */
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
    padding-left: 0; /* Remove left padding completely */
    margin-left: 0; /* Remove any margin */
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
    margin-left: auto;
}

/* Navigation link styles */
.nav-items a {
    text-decoration: none;
    color: white;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

/* Navigation link hover effect */
.nav-items a:hover {
    background-color: #87CEFA;
    text-decoration: none;
    color: black;
}

/* Profile dropdown button styles */
.dropbtn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

/* Profile image in dropdown button */
.dropbtn img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-top: 4px;
}

/* Mobile menu toggle button styles */
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

/* Hamburger menu bars */
.menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 5px 0;
    transition: 0.4s;
}

/* Mobile-specific styles */
@media (max-width: 768px) {
    /* Show hamburger menu, hide regular nav */
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

/* Optional: Adjust the header padding if needed */
@media (max-width: 768px) {
    header {
        padding-right: 0;
    }

    nav {
        margin-right: 0;
    }
}

/* Add these styles to your existing CSS */
.form-group {
    margin-bottom: 16px;
    width: 100%;
}

/* Update input styles to ensure proper width in flex container */
.form-group input,
.form-group select {
    width: 100%;
    box-sizing: border-box;
}

/* Ensure proper spacing in mobile view */
@media (max-width: 480px) {
    div[style*="display: flex"] {
        flex-direction: column;
        gap: 8px;
    }
}

/* Add these styles for the address fields */
.address-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.address-grid .form-group {
    margin-bottom: 8px;
}

.address-grid input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

/* Responsive styling for mobile */
@media (max-width: 480px) {
    .address-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}

/* Add these styles for the action buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.action-button {
    background: none;
    border: none;
    padding: 6px;
    cursor: pointer;
    transition: transform 0.2s;
    color: #666;
    font-size: 16px;
}

.edit-button {
    color: #007bff;
}

.delete-button {
    color: #dc3545;
}

.action-button:hover {
    transform: scale(1.1);
}

.action-button i {
    pointer-events: none;
}

/* Optional: Add tooltip styles */
.action-button {
    position: relative;
}

.action-button::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 8px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 12px;
    border-radius: 4px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}

.action-button:hover::after {
    opacity: 1;
    visibility: visible;
}

/* Dropdown styles */
.dropdown {
    position: relative;
    display: inline-block;
    margin-left: 20px;
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
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #333;
    min-width: 160px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 8px;
}

.dropdown-content.show {
    display: block;
}

.dropdown-content a,
.dropdown-content a:hover,
.dropdown-content a:focus,
.dropdown-content a:active {
    text-decoration: none !important;
}

.dropdown-content a {
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    display: block;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: background-color 0.3s;
    font-size: 13px; /* Adjusted font size */
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover {
    background-color: #87CEFA;
    color: black;
}

@media (max-width: 768px) {
    .dropdown {
        display: none;
    }
}

/* Mobile nav styles */
.nav-items.show {
    display: flex;
    flex-direction: column;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #333;
    padding: 10px 0;
    z-index: 1000;
}

.nav-items.show a {
    color: white;
    padding: 12px 20px;
    width: 100%;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-items.show a:last-child {
    border-bottom: none;
}

/* Hide desktop dropdown on mobile */
@media (max-width: 768px) {
    .dropdown {
        display: none;
    }
}

/* Mobile-only elements */
.mobile-only {
    display: none;
}

@media (max-width: 768px) {
    .nav-items {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: #333;
        padding: 10px 0;
        z-index: 1000;
    }

    .nav-items.active {
        display: flex;
        flex-direction: column;
    }

    .nav-items a {
        color: white;
        padding: 6px 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-items.active .mobile-only {
        display: block;
    }
}

/* Profile image styles */
.profile-image {
    margin-left: 20px;
}

.profile-image img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

@media (max-width: 768px) {
    .profile-image {
        display: none;
    }
}

/* Profile image in modal */
.profile-image-container {
    text-align: center;
    margin-bottom: 20px;
}

#currentProfileImage {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
    border: 3px solid #f0f0f0;
}

.profile-image-label {
    display: inline-block;
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.profile-image-label:hover {
    background-color: #0056b3;
}

/* Add notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    z-index: 9999;
    transform: translateY(-100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
}

.notification.success {
    background-color: #28a745;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.notification.error {
    background-color: #dc3545;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
}

/* Add styles for delete button */
.delete-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
}

.delete-btn:hover {
    color: #bd2130;
}

/* Add styles for confirmation modal */
.confirmation-modal {
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
}

.confirmation-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    max-width: 400px;
    width: 90%;
}

.confirmation-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 10px;
}

.confirm-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.confirm-delete:hover {
    background-color: #bd2130;
}

.cancel-delete {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.cancel-delete:hover {
    background-color: #5a6268;
}

/* Mobile styles */
@media (max-width: 768px) {
    /* Menu and general layout */
    .menu-toggle {
        display: block;
        margin-left: auto;
    }

    .nav-items {
        display: none;
        position: fixed;
        top: 60px;
        right: 0;
        background: #333;
        width: 200px;
        flex-direction: column;
        padding: 10px 0;
        z-index: 1000;
    }

    .nav-items.active {
        display: flex;
    }

    .content {
        padding: 70px 10px 20px;
    }

    .welcome-message {
        margin: 0 0 20px 0;
        padding: 15px;
    }

    /* Container and search styles */
    .schedule-container {
        padding: 10px;
        margin: 0;
    }

    .search-container {
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
    }

    .search-container input {
        width: 100%;
        margin-right: 0;
    }

    .button-group {
        width: 100%;
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .button-group button,
    .button-group a {
        flex: 1;
        padding: 10px;
    }

    /* Card-based layout */
    .schedule-table {
        display: block;
        width: 100%;
        border: none;
        background: none;
    }

    .schedule-table thead {
        display: none;
    }

    .schedule-table tbody {
        display: block;
        width: 100%;
    }

    .schedule-table tr {
        display: block;
        width: 100%;
        margin-bottom: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
        position: relative;
    }

    .schedule-table td {
        display: block;
        width: 100%;
        padding: 8px 0;
        text-align: left;
        border: none;
    }

    /* Profile image styling */
    .schedule-table td:first-child {
        text-align: center;
        padding-bottom: 15px;
    }

    .schedule-table td:first-child img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto;
    }

    /* Cell labels */
    .schedule-table td:not(:first-child):before {
        content: attr(data-label);
        font-weight: bold;
        display: inline-block;
        width: 120px;
        color: #666;
    }

    /* Status styling */
    .schedule-table td:nth-last-child(2) {
        margin: 8px 0;
    }

    .schedule-table td:nth-last-child(2):before {
        margin-right: 10px;
    }

    /* Action buttons */
    .schedule-table td:last-child {
        padding-top: 12px;
        border-top: 1px solid #eee;
        margin-top: 8px;
    }

    .action-buttons {
        display: flex;
        justify-content: flex-start;
        gap: 10px;
    }

    .action-button {
        flex: 1;
        padding: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 4px;
    }

    /* Pagination */
    .pagination {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
        padding: 10px;
    }

    .pagination select,
    .pagination-buttons {
        width: 100%;
    }

    .pagination-buttons {
        display: flex;
        justify-content: center;
        gap: 5px;
    }

    .pagination button {
        padding: 8px 12px;
    }

    /* Modal */
    .modal-content {
        width: 90%;
        margin: 20px auto;
        padding: 15px;
    }

    .modal-body {
        padding: 15px 0;
    }

    .modal-footer {
        padding: 15px 0;
    }

    /* Show mobile-only elements */
    .mobile-only {
        display: block !important;
    }

    /* Hide desktop dropdown */
    .dropdown {
        display: none !important;
    }
}
</style>
<body>
    <div id="notification" class="notification"></div>
    <!-- Header section start -->
    <header>
        <!-- Brand logo -->
        <a class="navbar-brand" href="#">
            <img src="../images/logo.jpg" alt="Brand Logo">
        </a>
        
        <!-- Navigation section -->
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
    <!-- Header section end -->

    <main>
        <!-- <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section> -->

        <?php if (isset($message)): ?>
            <div class="message <?php echo $msg_type; ?>" id="status-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <div class="schedule-container">
                <h2>Entertainer List</h2>

                <div class="search-container">
                    <input type="text" id="search-input" placeholder="Search By Name" oninput="searchEntertainer()">
                    <div class="button-group">
                        <button class="refresh-btn" aria-label="Refresh" onclick="refreshList()">⟳</button>
                        <a href="add-entertainer.php" class="add-btn" aria-label="Add">+</a>
                    </div>
                </div>

                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Entertainer Name</th>
                            <th>Title</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-table-body">
                        <?php foreach ($entertainers as $entertainer): ?>
                            <tr>
                                <td>
                                    <img src="../images/<?php echo htmlspecialchars($entertainer['profile_image']); ?>" alt="Profile" width="50">
                                </td>
                                <td data-label="Name">
                                    <?php 
                                        echo capitalizeWords(htmlspecialchars($entertainer['first_name'])) . ' ' . 
                                             capitalizeWords(htmlspecialchars($entertainer['last_name'])); 
                                    ?>
                                </td>
                                <td data-label="Title"><?php echo capitalizeWords(htmlspecialchars($entertainer['title'])); ?></td>
                                <td data-label="Address">
                                    <?php 
                                        $address_parts = [
                                            capitalizeWords(htmlspecialchars($entertainer['street'])),
                                            capitalizeWords(htmlspecialchars($entertainer['barangay'])),
                                            capitalizeWords(htmlspecialchars($entertainer['municipality'])),
                                            capitalizeWords(htmlspecialchars($entertainer['province']))
                                        ];
                                        echo implode(', ', array_filter($address_parts));
                                    ?>
                                </td>
                                <td data-label="Contact"><?php 
                                    $formatted_number = formatPhoneNumberForDisplay($entertainer['contact_number']);
                                    echo htmlspecialchars($formatted_number); 
                                ?></td>
                                <td data-label="Status"><?php echo capitalizeWords(htmlspecialchars($entertainer['status'])); ?></td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" 
                                                onclick="viewDetails(<?php echo $entertainer['entertainer_id']; ?>)"
                                                data-tooltip="Edit">
                                            <i class="fas fa-edit"></i>
                                            <span class="mobile-only">Edit</span>
                                        </button>
                                        <button class="action-button delete-button" 
                                                onclick="deleteEntertainer(<?php echo $entertainer['entertainer_id']; ?>, '<?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name'], ENT_QUOTES); ?>')"
                                                data-tooltip="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="mobile-only">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <div>
                        <label for="items-per-page">Items per page:</label>
                        <select id="items-per-page" onchange="changeItemsPerPage(this.value)">
                            <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $items_per_page == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="30" <?php echo $items_per_page == 30 ? 'selected' : ''; ?>>30</option>
                        </select>
                    </div>
                    <div class="page-controls">
                        <button <?php if ($page <= 1) echo 'disabled'; ?> onclick="changePage(<?php echo $page - 1; ?>)">◀</button>
                        <span id="pagination-info"><?php echo ($offset + 1) . '-' . min($offset + $items_per_page, $total_records) . ' of ' . $total_records; ?></span>
                        <button <?php if ($page >= $total_pages) echo 'disabled'; ?> onclick="changePage(<?php echo $page + 1; ?>)">▶</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

<!-- Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        <h2>Edit Entertainer</h2>
        
        <form id="editForm" method="post" action="update_entertainer.php" enctype="multipart/form-data">
            <input type="hidden" id="entertainerId" name="entertainer_id">
            
            <div class="profile-image-container">
                <img id="currentProfileImage" src="../images/default-profile.jpg" alt="Profile Image">
                <div>
                    <label class="profile-image-label">
                        <i class="fas fa-camera"></i> Change Photo
                        <input type="file" id="profileImage" name="image" accept="image/*" onchange="uploadFile(this)" style="display: none;">
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div class="form-group" style="flex: 1;">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="first_name" required>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="last_name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label>Address</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label for="street">Street</label>
                        <input type="text" id="street" name="street" required placeholder="Street">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" required placeholder="Barangay">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label for="municipality">Municipality</label>
                        <input type="text" id="municipality" name="municipality" required placeholder="Municipality">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" required placeholder="Province">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" 
                       id="contact" 
                       name="contact_number" 
                       required 
                       pattern="^09\d{9}$"
                       placeholder="09XXXXXXXXX"
                       maxlength="11"
                       oninput="formatPhoneNumber(this)"
                       title="Please enter a valid phone number starting with 09">
            </div>

            <div class="form-group">
                <label for="roles">Talents</label>
                <div id="roles">
                    <?php foreach ($roles as $role): ?>
                        <label>
                            <input type="checkbox" 
                                   name="roles[]" 
                                   value="<?php echo htmlspecialchars($role['role_name']); ?>">
                            <?php echo ucwords(strtolower($role['role_name'])); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-button">Update Entertainer</button>
                <button type="button" class="cancel-button" onclick="closeModal('editModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="deleteModal" class="confirmation-modal">
    <div class="confirmation-content">
        <h3>Delete Entertainer</h3>
        <p id="deleteConfirmText">Are you sure you want to delete this entertainer?</p>
        <div class="confirmation-buttons">
            <button id="confirmDelete" class="confirm-delete">Delete</button>
            <button onclick="closeModal('deleteModal')" class="cancel-delete">Cancel</button>
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
            const navItems = document.querySelector('.nav-items');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById('dropdownContent');
            
            // Close mobile menu when clicking outside
            if (!event.target.closest('.nav-items') && 
                !event.target.closest('.menu-toggle') && 
                navItems.classList.contains('active')) {
                navItems.classList.remove('active');
                menuToggle.classList.remove('active');
            }

            // Close dropdown when clicking outside
            if (!event.target.matches('.dropbtn') && 
                !event.target.matches('.dropbtn img') && 
                !event.target.closest('.dropdown-content') &&
                dropdownContent.classList.contains('show')) {
                dropdownContent.classList.remove('show');
            }
        });

        function refreshList() {
            // Reload the page to refresh the list
            window.location.reload();
        }

        function addEntertainer() {
            // Redirect to add entertainer page
            window.location.href = 'add-entertainer.php';
        }

        function viewDetails(id) {
            // Show the modal
            document.getElementById('editModal').classList.add('active');
            document.getElementById('editModal').style.display = 'flex';
            
            // Fetch entertainer details
            fetch(`get_entertainer.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Set form values
                    document.getElementById('entertainerId').value = data.entertainer_id;
                    document.getElementById('firstName').value = data.first_name || '';
                    document.getElementById('lastName').value = data.last_name || '';
                    document.getElementById('title').value = data.title || '';
                    document.getElementById('street').value = data.street || '';
                    document.getElementById('barangay').value = data.barangay || '';
                    document.getElementById('municipality').value = data.municipality || '';
                    document.getElementById('province').value = data.province || '';
                    
                    // Format phone number for display
                    let phone = data.contact_number || '';
                    phone = phone.replace(/\D/g, '');
                    if (phone.startsWith('63')) {
                        phone = '0' + phone.substring(2);
                    }
                    if (!phone.startsWith('0')) {
                        phone = '0' + phone;
                    }
                    phone = phone.substring(0, 11);
                    document.getElementById('contact').value = phone;
                    
                    document.getElementById('status').value = data.status || 'Active';
                    
                    // Rest of your existing code...
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching entertainer details: ' + error.message);
                    closeModal('editModal');
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.getElementById(modalId).style.display = 'none';
        }

        // Function to handle file upload preview
        function uploadFile(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    input.value = '';
                    return;
                }
                
                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size should be less than 5MB');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profileImage = document.getElementById('currentProfileImage');
                    profileImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function formatPhoneNumber(input) {
            // Remove all non-digit characters
            let number = input.value.replace(/\D/g, '');
            
            // If the number starts with 63, convert it to 0
            if (number.startsWith('63')) {
                number = '0' + number.substring(2);
            }
            
            // If it doesn't start with 0, add it
            if (!number.startsWith('0')) {
                number = '0' + number;
            }
            
            // Limit to 11 digits (09XXXXXXXXX format)
            number = number.substring(0, 11);
            
            input.value = number;
            
            // Validate the length
            if (number.length === 11 && number.startsWith('09')) {
                input.setCustomValidity('');
            } else {
                input.setCustomValidity('Please enter a valid phone number starting with 09');
            }
        }

        // Function to show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            
            // Add show class after a brief delay to trigger animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Hide notification after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Form submission handling
        function handleFormSubmit(e) {
            e.preventDefault();
            
            // Validate phone number
            const contactInput = document.getElementById('contact');
            const phoneNumber = contactInput.value.replace(/\D/g, '');
            
            if (phoneNumber.length !== 11 || !phoneNumber.startsWith('09')) {
                showNotification('Please enter a valid phone number starting with 09', 'error');
                contactInput.focus();
                return false;
            }
            
            // Get form data
            const formData = new FormData(e.target);
            
            // Show loading state
            const submitButton = e.target.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;
            
            fetch('update_entertainer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    if (data.status === 'success') {
                        showNotification('Entertainer updated successfully!', 'success');
                        setTimeout(() => {
                            closeModal('editModal');
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message || 'Error updating entertainer', 'error');
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    showNotification('Error updating entertainer', 'error');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                showNotification('Error updating entertainer', 'error');
            })
            .finally(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        }

        // Remove any existing event listeners and add the new one
        const form = document.getElementById('editForm');
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        newForm.addEventListener('submit', handleFormSubmit);

        // Rest of your JavaScript code...

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.getElementById(modalId).style.display = 'none';
        }

        function searchEntertainer() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#schedule-table-body tr');

            rows.forEach(row => {
                const entertainerName = row.cells[1].textContent.toLowerCase(); // Index 1 is now the combined name column
                if (entertainerName.includes(searchValue)) {
                    row.style.display = ''; // Show row
                } else {
                    row.style.display = 'none'; // Hide row
                }
            });
        }

        function changePage(page) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            window.location.search = urlParams.toString();
        }

        function changeItemsPerPage(itemsPerPage) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('items-per-page', itemsPerPage);
            urlParams.set('page', 1); // Reset to the first page
            window.location.search = urlParams.toString();
        }

        // Add this JavaScript function for delete functionality
        function deleteEntertainer(id, name) {
            const modal = document.getElementById('deleteModal');
            const confirmText = document.getElementById('deleteConfirmText');
            const confirmButton = document.getElementById('confirmDelete');
            
            // Update confirmation text
            confirmText.textContent = `Are you sure you want to delete ${name}?`;
            
            // Show modal
            modal.style.display = 'flex';
            
            // Remove any existing click handlers
            const newConfirmButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
            
            // Add click handler for delete confirmation
            newConfirmButton.addEventListener('click', function() {
                // Show loading state
                newConfirmButton.textContent = 'Deleting...';
                newConfirmButton.disabled = true;
                
                fetch('delete_entertainer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'entertainer_id=' + id
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.status === 'success') {
                            showNotification('Entertainer deleted successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.message || 'Error deleting entertainer', 'error');
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        showNotification('Error deleting entertainer', 'error');
                    }
                })
                .catch(error => {
                    console.error('Network error:', error);
                    showNotification('Error deleting entertainer', 'error');
                })
                .finally(() => {
                    closeModal('deleteModal');
                    newConfirmButton.textContent = 'Delete';
                    newConfirmButton.disabled = false;
                });
            });
        }
    </script>
</body>
</html>