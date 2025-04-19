<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = array();
    
    try {
        $entertainer_id = intval($_POST['entertainer_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        // Delete the entertainer
        $sql = "DELETE FROM entertainer_account WHERE entertainer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $entertainer_id);
        $stmt->execute();
        
        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("Entertainer not found or already deleted.");
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = "Entertainer deleted successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        $response['status'] = 'error';
        $response['message'] = "Error deleting entertainer: " . $e->getMessage();
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
