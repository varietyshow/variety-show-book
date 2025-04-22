<?php
session_start();
include 'db_connect.php'; // Include the database connection
include("get_locations.php");

// Get the list of provinces from the $mindanao_provinces array
$provinces = $mindanao_provinces;

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

// Get customer details from database using customer_id from session
$customer_id = $_SESSION['customer_id']; // Assuming you store customer_id in session
$sql = "SELECT * FROM customer_account WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    $first_name = htmlspecialchars($customer['first_name']);
    $last_name = htmlspecialchars($customer['last_name']);
    $contact_number = htmlspecialchars($customer['contact_number']);
    $email = htmlspecialchars($customer['email']);
} else {
    $first_name = htmlspecialchars($_SESSION['first_name']);
    $last_name = "";
    $contact_number = "";
    $email = "";
}

// Fetch payment methods and QR codes from the database
$payment_methods_sql = "SELECT payment_method, qr_code FROM billing";
$result = $conn->query($payment_methods_sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Store in an associative array with payment method as key
        $payment_methods[$row['payment_method']] = $row['qr_code'];
    }
} else {
    echo "<script>console.log('No payment methods available.');</script>";
} 

sort($provinces); // Sort provinces alphabetically
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="cardstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Magpie Checkout.js from CDN -->
    <script src="https://checkout.magpie.im/v2/checkout.js"></script>
    
    <!-- Location Selection JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const provinceSelect = document.getElementById('province');
        const municipalitySelect = document.getElementById('municipality');
        const barangaySelect = document.getElementById('barangay');
        
        // Function to load municipalities based on selected province
        function loadMunicipalities() {
            const selectedProvince = provinceSelect.value;
            
            // Reset municipality and barangay dropdowns
            municipalitySelect.innerHTML = '<option value="">Select Municipality/City</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (selectedProvince) {
                // Show loading indicator
                municipalitySelect.innerHTML += '<option value="" disabled>Loading...</option>';
                
                // Send AJAX request to get municipalities
                const formData = new FormData();
                formData.append('province', selectedProvince);
                
                fetch('get_locations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Remove loading indicator
                    municipalitySelect.innerHTML = '<option value="">Select Municipality/City</option>';
                    
                    // Add municipalities to dropdown
                    data.forEach(municipality => {
                        const option = document.createElement('option');
                        option.value = municipality;
                        option.textContent = municipality;
                        municipalitySelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading municipalities:', error);
                    municipalitySelect.innerHTML = '<option value="">Error loading data</option>';
                });
            }
        }
        
        // Function to load barangays based on selected municipality
        function loadBarangays() {
            const selectedProvince = provinceSelect.value;
            const selectedMunicipality = municipalitySelect.value;
            
            // Reset barangay dropdown
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (selectedProvince && selectedMunicipality) {
                // Show loading indicator
                barangaySelect.innerHTML += '<option value="" disabled>Loading...</option>';
                
                // Send AJAX request to get barangays
                const formData = new FormData();
                formData.append('province', selectedProvince);
                formData.append('municipality', selectedMunicipality);
                
                fetch('get_locations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Remove loading indicator
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    
                    // Add barangays to dropdown
                    data.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay;
                        option.textContent = barangay;
                        barangaySelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading barangays:', error);
                    barangaySelect.innerHTML = '<option value="">Error loading data</option>';
                });
            }
        }
        
        // Add event listeners
        provinceSelect.addEventListener('change', loadMunicipalities);
        municipalitySelect.addEventListener('change', loadBarangays);
        
        // Initialize if province is already selected (e.g., on page refresh)
        if (provinceSelect.value) {
            loadMunicipalities();
        }
    });
    </script>
</head>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    header {
            background-color: #333;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            position: relative;
            gap: 20px;
        }

        .navbar-brand img {
            height: 40px;
        }

        nav {
            display: flex;
            align-items: center;
            flex-grow: 1;
            justify-content: flex-end;
            gap: 15px;
        }

        .nav-items {
            display: flex;
            gap: 15px;
            margin-right: 65px;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-items a:hover {
            background-color: #555;
        }

        .dropdown {
            margin-left: auto;
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

    .card-box {
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }
    
    .card-box h3 {
        background-color: white;
        color: #333;
        padding: 8px;
        border-radius: 4px;
        margin: 0;
    }

    .card-box:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        transition: all 0.3s ease;
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

    /* New CSS for the form container */
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 15px;
        margin: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* General Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    /* Input and Label Styles */
    input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: normal;
    }

    /* Name Fields */
    .names-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
    }

    .name-field {
        flex: 1;
        min-width: 200px;
        margin-right: 10px;
    }

    .name-field:last-child {
        margin-right: 0;
    }

    /* Time Fields */
    .time-group {
        display: flex;
        gap: 0;
        margin-top: 10px;
    }

    .time-field {
        flex: 1;
        margin-right: 10px;
    }

    .time-field:last-child {
        margin-right: 0;
    }

    /* Checkbox Styles */
    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 10px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 300px;
        overflow-y: auto;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
    }

    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-right: 8px;
    }

    .checkbox-item label {
        margin: 0;
        cursor: pointer;
        white-space: nowrap;
    }

    /* Roles Section */
    .roles-message {
        padding: 15px;
        color: #666;
        text-align: center;
        border: 1px dashed #ddd;
        border-radius: 4px;
        margin-top: 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .roles-container {
        margin-top: 10px;
        width: 100%;
    }

    .entertainer-roles {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
        width: 100%;
        box-sizing: border-box;
    }

    .entertainer-name {
        margin-bottom: 10px;
        width: 100%;
        font-weight: normal;
    }

    .role-options {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        width: 100%;
    }

    .role-option {
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 120px;
        padding: 5px;
    }

    .role-option input[type="checkbox"] {
        margin: 0;
        width: 16px;
        height: 16px;
    }

    .role-option label {
        margin: 0;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: normal;
    }

    .required {
        color: red;
    }

    #entertainer-section {
        width: 100% !important;
        max-width: 100% !important;
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

    @media (max-width: 768px) {
  .entertainer-grid {
    background-color: #fff; /* or any other color you prefer */
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  }
}

/* Add this new CSS rule */
.entertainer-grid:not(:empty) {
  background-color: #fff; /* or any other color you prefer */
  padding: 20px;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.has-multiple-cards {
  background-color: #fff; /* or any other color you prefer */
  padding: 20px;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.address-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    background-color: white;
    cursor: pointer;
}

.address-field select:focus {
    outline: none;
    border-color: #87CEFA;
}

.role-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.role-options {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.role-checkbox {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    margin: 0;
    cursor: pointer;
    min-width: 120px;
    flex: 0 1 auto;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.role-checkbox:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}

.role-checkbox input {
    margin: 0 8px 0 0;
    width: 16px;
    height: 16px;
}

.role-checkbox span {
    font-size: 14px;
    line-height: 1.2;
    color: #495057;
}

.role-checkbox input:checked + span {
    font-weight: 500;
    color: #2c3e50;
}

    #package_options {
        margin-top: 20px;
    }

    .package-option {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 0; /* Remove padding from container */
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .package-option:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .package-option label {
        display: flex;
        flex-direction: column;
        height: 100%;
        cursor: pointer;
        gap: 10px;
        padding: 20px; /* Move padding to label */
        border-radius: 7px; /* Slightly smaller than container */
        transition: all 0.3s ease;
    }

    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .package-option label {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        gap: 10px;
    }

    .package-header h4 {
        margin: 0;
        font-size: 18px;
        color: #2c3e50;
        flex: 1;
    }

    .package-price {
        background: #e9ecef;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        color: #2c3e50;
        font-size: 16px;
        white-space: nowrap;
    }

    .package-duration {
        color: #666;
        font-size: 0.9em;
        margin: 5px 0;
    }

    .package-roles {
        margin-top: auto;
        font-size: 0.9em;
        color: #666;
    }

    .roles-label {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }

    /* Hide radio button but keep functionality */
    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .package-option input[type="radio"]:checked + label {
        color: #007bff;
    }

    .package-option input[type="radio"]:checked + label .package-price {
        background: #007bff;
        color: #fff;
    }

    .no-packages {
        grid-column: 1 / -1;
        text-align: center;
        color: #666;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 10px 0;
    }

    .debug-info {
        grid-column: 1 / -1;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }

    /* Add CSS for package duration display */
    .package-duration {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
        color: #495057;
        margin: 10px 0;
        display: inline-block;
    }

    .package-option input[type="radio"]:checked + label .package-duration {
        background: #e3f2fd;
        color: #0d47a1;
    }

    /* Package Grid Layout Styles */
    .package-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* Creates 3 columns */
        grid-template-rows: repeat(2, auto); /* Creates 2 rows */
        gap: 20px;
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .package-option {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .package-option:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .package-option label {
        display: flex;
        flex-direction: column;
        height: 100%;
        cursor: pointer;
        gap: 10px;
    }

    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .package-header h4 {
        margin: 0;
        color: #333;
        font-size: 1.2em;
    }

    .package-price {
        font-weight: bold;
        color: #2c3e50;
        font-size: 1.1em;
    }

    .package-duration {
        color: #666;
        font-size: 0.9em;
        margin: 5px 0;
    }

    .package-roles {
        margin-top: auto;
        font-size: 0.9em;
        color: #666;
    }

    .roles-label {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }

    /* Hide radio button but keep functionality */
    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .package-option input[type="radio"]:checked + label {
        background: #f8f9ff;
        border: 2px solid #4a90e2;
        padding: 18px;
    }
    
</style>

<style>
    /* Package Grid Layout Styles */
    .package-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .package-option {
        position: relative;
        height: 100%;
    }

    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .package-option label {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 100%;
    }

    .package-option:hover label {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .package-option input[type="radio"]:checked + label {
        background: #f0f7ff;
        border: 2px solid #4a90e2;
        padding: 19px;
    }

    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .package-header h4 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .package-price {
        background: #f0f7ff;
        padding: 4px 12px;
        border-radius: 20px;
        color: #4a90e2;
        font-weight: bold;
    }

    .package-duration {
        font-size: 0.9em;
        color: #666;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .package-roles {
        margin-top: auto;
        font-size: 0.9em;
        color: #666;
    }

    .roles-label {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }
</style>

<style>
    /* Remove any previous package styles */
    .package-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding: 20px 0;
    }

    .package-option {
        position: relative;
    }

    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .package-option label {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 20px;
        background: #ffffff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .package-option:hover label {
        border-color: #4a90e2;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .package-option input[type="radio"]:checked + label {
        background: #f8f9ff;
        border-color: #4a90e2;
    }

    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .package-header h4 {
        margin: 0;
        font-size: 1.2em;
        color: #2c3e50;
    }

    .package-price {
        background: #f0f7ff;
        padding: 4px 12px;
        border-radius: 20px;
        color: #4a90e2;
        font-weight: bold;
    }

    .package-duration {
        font-size: 0.9em;
        color: #666;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .package-roles {
        margin-top: auto;
        font-size: 0.9em;
        color: #666;
    }

    .roles-label {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }
</style>

<style>
    /* Availability Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal .modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 500px;
        position: relative;
    }

    .modal .close {
        position: absolute;
        right: 20px;
        top: 10px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .modal .close:hover {
        color: #666;
    }

    .availability-message {
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .available {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .unavailable {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .error {
        color: #721c24;
        background-color: #f8d7da;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }
</style>

<style>
    /* Ensure modal appears above other content */
    .modal {
        z-index: 1050;
    }
    .modal-backdrop {
        z-index: 1040;
    }
    .modal-dialog {
        z-index: 1060;
    }

    /* Additional modal styling */
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .modal-header {
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .modal-footer {
        border-top: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
</style>

<style>
    /* Payment Method Styles */
    .payment-methods {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }

    .payment-method-option {
        position: relative;
        margin: 0;
        cursor: pointer;
    }

    .payment-method-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .payment-method-button {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        border: 1px solid #aaa;  /* Changed to gray border */
        border-radius: 5px;
        background-color: white;
        transition: all 0.3s ease;
        min-width: 150px;
    }

    .payment-logo {
        width: 30px;
        height: 30px;
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .payment-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .payment-method-option input[type="radio"]:checked + .payment-method-button {
        border-color: #0066cc;  /* Changed to blue border */
        border-width: 2px;  /* Make the border slightly thicker when selected */
        box-shadow: 0 0 5px rgba(0, 102, 204, 0.3);  /* Add a subtle blue glow */
        background-color: #f8f9fa;
    }

    .payment-method-button span {
        font-weight: 500;
    }
</style>

<body>
<script>
// Define formatWithCommas as a global function (must be at the top for all scripts to use)
window.formatWithCommas = function(x) {
    if (typeof x === 'number') x = x.toString();
    return x.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
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
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>

        <section class="form-container">
            <h2>Appointment Details</h2>
            <form id="bookingForm" method="post" action="process_booking.php">
                <input type="hidden" id="perform_duration" name="perform_duration" value="">
                <div class="form-group names-group">
                    <div class="name-field">
                        <label for="first_name">First Name: <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo $first_name; ?>" required>
                    </div>

                    <div class="name-field">
                        <label for="last_name">Last Name: <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo $last_name; ?>" required>
                    </div>
                </div>

                <div class="name-field">
                    <label for="email">Email: <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                </div>

                <div class="name-field">
                    <label for="contact_number">Contact Number: <span class="required">*</span></label>
                    <input type="tel" id="contact_number" name="contact_number" 
                           pattern="^(09|\+639)\d{9}$" 
                           placeholder="e.g., 0917-123-4567 or +63917-123-4567"
                           title="Please enter a valid Philippine mobile number (e.g., 0917-123-4567 or +63917-123-4567)"
                           required 
                           value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>"
                           oninput="this.value = formatPhoneNumber(this.value)">
                    <small class="form-text">Format: 09XXXXXXXXX or +639XXXXXXXXX</small>
                </div>
                    
                <div class="form-group venue-group">
                    <h3 class="section-title">Venue Information</h3>
                    <div class="address-grid">
                        <div class="address-field">
                            <label for="province">Province: <span class="required">*</span></label>
                            <select id="province" name="province" required>
                                <option value="">Select Province</option>
                                <?php foreach ($provinces as $province) { ?>
                                <option value="<?php echo $province; ?>"><?php echo $province; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="address-field">
                            <label for="municipality">Municipality/City: <span class="required">*</span></label>
                            <select id="municipality" name="municipality" required>
                                <option value="">Select Municipality/City</option>
                            </select>
                        </div>
                        <div class="address-field">
                            <label for="barangay">Barangay: <span class="required">*</span></label>
                            <select id="barangay" name="barangay" required>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                        <div class="address-field">
                            <label for="street">Street/Purok: <span class="required">*</span></label>
                            <input type="text" id="street" name="street" required>
                        </div>
                    </div>
                </div>

                <style>
                    .venue-group {
                        margin-top: 20px;
                        margin-bottom: 20px;
                    }
                    .section-title {
                        font-size: 16px;
                        margin-bottom: 15px;
                        color: #333;
                        font-weight: bold;
                    }
                    .address-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 15px;
                    }
                    .address-field {
                        width: 100%;
                    }
                    .address-field label {
                        display: block;
                        margin-bottom: 5px;
                    }
                    .address-field input {
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        box-sizing: border-box;
                    }
                    @media (max-width: 768px) {
                        .address-grid {
                            grid-template-columns: 1fr;
                        }
                    }
                </style>

<div class="form-group">
        <label for="date">Date: <span class="required">*</span></label>
        <input type="date" id="date" name="date" required>
    </div>

    <div class="form-group time-group">
        <div class="time-field">
            <label for="start_time">Start Time: <span class="required">*</span></label>
            <input type="time" id="start_time" name="start_time" required>
        </div>
        
        <div class="time-field">
            <label for="end_time">End Time: <span class="required">*</span></label>
            <input type="time" id="end_time" name="end_time" required>
        </div>
    </div>


<div class="form-group">
    <label for="bookingOption">Select Booking Option:</label>
    <select id="bookingOption" name="bookingOption" class="form-control" required style="box-shadow:none;">
        <option value="">Choose an option</option>
        <option value="option1">Option 1: Individual Talent Booking</option>
        <option value="option2">Option 2: Package Deal Booking</option>
    </select>
</div>

    <!-- Add a container for both sections -->
    <div id="option1Section" style="display: none;">
    <div class="form-group names-group">
        <div class="name-field">
            <label>Select Entertainers: <span class="required">*</span></label>
            <div class="checkbox-group">
                <?php
                // Existing entertainer selection code
                $sql = "SELECT e.entertainer_id, e.first_name, e.last_name, e.title 
                       FROM entertainer_account e";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="entertainer_<?php echo $row['entertainer_id']; ?>" 
                                   name="entertainers[]" value="<?php echo $row['entertainer_id']; ?>">
                            <label for="entertainer_<?php echo $row['entertainer_id']; ?>"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></label>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No entertainers available.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="form-group names-group">
        <div class="name-field" id="entertainer-section" style="width: 100%;">
            <label>Entertainer Role: <span class="required">*</span></label>
            <style>
                input[name="entertainers[]"]:checked ~ #entertainer-section #no-entertainer-message {
                    display: none !important;
                }
                input[name="entertainers[]"]:checked ~ #entertainer-section .role-section {
                    display: block !important;
                }
            </style>
            <p id="no-entertainer-message" style="color: #6c757d; background: #f8f9fa; padding: 10px; border: 1px dashed #ccc; text-align: center; margin: 10px 0;">
                No entertainer selected
            </p>
            <script>
            // Immediate fix for the "No entertainer selected" message
            document.addEventListener('DOMContentLoaded', function() {
                // Check if any entertainer is selected on page load
                function checkEntertainers() {
                    const hasSelectedEntertainer = document.querySelector('input[name="entertainers[]"]:checked');
                    const message = document.getElementById('no-entertainer-message');
                    if (message && hasSelectedEntertainer) {
                        message.style.display = 'none';
                    }
                }
                
                // Add event listeners to all entertainer checkboxes
                const checkboxes = document.querySelectorAll('input[name="entertainers[]"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const message = document.getElementById('no-entertainer-message');
                        if (message) {
                            message.style.display = this.checked ? 'none' : 'block';
                        }
                    });
                });
                
                // Check immediately and again after a short delay
                checkEntertainers();
                setTimeout(checkEntertainers, 100);
            });
            </script>
            <div id="roles-container">
                            <?php
                            // First, get all roles from the roles table
                            $rolesQuery = "SELECT role_id, role_name FROM roles";
                            $rolesResult = $conn->query($rolesQuery);
                            $rolesMap = [];
                            while ($roleRow = $rolesResult->fetch_assoc()) {
                                $rolesMap[$roleRow['role_name']] = $roleRow['role_id'];
                            }

                            $sql = "SELECT * FROM entertainer_account WHERE roles IS NOT NULL";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <div class="role-section" id="roles-<?php echo $row['entertainer_id']; ?>" style="display: none;">
                                        <h4><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h4>
                                        <div class="role-options">
                                            <?php
                                            if (!empty($row['roles'])) {
                                                $roles = explode(',', $row['roles']);
                                                foreach($roles as $role) {
                                                    $role = trim($role);
                                                    if (!empty($role)) {
                                                        $roleId = isset($rolesMap[$role]) ? $rolesMap[$role] : '';
                                                        ?>
                                                        <label class="role-checkbox">
                                                            <input type="checkbox" name="entertainer_roles[<?php echo $row['entertainer_id']; ?>][]" value="<?php echo $roleId; ?>" 
                                                                   data-role-id="<?php echo $roleId; ?>">
                                                            <span><?php echo htmlspecialchars($role); ?></span>
                                                        </label>
                                                        <?php
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
        </div>
    </div>

    <div class="form-group price-method-group">
        <h4>Role Price Details:</h4>
        <div id="custom_price_details">
            <div id="custom_price_table"></div>
        </div>
    </div>
</div>

<div id="option2Section" style="display: none;">
        <!-- Package deal selection content -->
        <div class="form-group">
            <label>Select Package:</label>
            <div class="package-list">
                <?php
                // Fetch packages from the database with detailed role information
                $packageQuery = "SELECT cp.combo_id, cp.package_name, cp.price 
                                FROM combo_packages cp
                                ORDER BY cp.package_name";
                $packageResult = $conn->query($packageQuery);
                
                if ($packageResult && $packageResult->num_rows > 0) {
                    while($package = $packageResult->fetch_assoc()) {
                        ?>
                        <div class="package-option">
                            <input type="radio" id="package_<?php echo $package['combo_id']; ?>" 
                                   name="packageSelect" value="<?php echo $package['combo_id']; ?>"
                                   data-price="<?php echo $package['price']; ?>">
                            <label for="package_<?php echo $package['combo_id']; ?>">
                                <div class="package-header">
                                    <h4><?php echo htmlspecialchars($package['package_name']); ?></h4>
                                    <div class="package-price">₱<?php echo number_format($package['price'], 2); ?></div>
                                </div>
                                
                                <div class="package-roles">
                                    <span class="roles-label">Includes:</span>
                                    <ul class="roles-list">
                                    <?php
                                    // Get roles and entertainers for this package
                                    $rolesQuery = "SELECT r.role_name, ea.first_name, ea.last_name, ea.title 
                                                FROM combo_package_roles cpr
                                                JOIN roles r ON cpr.role_id = r.role_id
                                                JOIN entertainer_account ea ON cpr.entertainer_id = ea.entertainer_id
                                                WHERE cpr.combo_id = " . $package['combo_id'];
                                    $rolesResult = $conn->query($rolesQuery);
                                    
                                    if ($rolesResult && $rolesResult->num_rows > 0) {
                                        while($role = $rolesResult->fetch_assoc()) {
                                            // Capitalize first letter of role name
                                            $roleName = ucfirst($role['role_name']);
                                            
                                            // Capitalize first letter of first name and last name
                                            $firstName = ucfirst(strtolower($role['first_name']));
                                            $lastName = ucfirst(strtolower($role['last_name']));
                                            $entertainerName = $firstName . ' ' . $lastName;
                                            
                                            $entertainerTitle = !empty($role['title']) ? ' (' . $role['title'] . ')' : '';
                                            
                                            echo '<li>' . htmlspecialchars($roleName) . ' – ' . 
                                                htmlspecialchars($entertainerName) . 
                                                htmlspecialchars($entertainerTitle) . '</li>';
                                        }
                                    } else {
                                        echo '<li>No roles specified</li>';
                                    }
                                    ?>
                                    </ul>
                                </div>
                            </label>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-packages">No packages available at this time.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <style>
    .package-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 15px;
    }
    
    .package-option {
        position: relative;
    }
    
    .package-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .package-option label {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .package-option input[type="radio"]:checked + label {
        border-color: #4a90e2;
        box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.3);
    }
    
    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .package-header h4 {
        margin: 0;
        font-size: 1.1em;
        color: #333;
    }
    
    .package-price {
        font-size: 1.1em;
        font-weight: bold;
        color: #4a90e2;
    }
    
    .package-description {
        margin: 10px 0;
        font-size: 0.9em;
        color: #666;
    }
    
    .package-duration {
        font-size: 0.9em;
        color: #666;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }
    
    .package-roles {
        margin-top: auto;
        font-size: 0.9em;
        color: #666;
    }
    
    .roles-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .package-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .package-list {
            grid-template-columns: 1fr;
        }
    }

    /* Additional styles for package cards */
    .package-roles, .package-entertainers {
        margin-top: 10px;
        font-size: 0.9em;
        color: #666;
        line-height: 1.4;
    }
    
    .roles-label, .entertainers-label {
        font-weight: 600;
        color: #2c3e50;
        margin-right: 5px;
    }
    
    .package-entertainers {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #eee;
    }
</style>
<!-- Add this JavaScript code at the end of your file -->
<script>
// Define formatWithCommas as a global function
window.formatWithCommas = function(x) {
    if (typeof x === 'number') x = x.toString();
    return x.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

document.addEventListener('DOMContentLoaded', function() {
    const bookingOption = document.getElementById('bookingOption');
    const option1Section = document.getElementById('option1Section');
    const option2Section = document.getElementById('option2Section');
    const totalPriceInput = document.getElementById('total_price');
    const downPaymentInput = document.getElementById('down_payment');
    const balanceInput = document.getElementById('balance');

    // Function to reset all price fields
    function resetPriceFields() {
        if (totalPriceInput) totalPriceInput.value = '';
        if (downPaymentInput) downPaymentInput.value = '';
        if (balanceInput) balanceInput.value = '';
    }

    // Function to update grand total
    window.updateGrandTotal = function() {
        const totals = Array.from(document.querySelectorAll('tbody .total-cell'))
            .map(cell => parseFloat(cell.textContent.replace('₱', '').replace(/,/g, '')) || 0);
        const grandTotal = totals.reduce((sum, total) => sum + total, 0);
        const downPayment = grandTotal * 0.5;

        // Update display with formatted numbers
        document.getElementById('grand-total').textContent = `₱${formatWithCommas(grandTotal.toFixed(2))}`;
        
        if (totalPriceInput) totalPriceInput.value = formatWithCommas(grandTotal.toFixed(2));
        if (downPaymentInput) downPaymentInput.value = formatWithCommas(downPayment.toFixed(2));
        if (balanceInput) balanceInput.value = formatWithCommas((grandTotal - downPayment).toFixed(2));
    }

    // Function to update custom price table
    window.updateCustomPriceTable = function() {
        const totals = Array.from(document.querySelectorAll('tbody .total-cell'))
            .map(cell => parseFloat(cell.textContent.replace('₱', '').replace(/,/g, '')) || 0);
        const grandTotal = totals.reduce((sum, total) => sum + total, 0);
        const downPayment = grandTotal * 0.5;

        if (totalPriceInput) totalPriceInput.value = formatWithCommas(grandTotal.toFixed(2));
        if (downPaymentInput) downPaymentInput.value = formatWithCommas(downPayment.toFixed(2));
        if (balanceInput) balanceInput.value = formatWithCommas((grandTotal - downPayment).toFixed(2));
    }

    if (bookingOption) {
        bookingOption.addEventListener('change', function() {
            resetPriceFields();

            if (option1Section) option1Section.style.display = 'none';
            if (option2Section) option2Section.style.display = 'none';
            
            // Clear any selected package radios
            const packageRadios = document.querySelectorAll('input[name="packageSelect"]');
            packageRadios.forEach(radio => radio.checked = false);

            // Clear any selected entertainers and roles
            const entertainerCheckboxes = document.querySelectorAll('input[name="entertainers[]"]');
            entertainerCheckboxes.forEach(cb => cb.checked = false);

            const roleCheckboxes = document.querySelectorAll('.role-checkbox input');
            roleCheckboxes.forEach(cb => cb.checked = false);

            // Clear custom price table
            const customPriceTable = document.getElementById('custom_price_table');
            if (customPriceTable) customPriceTable.innerHTML = '';

            if (this.value === 'option1') {
                if (option1Section) option1Section.style.display = 'block';
            } else if (this.value === 'option2') {
                if (option2Section) option2Section.style.display = 'block';
            }
        });
    }

    // Package selection handler
    const packageRadios = document.querySelectorAll('input[name="packageSelect"]');
    packageRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const price = parseFloat(this.dataset.price);
                const downPayment = price * 0.5;
                if (totalPriceInput) totalPriceInput.value = formatWithCommas(price.toFixed(2));
                if (downPaymentInput) downPaymentInput.value = formatWithCommas(downPayment.toFixed(2));
                if (balanceInput) balanceInput.value = formatWithCommas((price - downPayment).toFixed(2));
            }
        });
    });

    // Add event listeners for custom price calculations
    document.querySelectorAll('input[name="entertainers[]"], .role-checkbox input').forEach(input => {
        input.addEventListener('change', function() {
            updateCustomPriceTable();
        });
    });
});</script>
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
        
                // Immediate fix for the "No entertainer selected" message
                function fixNoEntertainerMessage() {
                    const message = document.getElementById('no-entertainer-message');
                    const selectedEntertainers = document.querySelectorAll('input[name="entertainers[]"]:checked');
                    
                    if (message && selectedEntertainers.length > 0) {
                        // Force hide the message if any entertainer is selected
                        message.style.display = 'none';
                        console.log('Hiding no entertainer message');
                        
                        // Show the role sections for selected entertainers
                        selectedEntertainers.forEach(checkbox => {
                            const section = document.getElementById('roles-' + checkbox.value);
                            if (section) {
                                section.style.display = 'block';
                                console.log('Showing role section for entertainer ID:', checkbox.value);
                            }
                        });
                    }
                }
                
                // Run the fix immediately
                fixNoEntertainerMessage();
                
                // Also run when DOM is fully loaded
                document.addEventListener('DOMContentLoaded', function() {
                    const message = document.getElementById('no-entertainer-message');
                    const rolesContainer = document.getElementById('roles-container');
                    
                    // Run the fix again
                    fixNoEntertainerMessage();
                    
                    // Function to update display based on selected entertainers
                    function updateDisplay() {
                        const selectedEntertainers = document.querySelectorAll('input[name="entertainers[]"]:checked');
                        const hasSelectedEntertainer = selectedEntertainers.length > 0;
                        
                        // Hide the message if any entertainer is selected
                        if (message) {
                            message.style.display = hasSelectedEntertainer ? 'none' : 'block';
                        }
                        
                        // Show/hide individual role sections based on selected entertainers
                        document.querySelectorAll('.role-section').forEach(section => {
                            section.style.display = 'none'; // Hide all by default
                        });
                        
                        // Show role sections for selected entertainers
                        selectedEntertainers.forEach(checkbox => {
                            const section = document.getElementById('roles-' + checkbox.value);
                            if (section) {
                                section.style.display = 'block';
                            }
                        });
                    }

                    // Add event listeners to entertainer checkboxes
                    document.querySelectorAll('input[name="entertainers[]"]').forEach(cb => {
                        cb.addEventListener('change', function() {
                            updateDisplay();
                            // Run the fix again after a change
                            setTimeout(fixNoEntertainerMessage, 10);
                        });
                    });
                    
                    // Run on page load to handle pre-selected entertainers
                    updateDisplay();

                    // Debug log
                    console.log('Roles container found:', document.getElementById('roles-container') !== null);
                    console.log('Number of role sections:', document.querySelectorAll('.role-section').length);
                    console.log('Selected entertainers:', document.querySelectorAll('input[name="entertainers[]"]:checked').length);
                    
                    // Run the fix one more time after a short delay
                    setTimeout(fixNoEntertainerMessage, 100);
                });
            </script>



                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const customPriceDetails = document.getElementById('custom_price_details');
                   
                    
                    // Store roles data and create a mapping
                    const rolesData = <?php
                        $roles_query = "SELECT role_id, role_name, rate, duration, duration_unit FROM roles";
                        $roles_result = $conn->query($roles_query);
                        $roles = [];
                        while($row = $roles_result->fetch_assoc()) {
                            $roles[$row['role_id']] = [
                                'role_name' => $row['role_name'],
                                'rate' => $row['rate'],
                                'duration' => $row['duration'],
                                'duration_unit' => $row['duration_unit']
                            ];
                        }
                        echo json_encode($roles);
                    ?>;
                    


                    // Update table when entertainers or roles are changed
                    document.querySelectorAll('input[name="entertainers[]"], .role-checkbox input').forEach(input => {
                            input.addEventListener('change', function() {
                                updateCustomPriceTable();
                            });
                        });

                        // Initial call to show the price table
                        updateCustomPriceTable();

                    // Function to update price when package is selected
                    window.updatePackagePrice = function(packageInput) {
                        const price = parseFloat(packageInput.dataset.price);
                        document.getElementById('total_price').value = formatWithCommas(price.toFixed(2));
                        
                        // Calculate and set 50% down payment
                        const downPayment = price * 0.5;

                        // Update display with formatted numbers
                        document.getElementById('down_payment').value = formatWithCommas(downPayment.toFixed(2));
                        updateBalance();
                    };
                    
                    // Function to update balance based on total price and down payment
                    function updateBalance() {
                        const totalPriceStr = document.getElementById('total_price').value.replace(/,/g, '');
                        const downPaymentStr = document.getElementById('down_payment').value.replace(/,/g, '');
                        
                        const totalPrice = parseFloat(totalPriceStr) || 0;
                        const downPayment = parseFloat(downPaymentStr) || 0;
                        
                        const balance = totalPrice - downPayment;
                        document.getElementById('balance').value = formatWithCommas(balance.toFixed(2));
                    }

                    // Function to update custom price table
                    async function updateCustomPriceTable() {
                        const customPriceTable = document.getElementById('custom_price_table');
                        const noEntertainerMessage = document.getElementById('no-entertainer-message');
                        const selectedRoleCheckboxes = document.querySelectorAll('.role-checkbox input[type="checkbox"]:checked');
                        
    // Get all selected roles with their data
    const selectedRoles = Array.from(selectedRoleCheckboxes).map(checkbox => {
        const roleId = parseInt(checkbox.value);
        const roleSection = checkbox.closest('.role-section');
        const entertainerId = roleSection.getAttribute('id').replace('roles-', '');
        const entertainerName = roleSection.querySelector('h4').textContent;
        const roleName = checkbox.nextElementSibling.textContent;
        const roleData = rolesData[roleId] || {
            rate: 0,
            duration: '1',
            duration_unit: 'hour'
        };
        
        return {
            roleId: roleId,
            entertainerId: entertainerId,
            entertainerName: entertainerName,
            roleName: roleName.trim(),
            duration: roleData.duration,
            duration_unit: roleData.duration_unit,
            rate: parseFloat(roleData.rate)
        };
    });

    // Hide the no entertainer message when roles are selected
    if (noEntertainerMessage) {
        noEntertainerMessage.style.display = selectedRoles.length > 0 ? 'none' : 'block';
    }

    if (selectedRoles.length === 0) {
        customPriceTable.innerHTML = '<p>Please select entertainer roles to view pricing details.</p>';
        return;
    }

                        let tableHTML = `
                            <style>
                                .custom-price-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-top: 1rem;
                                }
                                .custom-price-table th,
                                .custom-price-table td {
                                    padding: 8px;
                                    border: 1px solid #ddd;
                                    text-align: left;
                                }
                                .custom-price-table th {
                                    background-color: #f5f5f5;
                                }
                                .duration-input {
                                    width: 60px;
                                    margin-right: 5px;
                                }
                                .total-cell {
                                    font-weight: bold;
                                }
                            </style>
                            <table class="custom-price-table">
                                <thead>
                                    <tr>
                                        <th>Entertainer</th>
                                        <th>Role</th>
                                        <th>Duration</th>
                                        <th>Rate</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                        selectedRoles.forEach(role => {
                            const rate = parseFloat(role.rate) || 0;
                            tableHTML += `
                                <tr>
                                    <td>${role.entertainerName}</td>
                                    <td>${role.roleName}</td>
                                    <td>
                                        <input type="number" class="duration-input" min="1" value="${role.duration}" 
                                               data-role-id="${role.roleId}" data-entertainer-id="${role.entertainerId}">
                                        ${role.duration_unit}${parseInt(role.duration) !== 1 ? 's' : ''}
                                    </td>
                                    <td>₱${rate.toFixed(2)} per ${role.duration_unit}</td>
                                    <td class="total-cell">₱${(rate * parseFloat(role.duration)).toFixed(2)}</td>
                                </tr>
                            `;
                        });

                        tableHTML += `
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" style="text-align: right;"><strong>Total Amount:</strong></td>
                                        <td id="grand-total" class="total-cell">₱0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;

                        customPriceTable.innerHTML = tableHTML;

                        // Add event listeners for duration inputs
                        document.querySelectorAll('.duration-input').forEach(input => {
                            input.addEventListener('input', function() {
                                const roleId = this.dataset.roleId;
                                const entertainerId = this.dataset.entertainerId;
                                const role = selectedRoles.find(r => r.roleId === parseInt(roleId) && r.entertainerId === entertainerId);
                                if (role) {
                                    const rate = parseFloat(role.rate) || 0;
                                    const duration = parseFloat(this.value) || 0;
                                    const total = duration * rate;
                                    this.closest('tr').querySelector('.total-cell').textContent = `₱${total.toFixed(2)}`;
                                    updateGrandTotal();
                                }
                            });
                        });

                        function updateGrandTotal() {
                    const totals = Array.from(document.querySelectorAll('tbody .total-cell'))
                        .map(cell => parseFloat(cell.textContent.replace('₱', '').replace(/,/g, '')) || 0);
                    const grandTotal = totals.reduce((sum, total) => sum + total, 0);
                    
                    // Format with commas
                    document.getElementById('grand-total').textContent = `₱${formatWithCommas(grandTotal.toFixed(2))}`;
                    
                    // Set total price with comma formatting
                    document.getElementById('total_price').value = formatWithCommas(grandTotal.toFixed(2));
                    
                    // Calculate and set 50% down payment with comma formatting
                    const downPayment = grandTotal * 0.5;
                    document.getElementById('down_payment').value = formatWithCommas(downPayment.toFixed(2));
                    
                    // Calculate balance with comma formatting
                    document.getElementById('balance').value = formatWithCommas((grandTotal - downPayment).toFixed(2));
                }

                        updateGrandTotal();
                    }

                });
                </script>



                <!-- Availability Check Modal -->
                <div class="modal fade" id="availabilityModal" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="availabilityModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="availabilityModalLabel">Checking Availability</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="availabilityMessage"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                async function checkAvailability() {
                    const eventDate = document.getElementById('date').value;
                    const eventStartTime = document.getElementById('start_time').value;
                    const eventEndTime = document.getElementById('end_time').value;
                    const availabilityResults = document.getElementById('availabilityMessage');
                    const modal = document.getElementById('availabilityModal');

                    // Get selected entertainers
                    const selectedEntertainers = Array.from(document.querySelectorAll('input[name="entertainers[]"]:checked'))
                        .map(checkbox => checkbox.value);

                    // Only proceed if we have all required values
                    if (!eventDate || !eventStartTime || !eventEndTime || selectedEntertainers.length === 0) {
                        return;
                    }

                    // Show modal using Bootstrap
                    const myModal = bootstrap.Modal.getOrCreateInstance(modal);
                    myModal.show();
                    
                    availabilityResults.innerHTML = `
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Checking availability...</span>
                        </div>
                    `;

                    try {
                        const response = await fetch('check_availability.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                date: eventDate,
                                start_time: eventStartTime,
                                end_time: eventEndTime,
                                entertainers: selectedEntertainers
                            })
                        });

                        const data = await response.json();
                        
                        // Update availability results
                        availabilityResults.innerHTML = data.map(status => `
                            <div class="alert ${status.available ? 'alert-success' : 'alert-danger'} mb-2">
                                <strong>${status.name}</strong> ${status.available ? 'is available' : 'is not available'}
                            </div>
                        `).join('');

                    } catch (error) {
                        console.error('Error checking availability:', error);
                        availabilityResults.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> Error checking availability. Please try again.
                            </div>
                        `;
                    }
                }

                // Add event listeners when document is ready
                document.addEventListener('DOMContentLoaded', function() {
                    // Handle modal close events
                    const availabilityModal = document.getElementById('availabilityModal');
                    if (availabilityModal) {
                        availabilityModal.addEventListener('hidden.bs.modal', function () {
                            // Clean up any resources or reset state if needed
                            document.getElementById('availabilityMessage').innerHTML = '';
                        });
                    }

                    // Add event listeners for date and time inputs
                    const dateInput = document.getElementById('date');
                    const startTimeInput = document.getElementById('start_time');
                    const endTimeInput = document.getElementById('end_time');
                    
                    // Create a debounced version of checkAvailability
                    let timeoutId = null;
                    const debouncedCheck = () => {
                        if (timeoutId) clearTimeout(timeoutId);
                        timeoutId = setTimeout(checkAvailability, 500); // Wait 500ms after last change
                    };

                    // Add event listeners
                    if (dateInput) dateInput.addEventListener('change', debouncedCheck);
                    if (startTimeInput) startTimeInput.addEventListener('change', debouncedCheck);
                    if (endTimeInput) endTimeInput.addEventListener('change', debouncedCheck);
                    
                    // Add event listeners for entertainer checkboxes
                    document.querySelectorAll('input[name="entertainers[]"]').forEach(checkbox => {
                        checkbox.addEventListener('change', debouncedCheck);
                    });
                });
            </script>

                <div class="form-group">
                    <label><strong>Select Payment Method:</strong> <span class="required">*</span></label>
                    <div class="payment-methods">
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="gcash" required checked>
                            <div class="payment-method-button">
                                <div class="payment-logo">
                                    <img src="assets/images/gcash-logo.png" alt="GCash">
                                </div>
                                <span>Gcash</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group price-summary-group">
        <label for="total_price">Total Price:</label>
        <input type="text" id="total_price" name="total_price" class="form-control" readonly style="font-weight:bold; color:#2c3e50; background:#f8f9fa;">
    </div>
    <div class="form-group">
        <label for="down_payment">Down Payment (min 50%):</label>
        <input type="text" id="down_payment" name="down_payment" class="form-control" style="font-weight:bold; color:#2c3e50;">
        <small class="text-muted">Minimum down payment is 50% of the total price</small>
    </div>
    <div class="form-group">
        <label for="balance">Balance:</label>
        <input type="text" id="balance" name="balance" class="form-control" readonly style="font-weight:bold; color:#2c3e50; background:#f8f9fa;">
    </div>

                <script>
                // Add event listener for down payment input
                document.addEventListener('DOMContentLoaded', function() {
                    const downPaymentInput = document.getElementById('down_payment');
                    
                    // Handle down payment input changes - only format on blur or enter key
                    downPaymentInput.addEventListener('keydown', function(e) {
                        // If Enter key is pressed
                        if (e.key === 'Enter') {
                            formatAndValidateDownPayment();
                            e.preventDefault(); // Prevent form submission
                        }
                    });
                    
                    // Format and validate when focus is lost
                    downPaymentInput.addEventListener('blur', function() {
                        // Only validate if not already in validation process
                        if (downPaymentInput.dataset.validating !== 'true') {
                            formatAndValidateDownPayment();
                        }
                    });
                    
                    // Function to format and validate down payment
                    function formatAndValidateDownPayment() {
                        // Remove commas from the input value
                        let value = downPaymentInput.value.replace(/,/g, '');
                        
                        // Ensure it's a valid number
                        if (value === '' || isNaN(value)) {
                            value = '0';
                        }
                        
                        const numValue = parseFloat(value);
                        
                        // Format with commas and 2 decimal places
                        downPaymentInput.value = formatWithCommas(numValue.toFixed(2));
                        
                        // Validate minimum down payment
                        const totalPriceStr = document.getElementById('total_price').value.replace(/,/g, '');
                        const totalPrice = parseFloat(totalPriceStr) || 0;
                        const minimumDownPayment = totalPrice * 0.5;
                        
                        // Show warning if less than minimum
                        if (numValue < minimumDownPayment) {
                            // Set a flag to prevent recursive validation
                            downPaymentInput.dataset.validating = 'true';
                            
                            // Highlight the input to indicate error
                            downPaymentInput.style.borderColor = 'red';
                            
                            // Show alert with a slight delay to prevent focus issues
                            setTimeout(function() {
                                alert('Down payment must be at least 50% of the total price (' + formatWithCommas(minimumDownPayment.toFixed(2)) + ')');
                                // Set focus after alert is closed
                                setTimeout(function() {
                                    downPaymentInput.focus();
                                    // Reset the validation flag
                                    delete downPaymentInput.dataset.validating;
                                }, 100);
                            }, 100);
                        } else {
                            // Reset border if valid
                            downPaymentInput.style.borderColor = '';
                        }
                        
                        // Update the balance
                        updateBalance();
                    }
                    
                    // Add validation to the form submission
                    const bookingForm = document.getElementById('bookingForm');
                    const originalSubmit = bookingForm.onsubmit;
                    
                    bookingForm.onsubmit = function(event) {
                        // Validate minimum down payment
                        const totalPriceStr = document.getElementById('total_price').value.replace(/,/g, '');
                        const downPaymentStr = document.getElementById('down_payment').value.replace(/,/g, '');
                        
                        const totalPrice = parseFloat(totalPriceStr) || 0;
                        const downPayment = parseFloat(downPaymentStr) || 0;
                        const minimumDownPayment = totalPrice * 0.5;
                        
                        if (downPayment < minimumDownPayment) {
                            event.preventDefault();
                            alert('Down payment must be at least 50% of the total price (' + formatWithCommas(minimumDownPayment.toFixed(2)) + ')');
                            downPaymentInput.focus();
                            return false;
                        }
                        
                        // Call the original submit handler
                        if (originalSubmit) {
                            return originalSubmit.call(this, event);
                        }
                    };
                });
                </script>
                <button type="submit" class="btn btn-primary">Submit Booking & Pay</button>
            </form>

            <script>
                // Form submission handler
                document.getElementById('bookingForm').addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    // Validate minimum down payment
                    const totalPriceStr = document.getElementById('total_price').value.replace(/,/g, '');
                    const downPaymentStr = document.getElementById('down_payment').value.replace(/,/g, '');
                    
                    const totalPrice = parseFloat(totalPriceStr) || 0;
                    const downPayment = parseFloat(downPaymentStr) || 0;
                    const minimumDownPayment = totalPrice * 0.5;
                    
                    if (downPayment < minimumDownPayment) {
                        alert('Down payment must be at least 50% of the total price (' + formatWithCommas(minimumDownPayment.toFixed(2)) + ')');
                        document.getElementById('down_payment').focus();
                        return;
                    }
                    
                    // Show loading indicator
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    
                    const formData = new FormData(this);
{{ ... }}
