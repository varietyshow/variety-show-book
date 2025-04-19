<?php
header('Content-Type: application/json');
include("db_connect.php");

$response = array('status' => 'unknown');

try {
    if(isset($_POST['action']) && isset($_POST['province'])) {
        $province = $_POST['province'];
        
        // Query to get municipalities for the selected province
        $query = "SELECT DISTINCT city_municipality FROM location_list WHERE province = ? ORDER BY city_municipality";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("s", $province);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $municipalities = array();
        
        while ($row = $result->fetch_assoc()) {
            $municipalities[] = $row['city_municipality'];
        }
        
        $response = array(
            'status' => 'success',
            'data' => $municipalities,
            'province' => $province
        );
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Missing required parameters',
            'post' => $_POST
        );
    }
} catch (Exception $e) {
    $response = array(
        'status' => 'error',
        'message' => $e->getMessage()
    );
}

echo json_encode($response);
?>
