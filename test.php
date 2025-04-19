<?php
// Initialize variables for database connection
$host = 'localhost'; // Change if your hostname is different
$dbname = 'db_booking_system';
$username = 'root'; // Change this if you use a different username
$password = ''; // Add your password if you set one

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Variable to store message
$message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entertainer_id = $_POST['entertainer_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    // Prepare an insert statement
    $stmt = $conn->prepare("INSERT INTO sched_time (entertainer_id, date, start_time, end_time, price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $entertainer_id, $date, $start_time, $end_time, $price, $status);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        $message = "Schedule added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager</title>
    <script>
        // Show a JavaScript alert if there is a message
        function showAlert(message) {
            if (message) {
                alert(message);
            }
        }
    </script>
</head>
<body onload="showAlert('<?php echo $message; ?>')">
    <h1>Add Schedule</h1>
    <form method="POST" action="test.php">
        <!-- Hidden input for entertainer_id -->
        <input type="hidden" name="entertainer_id" value="1">  <!-- Replace '1' with the appropriate entertainer ID as needed. -->

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required><br><br>

        <label for="start_time">Start Time:</label>
        <input type="time" id="start_time" name="start_time" required><br><br>

        <label for="end_time">End Time:</label>
        <input type="time" id="end_time" name="end_time" required><br><br>

        <label for="price">Price:</label>
        <input type="number" id="price" name="price" step="0.01" required><br><br>

        <!-- Hidden input for status -->
        <input type="hidden" name="status" value="Available">  <!-- Default status as 'Available' -->

        <input type="submit" value="Submit">
    </form>
</body>
</html>