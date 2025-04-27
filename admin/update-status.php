<?php
session_start();
if (!isset($_SESSION['first_name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$host = 'sql12.freesqldatabase.com';
$dbname = 'sql12775634';
$username = 'sql12775634';
$password = 'kPZFb8pXsU';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Function to check for appointment conflicts
    function checkAppointmentConflict($pdo, $book_id) {
        // Get the appointment details we're trying to approve
        $stmt = $pdo->prepare("SELECT date_schedule, time_start, time_end, entertainer_name FROM booking_report WHERE book_id = ?");
        $stmt->execute([$book_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            return ['conflict' => true, 'message' => 'Appointment not found'];
        }

        // Check for any overlapping appointments for the same entertainer
        $stmt = $pdo->prepare("
            SELECT book_id, date_schedule, time_start, time_end, first_name, last_name 
            FROM booking_report 
            WHERE entertainer_name = :entertainer
            AND date_schedule = :date
            AND status = 'Approved'
            AND book_id != :book_id
            AND (
                (time_start BETWEEN :start AND :end)
                OR (time_end BETWEEN :start AND :end)
                OR (:start BETWEEN time_start AND time_end)
            )
        ");
        
        $stmt->execute([
            ':entertainer' => $appointment['entertainer_name'],
            ':date' => $appointment['date_schedule'],
            ':start' => $appointment['time_start'],
            ':end' => $appointment['time_end'],
            ':book_id' => $book_id
        ]);
        
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($conflicts) > 0) {
            $conflict = $conflicts[0];
            return [
                'conflict' => true,
                'message' => "Schedule conflict detected with another appointment: " .
                            $conflict['first_name'] . " " . $conflict['last_name'] . 
                            " from " . date('g:i A', strtotime($conflict['time_start'])) . 
                            " to " . date('g:i A', strtotime($conflict['time_end']))
            ];
        }
        
        return ['conflict' => false];
    }

    if (isset($_POST['book_id']) && isset($_POST['action'])) {
        $book_id = $_POST['book_id'];
        $action = $_POST['action'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        
        // Validate required reason for decline and cancel actions
        if (($action === 'decline' || $action === 'cancel') && empty($reason)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'A reason is required for ' . $action . ' action'
            ]);
            exit();
        }
        
        // Update the status and reason
        if ($action === 'approve') {
            // Check for conflicts before approving
            $conflictCheck = checkAppointmentConflict($pdo, $book_id);
            if ($conflictCheck['conflict']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $conflictCheck['message']
                ]);
                exit();
            }
        }
        
        $sql = "UPDATE booking_report SET status = :status";
        if ($action === 'decline' || $action === 'cancel') {
            $sql .= ", reason = :reason";
        } elseif ($action === 'approve') {
            $sql .= ", reason = NULL";
        }
        $sql .= " WHERE book_id = :book_id";
        
        $stmt = $pdo->prepare($sql);
        
        $status = '';
        switch ($action) {
            case 'approve':
                $status = 'Approved';
                break;
            case 'decline':
                $status = 'Declined';
                break;
            case 'cancel':
                $status = 'Cancelled';
                break;
            default:
                throw new Exception('Invalid action');
        }
        
        $params = [':status' => $status, ':book_id' => $book_id];
        if ($action === 'decline' || $action === 'cancel') {
            $params[':reason'] = $reason;
        }
        
        $stmt->execute($params);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Appointment has been ' . strtolower($status) . ' successfully'
        ]);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
