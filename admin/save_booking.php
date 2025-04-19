<?php
// Prevent any output before our JSON response
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();
include("db_connect.php");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    // Debug log the POST data
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'contact_number', 'event_street', 
                       'event_barangay', 'event_municipality', 'event_province', 
                       'date_schedule', 'time_start', 'time_end', 'total_price', 
                       'down_payment', 'price_method'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: " . $field);
        }
    }

    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $price_method = $_POST['price_method'];
    
    // Validate contact number format
    $contact_number = trim($contact_number);
    if (!preg_match('/^(09|\+639)\d{9}$/', $contact_number)) {
        throw new Exception("Invalid contact number format. Please use 09XXXXXXXXX or +639XXXXXXXXX format");
    }
    
    // Event Venue Information
    $street = $_POST['event_street'];
    $barangay = $_POST['event_barangay'];
    $municipality = $_POST['event_municipality'];
    $province = $_POST['event_province'];
    
    // Check if entertainer_id exists
    if (!isset($_POST['entertainer_id']) || !is_array($_POST['entertainer_id']) || empty($_POST['entertainer_id'])) {
        error_log("entertainer_id data: " . print_r($_POST['entertainer_id'] ?? 'not set', true));
        throw new Exception("No entertainer selected");
    }

    $entertainer_ids = $_POST['entertainer_id'];
    $date_schedule = $_POST['date_schedule'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $total_price = $_POST['total_price'];
    $down_payment = $_POST['down_payment'];
    $balance = $total_price - $down_payment;
    
    // Handle file upload for payment image
    $payment_image = '';
    if(isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] == 0) {
        $target_dir = "../images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["payment_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if(!move_uploaded_file($_FILES["payment_image"]["tmp_name"], $target_file)) {
            error_log("Failed to move uploaded file from " . $_FILES["payment_image"]["tmp_name"] . " to " . $target_file);
            throw new Exception("Failed to upload payment image");
        }
        $payment_image = $target_file;
    } else {
        error_log("Payment image error: " . print_r($_FILES['payment_image'], true));
        throw new Exception("Payment image is required");
    }

    // Start transaction
    $conn->begin_transaction();
    
    // Get entertainer details for all selected entertainers
    $entertainer_names = [];
    foreach ($entertainer_ids as $ent_id) {
        $entertainer_query = "SELECT CONCAT(first_name, ' ', last_name) as entertainer_name 
                             FROM entertainer_account 
                             WHERE entertainer_id = ?";
        $stmt_ent = $conn->prepare($entertainer_query);
        if (!$stmt_ent) {
            throw new Exception("Failed to prepare entertainer query: " . $conn->error);
        }
        
        $stmt_ent->bind_param("i", $ent_id);
        if (!$stmt_ent->execute()) {
            throw new Exception("Failed to execute entertainer query: " . $stmt_ent->error);
        }
        
        $result = $stmt_ent->get_result();
        $entertainer = $result->fetch_assoc();
        $entertainer_names[] = $entertainer['entertainer_name'];
    }
    
    // Join all entertainer names with commas
    $entertainer_name = implode(', ', $entertainer_names);

    // Handle roles and perform durations based on price method
    $roles_str = '';
    $perform_durations_str = '';
    $package = '';
    
    if ($price_method === 'package') {
        // Get package details
        $package = isset($_POST['package_name']) ? $_POST['package_name'] : '';
        
        // Get roles and durations from JSON
        $roles = json_decode($_POST['selected_roles'], true) ?: [];
        $perform_durations = json_decode($_POST['selected_durations'], true) ?: [];
        
        if (!empty($roles)) {
            $roles_str = implode(', ', array_filter($roles));
            $perform_durations_str = implode(', ', array_filter($perform_durations));
            
            // For debugging
            error_log("Package selected - Roles: " . print_r($roles, true));
            error_log("Package selected - Durations: " . print_r($perform_durations, true));
        }
    } else {
        // Handle custom price method
        $roles = json_decode($_POST['selected_roles'], true) ?: [];
        $perform_durations = json_decode($_POST['selected_durations'], true) ?: [];
        
        if (!empty($roles) && !empty($perform_durations)) {
            // Create an array to store role-duration pairs
            $role_duration_pairs = array();
            foreach ($roles as $key => $role) {
                if (isset($perform_durations[$key])) {
                    $role_duration_pairs[] = $role . ': ' . $perform_durations[$key];
                }
            }
            
            $roles_str = implode(', ', array_filter($roles));
            $perform_durations_str = implode(', ', $role_duration_pairs);
            
            // For debugging
            error_log("Custom price - Roles: " . print_r($roles, true));
            error_log("Custom price - Durations: " . print_r($perform_durations, true));
        }
    }

    // For debugging
    error_log("Final roles_str: " . $roles_str);
    error_log("Final perform_durations_str: " . $perform_durations_str);
    error_log("Package: " . $package);

    // Insert into booking_report
    $sql = "INSERT INTO booking_report (admin_id, customer_id, entertainer_id, first_name, last_name, 
            contact_number, street, barangay, municipality, province, date_schedule, time_start, time_end,
            entertainer_name, roles, perform_durations, package, total_price, down_payment, balance, payment_image, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $admin_id = 1; // Set this to the actual admin ID if available
    $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
    
    // Set status to 'pending' for new appointments
    $status = 'pending';
    
    // Debug logging for status
    error_log("Admin ID: " . $admin_id);
    error_log("Status being set to: " . $status);
    
    // Prepare and execute the SQL statement  
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare insert query: " . $conn->error);
    }
    
    error_log("Binding parameters with values:");
    error_log("admin_id: $admin_id");
    error_log("customer_id: $customer_id");
    error_log("entertainer_id: " . implode(', ', $entertainer_ids));
    error_log("first_name: $first_name");
    error_log("last_name: $last_name");
    error_log("contact_number: $contact_number");
    error_log("street: $street");
    error_log("barangay: $barangay");
    error_log("municipality: $municipality");
    error_log("province: $province");
    error_log("date_schedule: $date_schedule");
    error_log("time_start: $time_start");
    error_log("time_end: $time_end");
    error_log("entertainer_name: $entertainer_name");
    error_log("roles_str: $roles_str");
    error_log("perform_durations_str: $perform_durations_str");
    error_log("package: $package");
    error_log("total_price: $total_price");
    error_log("down_payment: $down_payment");
    error_log("balance: $balance");
    error_log("payment_image: $payment_image");
    error_log("status: $status");
    
    $stmt->bind_param("iiissssssssssssssdddss", 
        $admin_id, 
        $customer_id, 
        $entertainer_ids[0],
        $first_name, 
        $last_name,
        $contact_number,
        $street,
        $barangay,
        $municipality,
        $province,
        $date_schedule,
        $time_start,
        $time_end,
        $entertainer_name,
        $roles_str,
        $perform_durations_str,
        $package,
        $total_price,
        $down_payment,
        $balance,
        $payment_image,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save booking: " . $stmt->error);
    }

    // Get the booking ID
    $booking_id = $conn->insert_id;

    // Get the selected roles and durations from POST data
    $selected_roles_json = $_POST['selected_roles'];
    $selected_durations_json = $_POST['selected_durations'];
    
    $selected_roles = json_decode($selected_roles_json, true);
    $selected_durations = json_decode($selected_durations_json, true);

    if (!$selected_roles) {
        throw new Exception("No roles were selected for the entertainers");
    }

    // Insert into booking_entertainers table for each entertainer
    foreach ($entertainer_ids as $entertainer_id) {
        // Get roles and durations for this entertainer
        $entertainer_roles = isset($selected_roles[$entertainer_id]) ? $selected_roles[$entertainer_id] : [];
        $entertainer_durations = isset($selected_durations[$entertainer_id]) ? $selected_durations[$entertainer_id] : [];
        
        if (empty($entertainer_roles)) {
            throw new Exception("No roles selected for entertainer ID: " . $entertainer_id);
        }

        // Convert roles array to string
        $roles_str = implode(', ', $entertainer_roles);
        
        // Create duration string that matches each role with its duration
        $duration_parts = [];
        foreach ($entertainer_roles as $index => $role) {
            $duration = isset($entertainer_durations[$index]) ? $entertainer_durations[$index] : '1 hour';
            $duration_parts[] = $role . ': ' . $duration;
        }
        $duration_str = implode(', ', $duration_parts);

        // Insert into booking_entertainers table
        $entertainer_sql = "INSERT INTO booking_entertainers (book_id, entertainer_id, roles, perform_durations) 
                           VALUES (?, ?, ?, ?)";
        $stmt_ent = $conn->prepare($entertainer_sql);
        if (!$stmt_ent) {
            throw new Exception("Failed to prepare entertainer insert query: " . $conn->error);
        }
        
        $stmt_ent->bind_param("iiss", $booking_id, $entertainer_id, $roles_str, $duration_str);
        if (!$stmt_ent->execute()) {
            throw new Exception("Failed to save entertainer booking: " . $stmt_ent->error);
        }
    }

    // Commit transaction
    $conn->commit();
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Booking added successfully',
        'booking_id' => $booking_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
