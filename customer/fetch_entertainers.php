<?php
include 'db_connect.php';

$date = $_GET['date'];
$start_time = $_GET['start_time'];
$end_time = $_GET['end_time'];

$sql = "
    SELECT e.entertainer_id, e.profile_image, e.title, e.first_name, e.last_name, e.contact_number, e.roles, r.role_name, r.rate, r.duration_unit
    FROM entertainer_account e
    JOIN sched_time s ON e.entertainer_id = s.entertainer_id
    JOIN roles r ON FIND_IN_SET(r.role_name, e.roles) > 0
    WHERE s.status = 'Available'
    AND s.date = ? 
    AND (
        (s.start_time <= ? AND s.end_time >= ?)
        OR (s.start_time <= ? AND s.end_time >= ?)
    )
    GROUP BY e.entertainer_id, r.role_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $date, $start_time, $start_time, $end_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();

$entertainers = [];
while ($row = $result->fetch_assoc()) {
    $entertainers[] = $row;
}

header('Content-Type: application/json');
echo json_encode($entertainers);

$stmt->close();
$conn->close();
?>