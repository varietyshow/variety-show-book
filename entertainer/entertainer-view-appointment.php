<?php
session_start();

// Add this function after your session_start()
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: entertainer-loginpage.php");
    exit();
}

// Get the book_id from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$book_id) {
    echo "No appointment ID provided";
    exit();
}

// Database connection
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12774230';
$username = 'sql12774230';
$password = 'ytPEFx33BF';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch appointment details
    $stmt = $pdo->prepare("
        SELECT br.*, 
               GROUP_CONCAT(r.rate ORDER BY FIND_IN_SET(r.role_name, br.roles)) as role_rates,
               GROUP_CONCAT(CONCAT(r.duration, ' ', r.duration_unit)) as role_durations
        FROM booking_report br
        LEFT JOIN roles r ON FIND_IN_SET(r.role_name, br.roles)
        WHERE br.book_id = ?
        GROUP BY br.book_id
    ");
    $stmt->execute([$book_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        echo "Appointment not found";
        exit();
    }

    // Fetch entertainer details with their roles and durations
    $stmt_entertainers = $pdo->prepare("
        SELECT be.*, ea.first_name, ea.last_name, be.roles, be.perform_durations
        FROM booking_entertainers be
        JOIN entertainer_account ea ON be.entertainer_id = ea.entertainer_id
        WHERE be.book_id = ?
    ");
    $stmt_entertainers->execute([$book_id]);
    $entertainers = $stmt_entertainers->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Appointment Details</title>
    <link rel="stylesheet" href="style3.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
        }

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
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .details-table th,
        .details-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        /* Payment proof styles */
        .payment-proof {
            margin-top: 20px;
        }

        .payment-proof img {
            max-width: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            cursor: pointer;
        }

        .payment-proof img:hover {
            transform: scale(1.05);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding: 20px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            margin: auto;
            display: block;
        }

        .modal-close {
            position: absolute;
            right: 35px;
            top: 15px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #bbb;
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
        <a href="entertainer-myAppointment.php" class="back-button">← Back to Appointments</a>
        
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
            <?php if (!empty($appointment['package'])): ?>
                <!-- Package Details -->
                <h3>Package Details</h3>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Entertainer</th>
                            <th>Assigned Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Parse the entertainer_name field to extract entertainers and their roles
                        $parsed_entertainers = [];
                        
                        // Check if we have entertainer_name in the appointment
                        if (!empty($appointment['entertainer_name'])) {
                            $entertainer_string = $appointment['entertainer_name'];
                            
                            // Debug the package info
                            error_log("Entertainer view - Package ID: {$appointment['package']}, Entertainer string: {$entertainer_string}");
                            
                            // Get all roles from the roles table for reference
                            $roles_query = $pdo->prepare("SELECT role_id, role_name FROM roles");
                            $roles_query->execute();
                            $all_roles = $roles_query->fetchAll(PDO::FETCH_KEY_PAIR);
                            
                            // Get roles based on package type
                            $package_roles = [];
                            
                            // Try to determine if this is a combo or role package
                            $package_type_query = $pdo->prepare("
                                SELECT 
                                    CASE 
                                        WHEN EXISTS (SELECT 1 FROM role_packages WHERE package_id = ?) THEN 'role'
                                        WHEN EXISTS (SELECT 1 FROM combo_packages WHERE combo_id = ?) THEN 'combo'
                                        ELSE NULL
                                    END as package_type
                            ");
                            $package_type_query->execute([$appointment['package'], $appointment['package']]);
                            $package_type_result = $package_type_query->fetch(PDO::FETCH_ASSOC);
                            $package_type = $package_type_result['package_type'] ?? null;
                            
                            // For combo packages
                            if ($package_type === 'combo') {
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
                            } 
                            // For role packages
                            elseif ($package_type === 'role') {
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
                            } 
                            // For packages stored directly as strings
                            else {
                                // Try to get some default roles
                                $package_roles_query = $pdo->prepare("SELECT role_id, role_name, NULL as entertainer_name FROM roles LIMIT 10");
                                $package_roles_query->execute();
                                $package_roles = $package_roles_query->fetchAll(PDO::FETCH_ASSOC);
                            }
                            
                            // Split the entertainer names
                            $entertainer_entries = explode(',', $entertainer_string);
                            
                            // Process each entertainer entry
                            foreach ($entertainer_entries as $entry) {
                                $name = trim($entry);
                                if (empty($name)) continue;
                                
                                // Check if the name contains a role in parentheses
                                if (preg_match('/(.+?)\((.+?)\)/', $name, $matches)) {
                                    $clean_name = trim($matches[1]);
                                    $role = trim($matches[2]);
                                } else {
                                    $clean_name = $name;
                                    $role = 'Performer'; // Default role
                                    
                                    // First try to match with package roles from the database
                                    $matched_db_role = false;
                                    
                                    // If we have package roles from the database, try to assign them in order
                                    if (!empty($package_roles)) {
                                        static $used_roles = [];
                                        
                                        foreach ($package_roles as $role_info) {
                                            if (!empty($role_info['role_name']) && !in_array($role_info['role_name'], $used_roles)) {
                                                $role = $role_info['role_name'];
                                                $used_roles[] = $role;
                                                $matched_db_role = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // If no role from database, try to determine based on name patterns
                                    if (!$matched_db_role) {
                                        // Special handling for Special Package3
                                        if ($appointment['package'] == 'Special Package3') {
                                            if (stripos($name, 'Jomarie') !== false) {
                                                $role = 'Comedy Clown';
                                            } elseif (stripos($name, 'Markyyyy') !== false || 
                                                      stripos($name, 'Marky') !== false) {
                                                $role = 'Macho Dancer';
                                            }
                                        }
                                        
                                        // Try to determine role based on name patterns
                                        if ($role == 'Performer') { // Only if still using the default
                                            if (stripos($name, 'clown') !== false || 
                                                stripos($name, 'magic') !== false || 
                                                stripos($name, 'comedy') !== false) {
                                                $role = 'Comedy Clown';
                                            } elseif (stripos($name, 'dance') !== false || 
                                                     stripos($name, 'dancer') !== false) {
                                                $role = 'Dancer';
                                            } elseif (stripos($name, 'host') !== false || 
                                                     stripos($name, 'mc') !== false || 
                                                     stripos($name, 'emcee') !== false) {
                                                $role = 'Host/Emcee';
                                            } elseif (stripos($name, 'face') !== false && 
                                                     stripos($name, 'paint') !== false) {
                                                $role = 'Face Painter';
                                            } elseif (stripos($name, 'balloon') !== false) {
                                                $role = 'Balloon Twister';
                                            } elseif (stripos($name, 'mascot') !== false) {
                                                $role = 'Mascot';
                                            }
                                        }
                                    }
                                }
                                
                                $parsed_entertainers[] = [
                                    'name' => $clean_name,
                                    'role' => $role
                                ];
                            }
                        } else {
                            // If no entertainer_name, use the entertainers array
                            foreach ($entertainers as $entertainer) {
                                $parsed_entertainers[] = [
                                    'name' => $entertainer['first_name'] . ' ' . $entertainer['last_name'],
                                    'role' => $entertainer['roles'] ?? 'Performer'
                                ];
                            }
                        }
                        
                        // If we still have no entertainers, add a default one
                        if (empty($parsed_entertainers)) {
                            $parsed_entertainers[] = [
                                'name' => 'Package Entertainer',
                                'role' => 'Performer'
                            ];
                        }
                        
                        // Display the first row with rowspan for package name if multiple entertainers
                        $rowspan = count($parsed_entertainers) > 1 ? ' rowspan="' . count($parsed_entertainers) . '"' : '';
                        
                        // Output the first entertainer
                        echo '<tr>';
                        echo '<td' . $rowspan . '>' . htmlspecialchars($appointment['package']) . '</td>';
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
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Service Details -->
                <h3>Service Details</h3>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Services & Perform Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entertainers as $entertainer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($entertainer['perform_durations'])) {
                                        $durations = explode(', ', $entertainer['perform_durations']);
                                        foreach ($durations as $duration) {
                                            echo htmlspecialchars($duration) . "<br>";
                                        }
                                    } else {
                                        echo "No services assigned";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
                    <?php
                    // Check if the payment image path already contains a directory prefix
                    $image_path = $appointment['payment_image'];
                    if (strpos($image_path, '/') === false && strpos($image_path, '\\') === false) {
                        // No directory prefix found, add ../uploads/
                        $image_path = '../uploads/' . $image_path;
                    } else if (strpos($image_path, 'images/payment_proofs/') !== false) {
                        // If it's from customer booking (contains images/payment_proofs/), add only ../
                        $image_path = '../' . $image_path;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Payment Proof" onclick="showModal(this)">
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

        function showModal(img) {
            modal.classList.add("show");
            modalImg.src = img.src;
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
    </script>
</body>
</html>
