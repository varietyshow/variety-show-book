<?php
session_start();

// Add this function after your session_start()
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php");
    exit();
}

// Get the book_id from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$book_id) {
    echo "No appointment ID provided";
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'db_booking_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Debug log
    error_log("Fetching details for booking ID: " . $book_id);
    
    // First, get the package type (role_package or combo_package)
    $package_query = $pdo->prepare("
        SELECT 
            package,
            CASE 
                WHEN EXISTS (SELECT 1 FROM role_packages WHERE package_id = booking_report.package) THEN 'role'
                WHEN EXISTS (SELECT 1 FROM combo_packages WHERE combo_id = booking_report.package) THEN 'combo'
                ELSE NULL
            END as package_type
        FROM booking_report 
        WHERE book_id = ?
    ");
    $package_query->execute([$book_id]);
    $package_info = $package_query->fetch(PDO::FETCH_ASSOC);
    
    error_log("Package info: " . print_r($package_info, true));
    
    // Get the package name directly from the appropriate table or use the package field if it's a string
    $package_name = null;
    if (!empty($package_info['package'])) {
        // Check if the package field contains a name directly (string) instead of an ID (number)
        if (!is_numeric($package_info['package'])) {
            // If it's a string, use it directly as the package name
            $package_name = $package_info['package'];
            error_log("Using package field directly as name: " . $package_name);
        } else {
            // If it's a number, try to look up the package name
            if ($package_info['package_type'] === 'role') {
                $name_query = $pdo->prepare("SELECT package_name FROM role_packages WHERE package_id = ?");
                $name_query->execute([$package_info['package']]);
                $name_result = $name_query->fetch(PDO::FETCH_ASSOC);
                if ($name_result) {
                    $package_name = $name_result['package_name'];
                }
            } elseif ($package_info['package_type'] === 'combo') {
                $name_query = $pdo->prepare("SELECT package_name FROM combo_packages WHERE combo_id = ?");
                $name_query->execute([$package_info['package']]);
                $name_result = $name_query->fetch(PDO::FETCH_ASSOC);
                if ($name_result) {
                    $package_name = $name_result['package_name'];
                }
            }
        }
        
        error_log("Found package name: " . ($package_name ?? 'Not found'));
    }
    
    // Fetch appointment details with package information
    $stmt = $pdo->prepare("
        SELECT 
            br.*,
            CASE 
                WHEN ? = 'role' THEN (SELECT package_name FROM role_packages WHERE package_id = br.package)
                WHEN ? = 'combo' THEN (SELECT package_name FROM combo_packages WHERE combo_id = br.package)
                ELSE br.package
            END as package_name
        FROM booking_report br
        WHERE br.book_id = ?
    ");
    $stmt->execute([$package_info['package_type'], $package_info['package_type'], $book_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Appointment details: " . print_r($appointment, true));

    if (!$appointment) {
        echo "Appointment not found";
        exit();
    }

    // Fetch package details based on package type
    $package_details = [];
    $package_roles = [];
    
    if (!empty($package_info['package'])) {
        error_log("Package ID: {$package_info['package']}, Type: {$package_info['package_type']}");
        
        // For combo packages, get the roles and assigned entertainers
        if ($package_info['package_type'] === 'combo') {
            $package_details_query = $pdo->prepare("
                SELECT 
                    cp.package_name,
                    r.role_name,
                    r.role_id,
                    cpr.entertainer_id,
                    CONCAT(ea.first_name, ' ', ea.last_name) as entertainer_name,
                    CONCAT(r.duration, ' ', r.duration_unit) as duration
                FROM combo_packages cp
                JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
                JOIN roles r ON cpr.role_id = r.role_id
                LEFT JOIN entertainer_account ea ON cpr.entertainer_id = ea.entertainer_id
                WHERE cp.combo_id = ?
            ");
            $package_details_query->execute([$package_info['package']]);
            $package_details = $package_details_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Create a mapping of roles to use later
            foreach ($package_details as $detail) {
                $package_roles[$detail['role_id']] = $detail['role_name'];
            }
            
            error_log("Combo package details: " . print_r($package_details, true));
        } 
        // For role packages, get the role details
        elseif ($package_info['package_type'] === 'role') {
            $package_details_query = $pdo->prepare("
                SELECT 
                    rp.package_name,
                    r.role_name,
                    r.role_id,
                    CONCAT(rp.package_duration, ' ', rp.package_duration_unit) as duration
                FROM role_packages rp
                JOIN roles r ON rp.role_id = r.role_id
                WHERE rp.package_id = ?
            ");
            $package_details_query->execute([$package_info['package']]);
            $package_details = $package_details_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Create a mapping of roles to use later
            foreach ($package_details as $detail) {
                $package_roles[$detail['role_id']] = $detail['role_name'];
            }
            
            error_log("Role package details: " . print_r($package_details, true));
        } 
        // If package type is not identified, try a direct lookup
        else {
            error_log("Package type not identified, trying direct lookup");
            
            // Try combo packages first
            $combo_query = $pdo->prepare("
                SELECT 
                    cp.combo_id, 
                    cp.package_name,
                    'combo' as package_type
                FROM combo_packages cp 
                WHERE cp.combo_id = ?
            ");
            $combo_query->execute([$package_info['package']]);
            $combo_result = $combo_query->fetch(PDO::FETCH_ASSOC);
            
            if ($combo_result) {
                $package_details[] = [
                    'package_name' => $combo_result['package_name'],
                    'role_name' => 'Performer',
                    'package_type' => 'combo'
                ];
                error_log("Found combo package directly: " . $combo_result['package_name']);
            } else {
                // Try role packages
                $role_query = $pdo->prepare("
                    SELECT 
                        rp.package_id, 
                        rp.package_name,
                        'role' as package_type
                    FROM role_packages rp 
                    WHERE rp.package_id = ?
                ");
                $role_query->execute([$package_info['package']]);
                $role_result = $role_query->fetch(PDO::FETCH_ASSOC);
                
                if ($role_result) {
                    $package_details[] = [
                        'package_name' => $role_result['package_name'],
                        'role_name' => 'Performer',
                        'package_type' => 'role'
                    ];
                    error_log("Found role package directly: " . $role_result['package_name']);
                }
            }
        }
    }
    
    // Fetch entertainers for this booking
    $entertainers = [];
    if (!empty($appointment['entertainer_name'])) {
        // Split the entertainer names if there are multiple
        $entertainer_names = explode(',', $appointment['entertainer_name']);
        
        foreach ($entertainer_names as $name) {
            $name = trim($name);
            // Try to find the entertainer in the database
            $entertainer_query = $pdo->prepare("
                SELECT 
                    entertainer_id, 
                    first_name, 
                    last_name, 
                    title as role
                FROM entertainer_account 
                WHERE CONCAT(first_name, ' ', last_name) = ? OR
                      CONCAT(last_name, ' ', first_name) = ?
            ");
            $entertainer_query->execute([$name, $name]);
            $entertainer = $entertainer_query->fetch(PDO::FETCH_ASSOC);
            
            if ($entertainer) {
                $entertainers[] = $entertainer;
            } else {
                // If not found, create a placeholder entry
                $name_parts = explode(' ', $name, 2);
                $first_name = $name_parts[0] ?? $name;
                $last_name = $name_parts[1] ?? '';
                $entertainers[] = [
                    'entertainer_id' => 0,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'role' => ''
                ];
            }
        }
    }
    
    // If no entertainers were found but we have a package, create a default entertainer
    // Force creation of default entertainer for debugging
    error_log("Checking for entertainers: " . count($entertainers) . ", Package name: " . ($package_name ?? 'None'));
    
    // Always create a default entertainer for packages for now
    if (!empty($package_name)) {
        error_log("Creating default entertainer for package: " . $package_name);
        $entertainers[] = [
            'entertainer_id' => 0,
            'first_name' => 'Package',
            'last_name' => 'Entertainer',
            'role' => 'Performer'
        ];
        
        // Also create entertainers from the entertainer_name field if it exists
        if (!empty($appointment['entertainer_name'])) {
            $names = explode(',', $appointment['entertainer_name']);
            foreach ($names as $name) {
                $name = trim($name);
                if (!empty($name)) {
                    $name_parts = explode(' ', $name, 2);
                    $first_name = $name_parts[0] ?? $name;
                    $last_name = $name_parts[1] ?? '';
                    $entertainers[] = [
                        'entertainer_id' => 0,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'role' => 'Performer'
                    ];
                    error_log("Added entertainer from name: " . $name);
                }
            }
        }
    }
    
    error_log("Final entertainers array: " . print_r($entertainers, true));
    
    error_log("Package details: " . print_r($package_details ?? [], true));

    // Fetch entertainer details
    $stmt_entertainers = $pdo->prepare("
        SELECT 
            be.*,
            ea.first_name,
            ea.last_name,
            be.roles,
            be.perform_durations
        FROM booking_entertainers be
        JOIN entertainer_account ea ON be.entertainer_id = ea.entertainer_id
        WHERE be.book_id = ?
    ");
    $stmt_entertainers->execute([$book_id]);
    $entertainers = $stmt_entertainers->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Entertainers: " . print_r($entertainers, true));
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Database error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details</title>
    <link rel="stylesheet" href="style2.css">
    <style>
        .details-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .details-section {
            margin-bottom: 20px;
        }

        .details-section h3 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .details-table th,
        .details-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .details-table th {
            background-color: #f8f8f8;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-declined { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #383d41; }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        .payment-proof {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .payment-proof h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .payment-proof img {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .payment-proof img {
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .payment-proof img:hover {
            opacity: 0.8;
        }



        .payment-info {
            margin-bottom: 20px;
        }
        
        .payment-info p {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .payment-info .amount {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<div class="details-container">
        <a href="customer-appointment.php" class="back-button">← Back to Appointments</a>
        
        <div class="details-section">
            <h3>Customer Information</h3>
            <p>Name: <?php echo htmlspecialchars(ucfirst($appointment['first_name']) . ' ' . ucfirst($appointment['last_name'])); ?></p>
            <p>Contact Number: <?php echo htmlspecialchars($appointment['contact_number']); ?></p>
        </div>

        <div class="details-section">
            <h3>Event Details</h3>
            <p>Date: <?php echo htmlspecialchars($appointment['date_schedule']); ?></p>
            <p>Time: <?php echo formatTime($appointment['time_start']) . ' - ' . formatTime($appointment['time_end']); ?></p>
            <p>Venue: <?php 
                echo htmlspecialchars(ucfirst($appointment['street'])) . ', ' .
                     htmlspecialchars(ucfirst($appointment['barangay'])) . ', ' .
                     htmlspecialchars(ucfirst($appointment['municipality'])) . ', ' .
                     htmlspecialchars(ucfirst($appointment['province']));
            ?></p>
            <p>Entertainers: <?php echo htmlspecialchars(ucfirst($appointment['entertainer_name'])); ?></p>
        </div>

        <div class="details-section">
            <h3>Entertainment Details</h3>
            
            <?php 
            // Debug information
            error_log("Package check: " . (isset($appointment['package']) ? "Package exists: {$appointment['package']}" : "No package"));
            error_log("Entertainers: " . print_r($entertainers, true));
            
            // Check if this is a package booking
            $has_package = !empty($appointment['package']);
            
            // Always show package information for this appointment if we have a package name
            error_log("Package value: " . var_export($appointment['package'], true));
            error_log("Package details: " . var_export($package_details, true));
            error_log("Package name for display: " . ($package_name ?? 'None'));
            
            // Get package name - use the one we fetched earlier or fallbacks
            if (empty($package_name)) {
                if (!empty($package_details) && isset($package_details[0]['package_name'])) {
                    $package_name = $package_details[0]['package_name'];
                } elseif (!empty($appointment['package_name'])) {
                    $package_name = $appointment['package_name'];
                } elseif (!empty($appointment['package'])) {
                    $package_name = $appointment['package'];
                } else {
                    $package_name = 'Standard Package';
                }
            }
            
            error_log("Final package name: " . $package_name);
            error_log("Entertainers for display: " . print_r($entertainers, true));
            
            if (!empty($package_name)) {
                // Get the entertainer name from the appointment if available
                $display_entertainer = 'Package Entertainer';
                if (!empty($appointment['entertainer_name'])) {
                    $display_entertainer = $appointment['entertainer_name'];
                }
                
                // Get all roles from the roles table for reference
                $roles_query = $pdo->prepare("SELECT role_id, role_name FROM roles");
                $roles_query->execute();
                $all_roles = $roles_query->fetchAll(PDO::FETCH_KEY_PAIR);
                error_log("All roles: " . print_r($all_roles, true));
                
                // Parse the entertainer_name field to extract entertainers and their roles
                $parsed_entertainers = [];
                
                // Check if we have entertainer_name in the appointment
                if (!empty($appointment['entertainer_name'])) {
                    $entertainer_string = $appointment['entertainer_name'];
                    error_log("Parsing entertainer string: " . $entertainer_string);
                    
                    // Debug the package ID and type
                    error_log("Trying to get roles for package ID: {$appointment['package']}, Package Type: {$package_info['package_type']}");
                    
                    // Get roles based on package type
                    $package_roles = [];
                    
                    // For combo packages
                    if ($package_info['package_type'] === 'combo') {
                        $package_roles_query = $pdo->prepare("
                            SELECT 
                                r.role_name,
                                r.role_id,
                                CONCAT(ea.first_name, ' ', ea.last_name) as entertainer_name
                            FROM combo_package_roles cpr
                            JOIN roles r ON cpr.role_id = r.role_id
                            LEFT JOIN entertainer_account ea ON cpr.entertainer_id = ea.entertainer_id
                            WHERE cpr.combo_id = ?
                            ORDER BY cpr.combo_role_id
                        ");
                        $package_roles_query->execute([$appointment['package']]);
                        $package_roles = $package_roles_query->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Combo package roles from DB: " . print_r($package_roles, true));
                    } 
                    // For role packages
                    elseif ($package_info['package_type'] === 'role') {
                        $package_roles_query = $pdo->prepare("
                            SELECT 
                                r.role_name,
                                r.role_id,
                                NULL as entertainer_name
                            FROM role_packages rp
                            JOIN roles r ON rp.role_id = r.role_id
                            WHERE rp.package_id = ?
                        ");
                        $package_roles_query->execute([$appointment['package']]);
                        $package_roles = $package_roles_query->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Role package roles from DB: " . print_r($package_roles, true));
                    }
                    // For packages stored as strings (direct package names)
                    else {
                        // Try to find roles by looking up the package name in both tables
                        $package_roles_query = $pdo->prepare("
                            SELECT 
                                r.role_name,
                                r.role_id,
                                NULL as entertainer_name
                            FROM roles r
                            WHERE r.role_name LIKE ?
                            LIMIT 1
                        ");
                        $package_roles_query->execute(['%' . $package_name . '%']);
                        $package_roles = $package_roles_query->fetchAll(PDO::FETCH_ASSOC);
                        
                        // If no roles found, get some default roles
                        if (empty($package_roles)) {
                            $package_roles_query = $pdo->prepare("SELECT role_id, role_name, NULL as entertainer_name FROM roles LIMIT 5");
                            $package_roles_query->execute();
                            $package_roles = $package_roles_query->fetchAll(PDO::FETCH_ASSOC);
                        }
                        
                        error_log("String package roles from DB: " . print_r($package_roles, true));
                    }
                    
                    // If we have package roles, use them to match with entertainers
                    if (!empty($package_roles)) {
                        // Create a mapping of entertainer names to their roles from the database
                        $role_map = [];
                        foreach ($package_roles as $role_info) {
                            if (!empty($role_info['entertainer_name'])) {
                                $role_map[$role_info['entertainer_name']] = $role_info['role_name'];
                            }
                        }
                        error_log("Role map from database: " . print_r($role_map, true));
                        
                        // Split the entertainer names
                        $entertainer_entries = explode(',', $entertainer_string);
                        
                        // First pass: Try to match exact names from the database
                        $matched_entertainers = [];
                        $unmatched_entries = [];
                        
                        foreach ($entertainer_entries as $entry) {
                            $name = trim($entry);
                            if (empty($name)) continue;
                            
                            // Check if the name contains a role in parentheses and extract it
                            if (preg_match('/(.+?)\((.+?)\)/', $name, $matches)) {
                                $clean_name = trim($matches[1]);
                                $role_in_parens = trim($matches[2]);
                            } else {
                                $clean_name = $name;
                                $role_in_parens = null;
                            }
                            
                            // Try to find this entertainer in our role map
                            $found_match = false;
                            foreach ($role_map as $db_name => $db_role) {
                                // Check for similarity in names (case insensitive)
                                if (stripos($clean_name, $db_name) !== false || stripos($db_name, $clean_name) !== false) {
                                    // Use the role from database, but prefer role in parentheses if available
                                    $role = $role_in_parens ?? $db_role;
                                    $matched_entertainers[] = [
                                        'name' => $clean_name,
                                        'role' => $role
                                    ];
                                    error_log("Matched entertainer {$clean_name} with role {$role} from database");
                                    $found_match = true;
                                    break;
                                }
                            }
                            
                            if (!$found_match) {
                                $unmatched_entries[] = [
                                    'name' => $clean_name,
                                    'role_in_parens' => $role_in_parens
                                ];
                            }
                        }
                        
                        // Second pass: For unmatched entertainers, assign roles from package_roles in order
                        for ($i = 0; $i < count($unmatched_entries); $i++) {
                            $entry = $unmatched_entries[$i];
                            
                            // Try to get a role from package_roles that hasn't been used yet
                            $role = 'Performer'; // Default fallback
                            foreach ($package_roles as $role_info) {
                                $role_name = $role_info['role_name'];
                                $already_used = false;
                                foreach ($matched_entertainers as $matched) {
                                    if ($matched['role'] == $role_name) {
                                        $already_used = true;
                                        break;
                                    }
                                }
                                
                                if (!$already_used) {
                                    $role = $role_name;
                                    break;
                                }
                            }
                            
                            // Use role in parentheses if available
                            if (!empty($entry['role_in_parens'])) {
                                $role = $entry['role_in_parens'];
                            }
                            
                            // Special handling for known packages
                            if ($package_name == 'Special Package3') {
                                if (stripos($entry['name'], 'Jomarie') !== false) {
                                    $role = 'Comedy Clown';
                                } elseif (stripos($entry['name'], 'Markyyyy') !== false || 
                                          stripos($entry['name'], 'Marky') !== false) {
                                    $role = 'Macho Dancer';
                                }
                            }
                            
                            // For all packages, try to match common role patterns in names
                            if (stripos($entry['name'], 'clown') !== false || 
                                stripos($entry['name'], 'magic') !== false || 
                                stripos($entry['name'], 'comedy') !== false) {
                                $role = 'Comedy Clown';
                            } elseif (stripos($entry['name'], 'dance') !== false || 
                                     stripos($entry['name'], 'dancer') !== false) {
                                $role = 'Dancer';
                            } elseif (stripos($entry['name'], 'host') !== false || 
                                     stripos($entry['name'], 'mc') !== false || 
                                     stripos($entry['name'], 'emcee') !== false) {
                                $role = 'Host/Emcee';
                            } elseif (stripos($entry['name'], 'face') !== false && 
                                     stripos($entry['name'], 'paint') !== false) {
                                $role = 'Face Painter';
                            } elseif (stripos($entry['name'], 'balloon') !== false) {
                                $role = 'Balloon Twister';
                            } elseif (stripos($entry['name'], 'mascot') !== false) {
                                $role = 'Mascot';
                            }
                            
                            $matched_entertainers[] = [
                                'name' => $entry['name'],
                                'role' => $role
                            ];
                            error_log("Added unmatched entertainer: {$entry['name']} with assigned role: {$role}");
                        }
                        
                        // Use the matched entertainers
                        $parsed_entertainers = $matched_entertainers;
                    } else {
                        // No package roles found, try to parse from the entertainer string
                        $entertainer_entries = explode(',', $entertainer_string);
                        error_log("Entertainer entries: " . print_r($entertainer_entries, true));
                        
                        foreach ($entertainer_entries as $entry) {
                            $entry = trim($entry);
                            if (empty($entry)) continue;
                            
                            error_log("Processing entry: " . $entry);
                            
                            // Try to extract the role from parentheses
                            if (preg_match('/(.+?)\((.+?)\)/', $entry, $matches)) {
                                $name = trim($matches[1]);
                                $role = trim($matches[2]);
                                error_log("Extracted name: " . $name . ", role: " . $role);
                            } else {
                                // No role in parentheses, try to determine role based on name patterns
                                $name = $entry;
                                $role = 'Performer';
                                
                                // For Special Package3, use specific roles based on the entertainer name
                                if ($package_name == 'Special Package3') {
                                    if (stripos($name, 'Jomarie') !== false) {
                                        $role = 'Comedy Clown';
                                    } elseif (stripos($name, 'Markyyyy') !== false) {
                                        $role = 'Macho Dancer';
                                    }
                                }
                            }
                            
                            $parsed_entertainers[] = [
                                'name' => $name,
                                'role' => $role
                            ];
                        }
                    }
                    
                    error_log("Parsed entertainers: " . print_r($parsed_entertainers, true));
                }
                
                // If we couldn't parse any entertainers, add a default one
                if (empty($parsed_entertainers)) {
                    $parsed_entertainers[] = [
                        'name' => 'Package Entertainer',
                        'role' => 'Performer'
                    ];
                }
                
                // Start output for package information
                echo '<h4>Package Information</h4>';
                echo '<table class="details-table">';
                echo '<thead><tr><th>Package Name</th><th>Entertainer(s)</th><th>Assigned Role</th></tr></thead>';
                echo '<tbody>';
                
                // Display the first row with rowspan for package name if multiple entertainers
                $rowspan = count($parsed_entertainers) > 1 ? ' rowspan="' . count($parsed_entertainers) . '"' : '';
                
                // Output the first entertainer
                echo '<tr>';
                echo '<td' . $rowspan . '>' . htmlspecialchars($package_name) . '</td>';
                echo '<td>' . htmlspecialchars($parsed_entertainers[0]['name']) . '</td>';
                echo '<td>' . htmlspecialchars($parsed_entertainers[0]['role']) . '</td>';
                echo '</tr>';
                
                // Output additional entertainers without repeating the package name
                for ($i = 1; $i < count($parsed_entertainers); $i++) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($parsed_entertainers[$i]['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($parsed_entertainers[$i]['role']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                // Service Details for non-package bookings
                echo '<h4>Service Details</h4>';
                echo '<table class="details-table">';
                echo '<thead><tr><th>Entertainer</th><th>Role</th><th>Duration</th></tr></thead>';
                echo '<tbody>';
                
                if (!empty($entertainers)) {
                    foreach ($entertainers as $entertainer) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($entertainer['role'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($entertainer['perform_durations'] ?? '2 hours') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">No entertainers assigned</td></tr>';
                }
                
                echo '</tbody></table>';
            }
            ?>
        </div>

        <div class="details-section">
            <div class="payment-info">
                <p>Total Price: <span class="amount">₱<?php echo number_format($appointment['total_price'], 2); ?></span></p>
                <p>Down Payment: <span class="amount">₱<?php echo number_format($appointment['down_payment'], 2); ?></span></p>
                <p>Balance: <span class="amount">₱<?php echo number_format($appointment['balance'], 2); ?></span></p>
            </div>
            
            <?php if (!empty($appointment['payment_image'])): ?>
                <div class="payment-proof">
                    <h4>Payment Proof</h4>
                    <img src="<?php echo '../' . htmlspecialchars($appointment['payment_image']); ?>" alt="Payment Proof" style="max-width: 300px; cursor: pointer;" onclick="showModal(this)">
                </div>
            <?php endif; ?>
        </div>

        <div class="details-section">
            <h3>Status</h3>
            <p>Current Status: 
                <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
            </p>
            <?php if (isset($appointment['status_reason']) && !empty($appointment['status_reason'])): ?>
                <p>Reason: <?php echo htmlspecialchars($appointment['status_reason']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

  
    <script>
       // Get the modal
       const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const closeBtn = document.getElementsByClassName("modal-close")[0];

        // Get the payment proof image
        const paymentImg = document.querySelector(".payment-proof img");

        if (paymentImg) {
            // When the user clicks the payment image, open the modal
            paymentImg.onclick = function() {
                modal.classList.add("show");
                modalImg.src = this.src;
            }

            // When the user clicks the close button or outside the modal, close it
            closeBtn.onclick = function() {
                modal.classList.remove("show");
            }

            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.classList.remove("show");
                }
            }

            // Close modal when pressing ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    modal.classList.remove("show");
                }
            });
        }
    </script>
</body>
</html>
