<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';
require_once '../includes/email-notifications.php';
session_start();

// Ensure we're sending JSON response
header('Content-Type: application/json');

try {
    // Start output buffering to catch any unexpected output
    ob_start();

    // Common required fields for both booking types
    $required_fields = [
        'first_name', 'last_name', 'contact_number',
        'street', 'barangay', 'municipality', 'province',
        'date', 'start_time', 'end_time', 'bookingOption'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get common form data
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $contactNumber = $_POST['contact_number'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $municipality = $_POST['municipality'];
    $province = $_POST['province'];
    $dateSchedule = $_POST['date'];
    $timeStart = $_POST['start_time'];
    $timeEnd = $_POST['end_time'];
    $bookingOption = $_POST['bookingOption'];
    
    // Initialize variables
    $selectedPackage = '';
    $performDurations = '';
    $rolesString = '';
    $entertainerNamesString = '';
    $totalPrice = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
    $downPayment = isset($_POST['down_payment']) ? floatval($_POST['down_payment']) : ($totalPrice / 2);
    $balance = $totalPrice - $downPayment;
    $status = 'Pending';
    $paymentImage = '';
    $remarks = 'Pending';
    $customerId = $_SESSION['customer_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        if ($bookingOption === 'option2') {
            // Package booking logic
            if (!isset($_POST['packageSelect']) || empty($_POST['packageSelect'])) {
                throw new Exception("No package selected");
            }

            $packageId = $_POST['packageSelect'];
            
            // Get package details
            $packageQuery = "SELECT cp.package_name, cp.price,
                           GROUP_CONCAT(DISTINCT CONCAT(ea.first_name, ' ', ea.last_name)) as entertainers,
                           GROUP_CONCAT(DISTINCT r.role_name) as roles
                           FROM combo_packages cp
                           JOIN combo_package_roles cpr ON cp.combo_id = cpr.combo_id
                           JOIN roles r ON cpr.role_id = r.role_id
                           JOIN entertainer_account ea ON cpr.entertainer_id = ea.entertainer_id
                           WHERE cp.combo_id = ?
                           GROUP BY cp.combo_id";
            
            $stmt = $conn->prepare($packageQuery);
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $selectedPackage = $row['package_name'];
                $entertainerNamesString = $row['entertainers'];
                $rolesString = $row['roles'];
                $totalPrice = $row['price'];
                $downPayment = $totalPrice / 2;
                $balance = $totalPrice - $downPayment;
            } else {
                throw new Exception("Package not found");
            }

        } else if ($bookingOption === 'option1') {
            // Individual booking logic - only validate these if option1 is selected
            if (!isset($_POST['entertainers']) || !is_array($_POST['entertainers']) || empty($_POST['entertainers'])) {
                throw new Exception("No entertainers selected");
            }

            $entertainerNames = [];
            $entertainerRoles = [];
            
            foreach ($_POST['entertainers'] as $entertainerId) {
                // Get entertainer name
                $nameStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM entertainer_account WHERE entertainer_id = ?");
                $nameStmt->bind_param("i", $entertainerId);
                $nameStmt->execute();
                $nameResult = $nameStmt->get_result();
                
                if ($row = $nameResult->fetch_assoc()) {
                    $entertainerNames[] = $row['full_name'];
                    
                    // Get roles for this entertainer
                    if (isset($_POST['entertainer_roles'][$entertainerId])) {
                        $roleIds = $_POST['entertainer_roles'][$entertainerId];
                        $roleNames = [];
                        
                        foreach ($roleIds as $roleId) {
                            $roleStmt = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                            $roleStmt->bind_param("i", $roleId);
                            $roleStmt->execute();
                            $roleResult = $roleStmt->get_result();
                            if ($roleRow = $roleResult->fetch_assoc()) {
                                $roleNames[] = $roleRow['role_name'];
                            }
                        }
                        
                        $entertainerRoles[$entertainerId] = implode(', ', $roleNames);
                    }
                }
            }
            
            $entertainerNamesString = implode(', ', $entertainerNames);
            $rolesString = implode(', ', array_unique(explode(', ', implode(', ', $entertainerRoles))));
        }

        // Insert into booking_report table
        $stmt = $conn->prepare("INSERT INTO booking_report (
            customer_id, first_name, last_name, contact_number,
            street, barangay, municipality, province,
            date_schedule, time_start, time_end,
            entertainer_name, roles, perform_durations, package,
            total_price, down_payment, balance, payment_image,
            status, remarks
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )");

        $stmt->bind_param(
            "issssssssssssssdddsss",
            $customerId, $firstName, $lastName, $contactNumber,
            $street, $barangay, $municipality, $province,
            $dateSchedule, $timeStart, $timeEnd,
            $entertainerNamesString, $rolesString, $performDurations, $selectedPackage,
            $totalPrice, $downPayment, $balance, $paymentImage,
            $status, $remarks
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert booking: " . $stmt->error);
        }

        $bookingId = $conn->insert_id;

        // For individual bookings, insert into booking_entertainers table
        if ($bookingOption === 'option1') {
            $assignStmt = $conn->prepare("INSERT INTO booking_entertainers (book_id, entertainer_id, roles) VALUES (?, ?, ?)");
            
            foreach ($_POST['entertainers'] as $entertainerId) {
                if (isset($entertainerRoles[$entertainerId])) {
                    $assignStmt->bind_param("iis",
                        $bookingId,
                        $entertainerId,
                        $entertainerRoles[$entertainerId]
                    );
                    if (!$assignStmt->execute()) {
                        throw new Exception("Failed to assign entertainer: " . $assignStmt->error);
                    }
                }
            }
            $assignStmt->close();
        }

        // Commit the transaction
        $conn->commit();

        // Send email notification
        sendNewAppointmentNotification($conn, $bookingId);
            
        // After successful booking insertion, store details in session
        $booking_reference = uniqid('BOOK-');
        $_SESSION['pending_booking'] = [
            'booking_reference' => $booking_reference,
            'down_payment' => $downPayment,
            'total_price' => $totalPrice,
            'booking_id' => $bookingId
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $bookingId,
            'booking_reference' => $booking_reference
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollback();
        }
        throw $e;
    }

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();
    
    error_log("Booking error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
