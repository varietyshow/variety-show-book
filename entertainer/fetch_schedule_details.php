<?php
// fetch_schedule_details.php
include('db_connect.php'); // Make sure this file contains your DB connection setup

if (isset($_GET['id'])) {
    $sched_id = $_GET['id'];

    // SQL query to fetch the schedule details by sched_id
    $query = "SELECT * FROM sched_time WHERE sched_id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $sched_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode($row); // Return data as JSON
        } else {
            echo json_encode(['error' => 'Schedule not found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare query']);
    }
} else {
    echo json_encode(['error' => 'Invalid schedule ID']);
}


$conn->close();
?>
