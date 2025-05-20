<?php
session_start();
include("db_connect.php");

// Add new function to check entertainer availability
function checkEntertainerAvailability($date, $entertainer_id) {
    global $conn;
    
    // First get the entertainer's name
    $nameQuery = "SELECT CONCAT(first_name, ' ', last_name) as entertainer_name 
                  FROM entertainer_account 
                  WHERE entertainer_id = ?";
    $nameStmt = $conn->prepare($nameQuery);
    $nameStmt->bind_param("i", $entertainer_id);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $entertainer = $nameResult->fetch_assoc();
    $entertainerName = $entertainer['entertainer_name'];
    
    // Check if entertainer is already booked
    $query = "SELECT * 
              FROM booking_report 
              WHERE entertainer_name LIKE ? 
              AND date_schedule = ? 
              AND (
                  (? BETWEEN time_start AND time_end) OR
                  (? BETWEEN time_start AND time_end)
              )
              AND status = 'Approved'";
              
    $stmt = $conn->prepare($query);
    $searchPattern = '%' . $entertainerName . '%';
    $stmt->bind_param("sssss", 
        $searchPattern, 
        $date,
        $_POST['time_start'], $_POST['time_end'],
        $_POST['time_start']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 0; // Returns true if entertainer is available
}

// Fetch all active entertainers with their titles
$entertainerQuery = "SELECT entertainer_id, CONCAT(first_name, ' ', last_name, ' (', title, ')') as entertainer_name 
                    FROM entertainer_account 
                    WHERE status = 'Active'
                    ORDER BY first_name, last_name";
$entertainerResult = $conn->query($entertainerQuery);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    
    // Event Venue Information
    $street = $_POST['event_street'];
    $barangay = $_POST['event_barangay'];
    $municipality = $_POST['event_municipality'];
    $province = $_POST['event_province'];
    
    $entertainer_ids = $_POST['entertainer_id'];
    $date_schedule = $_POST['date_schedule'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $roles = json_decode($_POST['selected_roles'], true);
    $perform_durations = json_decode($_POST['selected_durations'], true);
    $total_price = $_POST['total_price'];
    $down_payment = $_POST['down_payment'];
    $balance = $total_price - $down_payment;
    $package_name = $_POST['package_name'];
    
    // Handle file upload for payment image
    $payment_image = '';
    if(isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] == 0) {
        $target_dir = "../images/";
        $file_extension = pathinfo($_FILES["payment_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["payment_image"]["tmp_name"], $target_file)) {
            $payment_image = $target_file;
        }
    }

    // Start transaction
    $conn->begin_transaction();
    
    // Get entertainer details
    $entertainer_id = $entertainer_ids[0]; // Get the first entertainer
    $entertainer_query = "SELECT CONCAT(first_name, ' ', last_name) as entertainer_name 
                         FROM entertainer_account 
                         WHERE entertainer_id = ?";
    $stmt_ent = $conn->prepare($entertainer_query);
    $stmt_ent->bind_param("i", $entertainer_id);
    $stmt_ent->execute();
    $result = $stmt_ent->get_result();
    $entertainer = $result->fetch_assoc();
    $entertainer_name = $entertainer['entertainer_name'];

    // Combine roles and perform durations into strings
    $roles_str = '';
    $perform_durations_str = '';
    foreach($roles as $entertainer_id => $entertainer_roles) {
        foreach($entertainer_roles as $key => $role) {
            $roles_str .= $role . ', ';
            $perform_durations_str .= $role . ': ' . $perform_durations[$entertainer_id][$key];
            if($key < count($entertainer_roles) - 1) {
                $perform_durations_str .= ', ';
            }
        }
    }
    $roles_str = rtrim($roles_str, ', ');
    $perform_durations_str = rtrim($perform_durations_str, ', ');

    // Insert into booking_report
    $sql = "INSERT INTO booking_report (admin_id, customer_id, entertainer_id, first_name, last_name, 
            contact_number, street, barangay, municipality, province, date_schedule, time_start, time_end,
            entertainer_name, roles, perform_durations, package, total_price, down_payment, balance, 
            payment_image, status, remarks, reason) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', '')";
    
    $admin_id = 0; // Set this to the actual admin ID if available
    $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissssssssssssssddd", 
        $admin_id, $customer_id, $entertainer_id,
        $first_name, $last_name, $contact_number,
        $street, $barangay, $municipality, $province,
        $date_schedule, $time_start, $time_end,
        $entertainer_name, $roles_str, $perform_durations_str,
        $package_name, $total_price, $down_payment, $balance
    );
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Insert into booking_entertainers for each selected entertainer
        $entertainer_query = "INSERT INTO booking_entertainers (booking_id, entertainer_id, roles, perform_durations) VALUES (?, ?, ?, ?)";
        $entertainer_stmt = $conn->prepare($entertainer_query);
        
        $success = true;
        foreach($roles as $entertainer_id => $entertainer_roles) {
            $roles_str = implode(',', $entertainer_roles);
            $perform_durations_str = '';
            foreach($entertainer_roles as $key => $role) {
                $perform_durations_str .= $role . ': ' . $perform_durations[$entertainer_id][$key];
                if($key < count($entertainer_roles) - 1) {
                    $perform_durations_str .= ', ';
                }
            }
            $entertainer_stmt->bind_param("iiss", $booking_id, $entertainer_id, $roles_str, $perform_durations_str);
            if (!$entertainer_stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        // Insert into customer_details
        $customer_query = "INSERT INTO customer_details (booking_id, first_name, last_name, contact_number, 
                                                       event_street, event_barangay, event_municipality, event_province) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $customer_stmt = $conn->prepare($customer_query);
        $customer_stmt->bind_param("isssssss", 
            $booking_id, $first_name, $last_name, $contact_number,
            $street, $barangay, $municipality, $province
        );
        
        if ($success && $customer_stmt->execute()) {
            $conn->commit();
            echo "<script>
                alert('Booking added successfully!');
                window.location.href = 'admin-appointments.php';
            </script>";
        } else {
            $conn->rollback();
            echo "<script>
                alert('Error: Failed to save booking. Please try again.');
            </script>";
        }
    } else {
        $conn->rollback();
        echo "<script>
            alert('Error: Failed to save booking. Please try again.');
        </script>";
    }
}

$entertainerRolesQuery = "SELECT entertainer_id, first_name, last_name, roles FROM entertainer_account WHERE status = 'Active'";
$rolesResult = $conn->query($entertainerRolesQuery);
$entertainerRoles = array();
while($row = $rolesResult->fetch_assoc()) {
    $entertainerRoles[$row['entertainer_id']] = [
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'roles' => $row['roles']
    ];
}

$rolesQuery = "SELECT * FROM roles";
$rolesResult = $conn->query($rolesQuery);
$rolesData = array();
while($row = $rolesResult->fetch_assoc()) {
    // Convert role names to lowercase for case-insensitive matching
    $rolesData[strtolower($row['role_name'])] = array(
        'rate' => $row['rate'],
        'duration' => $row['duration'],
        'duration_unit' => $row['duration_unit']
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking</title>
  <!-- Add Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            position: relative;
        }

        label:has(+ [required])::after,
        label:has(~ .entertainer-checkbox-container)::after,
        label.required::after {
            content: "*";
            color: red;
            margin-left: 3px;
        }
        
        label.no-asterisk::after {
            content: none !important;
        }

        select, input[type="date"], input[type="time"], input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        .time-inputs {
            display: flex;
            gap: 20px;
        }

        .time-inputs .form-group {
            flex: 1;
        }

        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .cancel-btn:hover {
            background-color: #da190b;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .venue-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .venue-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1em;
        }

        .venue-section select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .venue-section select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .entertainer-checkbox-container {
            background-color: #fff;
        }
        .entertainer-checkbox-container .form-check {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .entertainer-checkbox-container .form-check-input {
            margin: 0;
            cursor: pointer;
        }
        .entertainer-checkbox-container .form-check-label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        .form-check-input {
            cursor: pointer;
        }


        .entertainer-box {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
    padding: 15px;
    background-color: #f9f9f9;
}

.entertainer-name {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 10px;
    color: #333;
    padding-bottom: 5px;
    border-bottom: 2px solid #ddd;
}

.role-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 5px;
}

.role-checkbox-container {
    display: flex;
    align-items: center;
    background-color: white;
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.role-checkbox {
    margin-right: 5px;
}

#entertainerRole {
    margin-top: 10px;
}

.no-entertainer-message {
    text-align: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
}

.price-calculation-table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
    }

    .price-calculation-table th,
    .price-calculation-table td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .price-calculation-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .price-calculation-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    .price-calculation-table td {
    vertical-align: middle;
}

    .duration-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.duration-input {
    width: 70px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.duration-unit {
    color: #666;
    font-size: 0.9em;
}



    .rate-input[readonly] {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .entertainer-total {
        font-weight: bold;
    }


    .package-details-container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            margin-top: 10px;
        }

        .package-info {
            text-align: center;
        }

        .package-name {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3em;
            font-weight: bold;
        }

        .package-roles ul {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .package-roles li {
            display: inline-block;
            margin: 5px;
            padding: 5px 10px;
            background-color: #e8f4f8;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .package-pricing {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .package-price {
            font-size: 1.5em;
            color: #27ae60;
            margin: 10px 0;
        }

        .package-savings {
            color: #e74c3c;
            font-weight: bold;
        }

        .no-roles-message,
        .loading-message,
        .no-package-message,
        .error-message {
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .no-roles-message {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .loading-message {
            background-color: #e8f4f8;
            color: #2c3e50;
        }

        .error-message {
            background-color: #fdf2f2;
            color: #dc3545;
        }

        .selected-roles {
            margin: 15px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            border: 1px dashed #ddd;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h2>Add New Booking</h2>
        <form id="appointment-form" method="POST" action="save_booking.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="tel" name="contact_number" id="contact_number" pattern="^(09|\+639)\d{9}$" 
                       class="form-control" required 
                       placeholder="Enter mobile number (e.g., 09123456789 or +639123456789)"
                       oninput="validateContactNumber(this)"
                       title="Please enter a valid Philippine mobile number starting with 09 or +639 followed by 9 digits">
                <small class="text-muted">Format: 09XXXXXXXXX or +639XXXXXXXXX</small>
                <div id="contact_number_error" class="invalid-feedback"></div>
            </div>

            <div class="venue-section">
                <h3>Event Venue Information</h3>
                <div class="form-group">
                    <label for="event_province">Province:</label>
                    <select name="event_province" id="event_province" required>
                        <option value="">Select Province</option>
                        <?php
                        $mindanao_provinces = array(
                            'Agusan del Norte',
                            'Agusan del Sur',
                            'Basilan',
                            'Bukidnon',
                            'Camiguin',
                            'Davao de Oro',
                            'Davao del Norte',
                            'Davao del Sur',
                            'Davao Occidental',
                            'Davao Oriental',
                            'Dinagat Islands',
                            'Lanao del Norte',
                            'Lanao del Sur',
                            'Maguindanao',
                            'Misamis Occidental',
                            'Misamis Oriental',
                            'North Cotabato',
                            'Sarangani',
                            'South Cotabato',
                            'Sultan Kudarat',
                            'Sulu',
                            'Surigao del Norte',
                            'Surigao del Sur',
                            'Tawi-Tawi',
                            'Zamboanga del Norte',
                            'Zamboanga del Sur',
                            'Zamboanga Sibugay'
                        );
                        sort($mindanao_provinces);
                        foreach($mindanao_provinces as $province) {
                            echo '<option value="'.$province.'">'.$province.'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_municipality">Municipality/City:</label>
                    <select name="event_municipality" id="event_municipality" required disabled>
                        <option value="">Select Municipality/City</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_barangay">Barangay:</label>
                    <select name="event_barangay" id="event_barangay" required disabled>
                        <option value="">Select Barangay</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_street">Street/Purok:</label>
                    <input type="text" name="event_street" id="event_street" required placeholder="Enter Street/Purok">
                </div>
            </div>

            <div class="form-group">
                <label>Select Entertainer(s):</label>
                <div class="entertainer-checkbox-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <?php
                    function capitalizeWords($str) {
                        // Split into words
                        $words = explode(' ', strtolower($str));
                        // Capitalize each word
                        $words = array_map('ucfirst', $words);
                        // Join back together
                        return implode(' ', $words);
                    }

                    $query = "SELECT 
                        entertainer_id,
                        first_name,
                        last_name,
                        title
                        FROM entertainer_account 
                        WHERE status = 'Active'
                        ORDER BY first_name, last_name";
                    
                    $result = $conn->query($query);
                    while($row = $result->fetch_assoc()) {
                        // Properly capitalize each part
                        $firstName = capitalizeWords($row['first_name']);
                        $lastName = capitalizeWords($row['last_name']);
                        $title = capitalizeWords($row['title']);
                        
                        // Format the full name with title
                        $formattedName = $firstName . ' ' . $lastName . ' (' . $title . ')';
                        
                        echo '<div class="form-check" style="display: flex; align-items: center; gap: 8px;">';
                        echo '<input class="form-check-input entertainer-checkbox" type="checkbox" name="entertainer_id[]" value="'.$row['entertainer_id'].'" id="entertainer_'.$row['entertainer_id'].'" style="margin: 0;">';
                        echo '<label class="form-check-label" for="entertainer_'.$row['entertainer_id'].'" style="margin: 0;">'.$formattedName.'</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="mt-2">
                    <small class="text-muted">Select one or more entertainers</small>
                </div>
            </div>

            <div class="form-group">
                <label for="date_schedule">Date Schedule:</label>
                <input type="date" name="date_schedule" id="date_schedule" required>
            </div>

            <div class="time-inputs">
                <div class="form-group">
                    <label for="time_start">Start Time:</label>
                    <input type="time" name="time_start" id="time_start" required>
                </div>

                <div class="form-group">
                    <label for="time_end">End Time:</label>
                    <input type="time" name="time_end" id="time_end" required>
                </div>
            </div>

            <div class="form-group">
                <label class="required">Entertainer Role:</label>
                <span id="entertainerRole">No entertainer selected</span>
            </div>

            <div class="form-group">
                <label for="price_method">Select Price Method:</label>
                <select name="price_method" id="price_method" class="form-control" required>
                    <option value="">Choose a price method</option>
                    <option value="custom">Custom Price</option>
                    <option value="package">Package</option>
                </select>
            </div>

            <div id="package-section" class="form-group" style="display: none;">
                <label>Package Details:</label>
                <div id="package-details" class="package-details-container">
                    <p>Select roles to see available packages</p>
                </div>
            </div>

            <div class="form-group" id="custom-price-section" style="display: none;">
                <label>Custom Price Details:</label>
                <div class="table-responsive">
                    <table class="price-calculation-table" style="width: 100%; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Entertainer</th>
                                <th>Selected Role</th>
                                <th>Rate (₱)</th>
                                <th>Duration</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="price-calculation-body">
                            <!-- Dynamic content will be inserted here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right;"><strong>Grand Total:</strong></td>
                                <td id="grand-total">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="form-group">
                <label class="no-asterisk" for="total_price">Total Price:</label>
                <input type="number" name="total_price" id="total_price" required readonly>
            </div>

            <div class="form-group">
                <label for="down_payment">Down Payment:</label>
                <input type="number" name="down_payment" id="down_payment" required step="0.01">
            </div>

            <div class="form-group">
                <label for="remaining_balance">Remaining Balance:</label>
                <input type="number" name="remaining_balance" id="remaining_balance" readonly step="0.01">
            </div>

            <div class="form-group">
                <label for="payment_image">Payment Image:</label>
                <input type="file" name="payment_image" id="payment_image" required accept="image/*">
            </div>

            <input type="hidden" name="package_name" id="package_name" value="">

            <input type="hidden" name="selected_roles" id="selected_roles" value="">
            <input type="hidden" name="selected_durations" id="selected_durations" value="">

            <div class="button-group">
                <button type="submit" class="submit-btn">Add Booking</button>
                <a href="admin-appointments.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>


<!-- Availability Check Modal -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="availabilityModalLabel">Entertainer Availability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="availabilityMessage">
                    <!-- Availability results will be dynamically inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        
        // Province dropdown change event
        $('#event_province').change(function() {
            var province = $(this).val();
            if(province != '') {
                loadMunicipalities(province);
                $('#event_municipality').prop('disabled', false);
                $('#event_barangay').prop('disabled', true);
                $('#event_street').prop('disabled', false);
            } else {
                $('#event_municipality').prop('disabled', true);
                $('#event_barangay').prop('disabled', true);
                $('#event_street').prop('disabled', true);
            }
        });

        // Municipality/City dropdown change event
        $('#event_municipality').change(function() {
            var province = $('#event_province').val();
            var municipality = $(this).val();
            if(municipality != '') {
                loadBarangays(province, municipality);
                $('#event_barangay').prop('disabled', false);
                $('#event_street').prop('disabled', false);
            } else {
                $('#event_barangay').prop('disabled', true);
                $('#event_street').prop('disabled', true);
            }
        });

        // Barangay dropdown change event
        $('#event_barangay').change(function() {
            if($(this).val() != '') {
                $('#event_street').prop('disabled', false);
            } else {
                $('#event_street').prop('disabled', true);
            }
        });

        // Function to load municipalities
        function loadMunicipalities(province) {
            $.ajax({
                url: 'get_locations.php',
                method: 'POST',
                data: {province: province},
                dataType: 'json',
                success: function(data) {
                    var html = '<option value="">Select Municipality/City</option>';
                    for(var count = 0; count < data.length; count++) {
                        html += '<option value="'+data[count]+'">'+data[count]+'</option>';
                    }
                    $('#event_municipality').html(html);
                }
            });
        }

        // Function to load barangays
        function loadBarangays(province, municipality) {
            $.ajax({
                url: 'get_locations.php',
                method: 'POST',
                data: {province: province, municipality: municipality},
                dataType: 'json',
                success: function(data) {
                    var html = '<option value="">Select Barangay</option>';
                    for(var count = 0; count < data.length; count++) {
                        html += '<option value="'+data[count]+'">'+data[count]+'</option>';
                    }
                    $('#event_barangay').html(html);
                }
            });
        }

      

        function checkAvailability() {
    var selectedDate = $('#date_schedule').val();
    var selectedTimeStart = $('#time_start').val();
    var selectedTimeEnd = $('#time_end').val();
    var selectedEntertainers = $('.entertainer-checkbox:checked').map(function() {
        return {
            id: $(this).val(),
            name: $(this).next('label').text()
        };
    }).get();

    if (selectedDate && selectedTimeStart && selectedTimeEnd && selectedEntertainers.length > 0) {
        $.ajax({
            url: 'check_availability.php',
            method: 'POST',
            data: {
                date: selectedDate,
                time_start: selectedTimeStart,
                time_end: selectedTimeEnd,
                entertainers: selectedEntertainers.map(e => e.id)
            },
            dataType: 'json',
            success: function(response) {
                // Check if response has the expected structure
                if (!response || !response.data) {
                    console.error('Invalid response format:', response);
                    showErrorMessage('Invalid response format from server');
                    return;
                }

                var html = '<div class="availability-results">';
                var allAvailable = true;

                // Use response.data array
                response.data.forEach(function(entertainer) {
                    var icon = entertainer.available ? 
                        '<i class="fas fa-check-circle text-success"></i>' : 
                        '<i class="fas fa-times-circle text-danger"></i>';
                    var status = entertainer.available ? 
                        '<span class="text-success">Available</span>' : 
                        '<span class="text-danger">Not Available</span>';
                    
                    html += `
                        <div class="availability-entry">
                            <p>
                                ${icon} ${entertainer.name}: ${status}
                                ${!entertainer.available ? '<br><small class="text-muted">This entertainer has a conflicting booking on the selected date and time.</small>' : ''}
                            </p>
                        </div>
                    `;
                    
                    if (!entertainer.available) allAvailable = false;
                });

                html += '</div>';
                
                if (!allAvailable) {
                    html += `
                        <div class="alert alert-warning mt-3">
                            Warning: Some entertainers are not available on the selected date and time. 
                            Please choose a different date, time, or entertainers.
                        </div>
                    `;
                }

                $('#availabilityMessage').html(html);
                var myModal = new bootstrap.Modal(document.getElementById('availabilityModal'));
                myModal.show();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                let errorMessage = 'Error checking availability';
                
                // Try to parse the response as JSON
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // If we can't parse as JSON, use the raw response text
                    // but clean it up first by removing any HTML tags
                    const div = document.createElement('div');
                    div.innerHTML = xhr.responseText;
                    errorMessage = div.textContent || div.innerText || error;
                }
                
                showErrorMessage(errorMessage);
            }
        });
    }
}

function showErrorMessage(message) {
    $('#availabilityMessage').html(`
        <div class="alert alert-danger">
            <h5>Error</h5>
            <p>${message}</p>
        </div>
    `);
    var myModal = new bootstrap.Modal(document.getElementById('availabilityModal'));
    myModal.show();
}

// Keep your existing event listeners
$('#date_schedule, #time_start, #time_end').change(function() {
    var selectedEntertainers = $('.entertainer-checkbox:checked');
    if (selectedEntertainers.length > 0) {
        checkAvailability();
    }
});

$('.entertainer-checkbox').change(function() {
    var selectedDate = $('#date_schedule').val();
    var selectedTimeStart = $('#time_start').val();
    var selectedTimeEnd = $('#time_end').val();
    
    if (selectedDate && selectedTimeStart && selectedTimeEnd) {
        checkAvailability();
    }
});


        // Form validation before submit
        $('#appointment-form').on('submit', function(e) {
            e.preventDefault();
            
            // Get all selected entertainers and their roles
            const selectedRolesData = {};
            const selectedDurationsData = {};
            
            // For custom pricing, get roles from the price calculation table
            $('.price-calculation-table tbody tr').each(function() {
                const entertainerId = $(this).data('entertainer-id');
                const role = $(this).find('td:nth-child(2)').text().trim();
                const duration = $(this).find('.duration-input').val();
                const durationUnit = $(this).find('.duration-unit').text().trim();
                
                if (role && duration) {
                    if (!selectedRolesData[entertainerId]) {
                        selectedRolesData[entertainerId] = [];
                        selectedDurationsData[entertainerId] = [];
                    }
                    selectedRolesData[entertainerId].push(role);
                    selectedDurationsData[entertainerId].push(duration + ' ' + durationUnit);
                }
            });
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Remove any existing selected_roles from formData
            formData.delete('selected_roles');
            formData.delete('selected_durations');
            
            // Add the selected roles and durations data
            formData.append('selected_roles', JSON.stringify(selectedRolesData));
            formData.append('selected_durations', JSON.stringify(selectedDurationsData));
            
            // Show loading state
            $('.submit-btn').prop('disabled', true).text('Adding booking...');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        // Make sure we have a valid response object
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        
                        if (response.success) {
                            alert('Booking added successfully!');
                            window.location.href = 'admin-appointments.php';
                        } else {
                            alert(response.error || 'Failed to add booking. Please try again.');
                            $('.submit-btn').prop('disabled', false).text('Add Booking');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Failed to add booking. Please try again.');
                        $('.submit-btn').prop('disabled', false).text('Add Booking');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    
                    let errorMessage = 'Failed to add booking. Please try again.';
                    
                    // Try to parse error message from response
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.error) {
                            errorMessage = response.error;
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    
                    alert(errorMessage);
                    $('.submit-btn').prop('disabled', false).text('Add Booking');
                }
            });
        });

        // Add validation class to required checkboxes
        $('.entertainer-checkbox').change(function() {
            if ($('.entertainer-checkbox:checked').length > 0) {
                $('.entertainer-checkbox').removeAttr('required');
            } else {
                $('.entertainer-checkbox').attr('required', 'required');
            }
        });
    });

    // Prevent selecting past dates
    const dateInput = document.getElementById('date_schedule');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);

    // Validate time inputs
    document.querySelector('form').addEventListener('submit', function(e) {
        const startTime = document.getElementById('time_start').value;
        const endTime = document.getElementById('time_end').value;
        const appointmentDate = document.getElementById('date_schedule').value;
        
        // Create Date objects for comparison
        const startDateTime = new Date(appointmentDate + ' ' + startTime);
        const endDateTime = new Date(appointmentDate + ' ' + endTime);
        const now = new Date();

        // Check if date is in the past
        if (startDateTime < now) {
            e.preventDefault();
            alert('Cannot schedule appointments in the past');
            return;
        }
        
        // Check if end time is after start time
        if (startDateTime >= endDateTime) {
            e.preventDefault();
            alert('End time must be later than start time');
            return;
        }
    });


    $(document).ready(function() {
    const entertainerRoles = <?php echo json_encode($entertainerRoles); ?>;
    
    function updateEntertainerRoles() {
        const selectedEntertainers = $('.entertainer-checkbox:checked');
        const roleDisplay = $('#entertainerRole');
        
        if (selectedEntertainers.length === 0) {
            roleDisplay.html('<div class="no-entertainer-message">No entertainer selected</div>');
            return;
        }
        
        let html = '';
        selectedEntertainers.each(function() {
            const entertainerId = $(this).val();
            const entertainerData = entertainerRoles[entertainerId];
            
            if (entertainerData) {
                const roles = entertainerData.roles.split(',').filter(role => role.trim() !== '');
                
                html += `
                    <div class="entertainer-box">
                        <div class="entertainer-name">${entertainerData.name}</div>
                        <div class="role-options">
                `;
                
                roles.forEach(role => {
                    const roleId = `role_${entertainerId}_${role.trim().replace(/\s+/g, '_')}`;
                    html += `
                        <div class="role-checkbox-container">
                            <input type="checkbox" 
                                   id="${roleId}" 
                                   name="selected_roles[${entertainerId}][]" 
                                   value="${role.trim()}" 
                                   class="role-checkbox">
                            <label for="${roleId}">${role.trim()}</label>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
        });
        
        roleDisplay.html(html);
    }
    
    // Update roles when entertainer checkboxes are clicked
    $('.entertainer-checkbox').change(updateEntertainerRoles);
    
    // Initial update
    updateEntertainerRoles();
});


$(document).ready(function() {
    // Store roles data from PHP to JavaScript
    const rolesData = <?php echo json_encode($rolesData); ?>;
    
    $('#price_method').change(function() {
        const selectedMethod = $(this).val();
        const customPriceSection = $('#custom-price-section');
        
        if (selectedMethod === 'custom') {
            customPriceSection.show();
            updatePriceCalculationTable();
        } else {
            customPriceSection.hide();
        }
    });

    // Update price calculation table when entertainer selection or roles change
    $(document).on('change', '.entertainer-checkbox, .role-checkbox', function() {
        if ($('#price_method').val() === 'custom') {
            updatePriceCalculationTable();
        }
    });

    function formatDuration(duration, unit) {
        return `${duration} ${unit}${duration > 1 ? 's' : ''}`;
    }

    function updatePriceCalculationTable() {
        let tableHtml = `
            <table class="price-calculation-table" style="width: 100%; margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Entertainer</th>
                        <th>Selected Role</th>
                        <th>Rate (₱)</th>
                        <th>Duration</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        $('.entertainer-checkbox:checked').each(function() {
            const entertainerId = $(this).val();
            const entertainerName = $(this).next('label').text();
            const selectedRoles = $(`input[name="selected_roles[${entertainerId}][]"]:checked`);
            
            selectedRoles.each(function() {
                const role = $(this).val().trim();
                const roleLowerCase = role.toLowerCase();
                const rowId = `row_${entertainerId}_${role.replace(/\s+/g, '_')}`;
                
                // Get the exact role data from database
                const roleData = rolesData[roleLowerCase];
                
                if (roleData) {
                    tableHtml += `
                        <tr id="${rowId}" data-entertainer-id="${entertainerId}">
                            <td>${entertainerName}</td>
                            <td>${role}</td>
                            <td>
                                <input type="number" class="rate-input" 
                                       data-row="${rowId}" 
                                       value="${roleData.rate}" 
                                       min="0" step="0.01"
                                       readonly>
                            </td>
                            <td>
                                <div class="duration-container">
                                    <input type="number" class="duration-input" 
                                           data-row="${rowId}" 
                                           value="${roleData.duration}" 
                                           min="0.5" step="0.5">
                                    <span class="duration-unit">${roleData.duration_unit}${roleData.duration > 1 ? 's' : ''}</span>
                                </div>
                            </td>
                            <td class="entertainer-total">₱0.00</td>
                        </tr>
                    `;
                }
            });
        });
        
        tableHtml += `
                </tbody>
            </table>
        `;
        
        // Update the entire table content
        $('#custom-price-section .table-responsive').html(tableHtml);

        // Calculate initial totals
        calculateTotals();

        // Add event listeners for calculation
        $('.rate-input, .duration-input').on('input change', calculateTotals);
    }

    // Function to calculate default down payment (50% of total price)
function updateDownPayment() {
    const totalPrice = parseFloat($('#total_price').val()) || 0;
    const defaultDownPayment = totalPrice * 0.5;
    $('#down_payment').val(defaultDownPayment.toFixed(2));
    updateRemainingBalance(); // Update remaining balance after setting down payment
}

// Update down payment when total price changes
$('#total_price').on('input', updateDownPayment);

// Update remaining balance when down payment is manually changed
$('#down_payment').on('input', updateRemainingBalance);

function calculateTotals() {
    let grandTotal = 0;

    // Calculate total for each row
    $('.price-calculation-table tbody tr').each(function() {
        const row = $(this);
        const rate = parseFloat(row.find('.rate-input').val()) || 0;
        const duration = parseFloat(row.find('.duration-input').val()) || 0;
        const total = rate * duration;
        
        // Update duration unit text based on current duration value
        const durationInput = row.find('.duration-input');
        const unitSpan = row.find('.duration-unit');
        const unit = unitSpan.text().replace(/s$/, ''); // Remove 's' if present
        unitSpan.text(`${unit}${duration > 1 ? 's' : ''}`);
        
        row.find('.entertainer-total').text(`₱${total.toFixed(2)}`);
        grandTotal += total;
    });

    // Update total price field
    $('#total_price').val(grandTotal.toFixed(2));
    
    // Update down payment to 50% of new total
    updateDownPayment();
}

// Function to calculate remaining balance
function updateRemainingBalance() {
    const totalPrice = parseFloat($('#total_price').val()) || 0;
    const downPayment = parseFloat($('#down_payment').val()) || 0;
    const remainingBalance = totalPrice - downPayment;
    $('#remaining_balance').val(remainingBalance.toFixed(2));
}

// Update remaining balance when total price or down payment changes
$('#total_price, #down_payment').on('input', updateRemainingBalance);

// Function to check if selected roles match a package
function checkForMatchingPackage() {
    const selectedRoles = [];
    const selectedEntertainers = $('.entertainer-checkbox:checked').length;
    
    // Gather all selected roles across entertainers
    $('.role-checkbox:checked').each(function() {
        const roleValue = $(this).val().trim();
        if (roleValue && !selectedRoles.includes(roleValue)) {
            selectedRoles.push(roleValue);
        }
    });
    
    // Only proceed if roles are selected
    if (selectedRoles.length === 0) {
        $('#package-details').html(`
            <div class="package-info">
                <p class="no-roles-message">Please select roles to view available packages</p>
            </div>
        `);
        return;
    }
    
    // Show loading state
    $('#package-details').html(`
        <div class="package-info">
            <p class="loading-message">Checking available packages...</p>
        </div>
    `);
    
    // Ajax call to check for matching package
    $.ajax({
        url: 'check_package.php',
        method: 'POST',
        data: {
            roles: selectedRoles
        },
        success: function(response) {
            console.log('Package response:', response); // Debug log
            
            if (response.packages && response.packages.length > 0) {
                // Packages found - update UI with package information
                let packagesHtml = '<div class="package-container">';
                
                response.packages.forEach((package, index) => {
                    let packageDetails = '';
                    if (package.type === 'role') {
                        // Role package display
                        packageDetails = `
                            <div class="package-duration">
                                ${package.duration ? `<p><strong>Duration:</strong> ${package.duration} ${package.duration_unit}${package.duration > 1 ? 's' : ''}</p>` : ''}
                            </div>
                        `;
                    } else {
                        // Combo package display
                        packageDetails = `
                            <div class="package-roles">
                                <p><strong>Included Roles:</strong></p>
                                <ul>
                                    ${package.roles.map(role => `<li>${role}</li>`).join('')}
                                </ul>
                            </div>
                            <div class="package-pricing">
                                <p class="package-savings">You Save: ₱${parseFloat(package.savings).toFixed(2)}</p>
                            </div>
                        `;
                    }
                    
                    packagesHtml += `
                        <div class="package-option" data-price="${package.price}">
                            <h4 class="package-name">${package.package_name}</h4>
                            ${packageDetails}
                            <p class="package-price">Package Price: ₱${parseFloat(package.price).toFixed(2)}</p>
                            <button type="button" class="btn select-package-btn btn-primary" 
                                    data-price="${package.price}" 
                                    data-package-name="${package.package_name}">
                                Select Package
                            </button>
                        </div>
                    `;
                });
                
                packagesHtml += '</div>';
                $('#package-details').html(packagesHtml);
                
            } else {
                // No matching package found
                $('#package-details').html(`
                    <div class="package-info">
                        <h4>No Package Available</h4>
                        <div class="selected-roles">
                            <p><strong>Selected Roles:</strong></p>
                            <ul>
                                ${selectedRoles.map(role => `<li>${role}</li>`).join('')}
                            </ul>
                        </div>
                        <p class="no-package-message">No package matches these exact roles. Consider individual pricing.</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Package check error:', error);
            console.log('XHR:', xhr.responseText); // Add this to see the actual error
            $('#package-details').html(`
                <div class="package-info">
                    <p class="error-message">Error checking packages. Please try again.</p>
                </div>
            `);
        }
    });
}

// Remove any existing click handlers
$(document).off('click', '.select-package-btn');
    
// Add the click handler once
$(document).on('click', '.select-package-btn', function(e) {
    e.preventDefault();
    const btn = $(this);
    const packageOption = btn.closest('.package-option');
    const selectedPrice = btn.data('price');
    const packageName = btn.data('package-name');
    
    // Get roles from the package
    const selectedRolesData = {};
    const selectedDurationsData = {};
    const duration = packageOption.find('.package-duration').text().trim() || '1 hour';
    
    // Get the selected entertainers
    const selectedEntertainers = $('.entertainer-checkbox:checked');
    
    // Get roles from package details or selected checkboxes
    const packageRoles = [];
    packageOption.find('.package-roles li').each(function() {
        packageRoles.push($(this).text().trim());
    });
    
    // If no package roles found, use selected checkboxes
    const rolesToUse = packageRoles.length > 0 ? packageRoles : 
        $('.role-checkbox:checked').map(function() { return $(this).val().trim(); }).get();
    
    // Distribute roles among selected entertainers
    selectedEntertainers.each(function() {
        const entertainerId = $(this).val();
        
        // Get the roles selected for this entertainer
        const entertainerRoles = [];
        const entertainerDurations = [];
        
        // For package pricing, assign all package roles to each entertainer
        // or assign the roles that were checked for this specific entertainer
        if (packageRoles.length > 0) {
            // Use package roles for all entertainers
            packageRoles.forEach(role => {
                entertainerRoles.push(role);
                entertainerDurations.push(duration);
            });
        } else {
            // Use the roles that were specifically checked for this entertainer
            $(`input[name="selected_roles[${entertainerId}][]"]:checked`).each(function() {
                const role = $(this).val().trim();
                entertainerRoles.push(role);
                entertainerDurations.push(duration);
            });
        }
        
        // Only add if roles were selected
        if (entertainerRoles.length > 0) {
            selectedRolesData[entertainerId] = entertainerRoles;
            selectedDurationsData[entertainerId] = entertainerDurations;
        }
    });
    
    // Log the collected data
    console.log('Package Selected - Roles Data:', selectedRolesData);
    console.log('Package Selected - Durations Data:', selectedDurationsData);
    
    // Update hidden fields
    $('#selected_roles').val(JSON.stringify(selectedRolesData));
    $('#selected_durations').val(JSON.stringify(selectedDurationsData));
    $('#package_name').val(packageName);
    $('#total_price').val(selectedPrice);
    
    // Update UI
    $('.package-option').removeClass('active');
    $('.select-package-btn').removeClass('btn-success').addClass('btn-primary').text('Select Package');
    
    packageOption.addClass('active');
    btn.removeClass('btn-primary').addClass('btn-success').text('Selected');
    
    updateDownPayment();
    
    // Show success message
    alert('Package "' + packageName + '" selected! Price updated to ₱' + parseFloat(selectedPrice).toFixed(2));
});

// Update the price method change handler
$('#price_method').change(function() {
    const selectedMethod = $(this).val();
    $('#custom-price-section').toggle(selectedMethod === 'custom');
    $('#package-section').toggle(selectedMethod === 'package');
    
    if (selectedMethod === 'package') {
        checkForMatchingPackage();
    } else if (selectedMethod === 'custom') {
        updatePriceCalculationTable();
    }
});

// Add event listener for role checkbox changes
$(document).on('change', '.role-checkbox', function() {
    if ($('#price_method').val() === 'package') {
        checkForMatchingPackage();
    } else if ($('#price_method').val() === 'custom') {
        updatePriceCalculationTable();
    }
});
});

// Add contact number validation
function validateContactNumber(input) {
    const contactNumber = input.value.trim();
    const phoneRegex = /^(09|\+639)\d{9}$/;
    const errorDiv = document.getElementById('contact_number_error');
    
    if (!phoneRegex.test(contactNumber)) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        errorDiv.textContent = 'Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789)';
        return false;
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        errorDiv.textContent = '';
        return true;
    }
}

// Add form validation before submission
document.getElementById('appointment-form').addEventListener('submit', function(e) {
    const contactInput = document.getElementById('contact_number');
    if (!validateContactNumber(contactInput)) {
        e.preventDefault();
        alert('Please enter a valid contact number before submitting.');
    }
});
    </script>

    <style>
    .availability-results p {
        margin-bottom: 10px;
        font-size: 16px;
    }
    .availability-results i {
        margin-right: 8px;
    }
    .text-success {
        color: #28a745;
    }
    .text-danger {
        color: #dc3545;
    }
    .package-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: flex-start;
    }
    
    .package-option {
        flex: 0 1 calc(33.333% - 20px);
        min-width: 250px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        background-color: #fff;
        transition: all 0.3s ease;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }
    
    .package-option::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background-color: #007bff;
        transition: background-color 0.3s ease;
    }
    
    .package-option.active {
        border-color: #28a745;
        box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
    }
    
    .package-option.active::before {
        background-color: #28a745;
    }
    
    .package-option h4 {
        margin-top: 0;
        font-size: 1.1em;
        color: #007bff;
        transition: color 0.3s ease;
    }
    
    .package-option.active h4 {
        color: #28a745;
    }
    
    .package-roles ul {
        margin: 5px 0;
        padding-left: 20px;
    }
    
    .package-roles li {
        font-size: 0.9em;
        color: #666;
    }
    
    .package-pricing {
        margin: 10px 0;
        font-size: 0.9em;
        padding: 10px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .package-price {
        font-size: 1.5em;
        color: #27ae60;
        margin: 10px 0;
    }
    
    .package-savings {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .no-roles-message,
    .loading-message,
    .no-package-message,
    .error-message {
        padding: 15px;
        border-radius: 4px;
        margin: 10px 0;
    }
    
    .no-roles-message {
        background-color: #f8f9fa;
        color: #6c757d;
    }
    
    .loading-message {
        background-color: #e8f4f8;
        color: #2c3e50;
    }
    
    .error-message {
        background-color: #fdf2f2;
        color: #dc3545;
    }
    
    .selected-roles {
        margin: 15px 0;
        padding: 10px;
        background-color: #fff;
        border-radius: 4px;
        border: 1px dashed #ddd;
    }
    label.required::after {
        content: "*";
        color: red;
        margin-left: 3px;
    }
    label.no-asterisk:has(+ [required])::after {
        content: none;
    }
    label:has(+ [required])::after {
        content: "*";
        color: red;
        margin-left: 3px;
    }
    label.required::after {
        content: "*";
        color: red;
        margin-left: 3px;
    }
    </style>
</body>
</html>
