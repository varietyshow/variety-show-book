<?php
session_start();
header('Content-Type: application/json');

// Database connection details
$host = 'sql12.freesqldatabase.com';
$db = 'sql12777569';
$user = 'sql12777569';
$pass = 'QlgHSeuU1n';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve month, year, and entertainer_id
    $month = $_GET['month'];
    $year = $_GET['year'];
    $entertainer_id = $_SESSION['entertainer_id'] ?? null;

    if (!$entertainer_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Modified query to include booking information and ensure proper date formatting
    $sql = "SELECT 
            st.*,
            DATE_FORMAT(st.date, '%Y-%m-%d') as formatted_date,
            CASE 
                WHEN br.status = 'Approved' THEN 'Booked'
                WHEN br.status = 'Pending' THEN 'Pending'
                ELSE st.status 
            END as current_status
            FROM sched_time st
            LEFT JOIN booking_report br ON st.date = br.date_schedule 
                AND br.entertainer_id = st.entertainer_id
            WHERE MONTH(st.date) = :month 
            AND YEAR(st.date) = :year 
            AND st.entertainer_id = :entertainer_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'month' => $month,
        'year' => $year,
        'entertainer_id' => $entertainer_id
    ]);

    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process dates to ensure consistent format
    foreach ($schedule as &$entry) {
        $entry['date'] = $entry['formatted_date'];
        unset($entry['formatted_date']);
    }
    
    echo json_encode($schedule);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
