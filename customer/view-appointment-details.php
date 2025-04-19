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
    if ($package_info['package_type'] === 'role') {
        $package_details_query = $pdo->prepare("
            SELECT 
                rp.package_name,
                r.role_name,
                CONCAT(rp.package_duration, ' ', rp.package_duration_unit) as duration
            FROM role_packages rp
            JOIN roles r ON rp.role_id = r.role_id
            WHERE rp.package_id = ?
        ");
        $package_details_query->execute([$package_info['package']]);
        $package_details = $package_details_query->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($package_info['package_type'] === 'combo') {
        $package_details_query = $pdo->prepare("
            SELECT 
                cp.package_name,
                r.role_name,
                CONCAT(r.duration, ' ', r.duration_unit) as duration
            FROM combo_packages cp
            JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
            JOIN roles r ON cpr.role_id = r.role_id
            WHERE cp.combo_id = ?
        ");
        $package_details_query->execute([$package_info['package']]);
        $package_details = $package_details_query->fetchAll(PDO::FETCH_ASSOC);
    }
    
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
            
            <?php if (!empty($appointment['package'])): ?>
                <!-- Package Information -->
                <h4>Package Information</h4>
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
                        if (!empty($package_details)) {
                            foreach ($entertainers as $entertainer): 
                                foreach ($package_details as $detail):
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['package_name']); ?></td>
                                <td><?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($detail['role_name']); ?></td>
                            </tr>
                        <?php 
                                endforeach;
                            endforeach;
                        } else {
                            // Fallback to basic package display if no detailed package info is found
                            foreach ($entertainers as $entertainer): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['package_name'] ?? $appointment['package']); ?></td>
                                <td><?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($entertainer['roles']); ?></td>
                            </tr>
                        <?php endforeach; 
                        } ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Service Details -->
                <h4>Service Details</h4>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Entertainer</th>
                            <th>Role</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entertainers as $entertainer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entertainer['first_name'] . ' ' . $entertainer['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($entertainer['roles']); ?></td>
                            <td><?php echo htmlspecialchars($entertainer['perform_durations']); ?></td>
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
