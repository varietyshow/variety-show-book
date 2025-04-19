<?php
session_start();
include 'db_connect.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = array();
    
    try {
        $entertainer_id = intval($_POST['entertainer_id']);
        $first_name = htmlspecialchars($_POST['first_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $title = htmlspecialchars($_POST['title']);
        $street = htmlspecialchars($_POST['street']);
        $barangay = htmlspecialchars($_POST['barangay']);
        $municipality = htmlspecialchars($_POST['municipality']);
        $province = htmlspecialchars($_POST['province']);
        $contact_number = htmlspecialchars($_POST['contact_number']);
        $status = htmlspecialchars($_POST['status']);
        $roles = isset($_POST['roles']) ? implode(',', $_POST['roles']) : '';
        
        // Validate and format phone number
        $phone = preg_replace('/\D/', '', $contact_number); // Remove all non-digits
        
        // Validate phone number format (must be 11 digits starting with 09)
        if (strlen($phone) !== 11 || !str_starts_with($phone, '09')) {
            throw new Exception("Invalid phone number format. Must be 11 digits starting with 09.");
        }
        
        // Start transaction
        $conn->begin_transaction();

        // Update main entertainer details
        $sql = "UPDATE entertainer_account SET 
                first_name=?, 
                last_name=?, 
                title=?,
                street=?,
                barangay=?,
                municipality=?,
                province=?,
                contact_number=?,
                status=?,
                roles=?
                WHERE entertainer_id=?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssi", 
            $first_name, 
            $last_name, 
            $title, 
            $street,
            $barangay,
            $municipality,
            $province,
            $phone, // Store the raw 11-digit number
            $status,
            $roles,
            $entertainer_id
        );
        
        $stmt->execute();
        
        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("No changes were made or entertainer not found.");
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = "Entertainer updated successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        $response['status'] = 'error';
        $response['message'] = "Error updating entertainer: " . $e->getMessage();
    } finally {
        // Close resources
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>