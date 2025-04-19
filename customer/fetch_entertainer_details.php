<?php
// Include your database connection file
include 'db_connect.php';

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'No entertainer ID provided']);
    exit();
}

// Sanitize the input to prevent SQL injection
$entertainer_id = $conn->real_escape_string($_GET['id']);

// Prepare SQL query to fetch entertainer details
$sql = "SELECT 
    e.entertainer_id,
    e.title,
    e.first_name,
    e.last_name,
    e.contact_number,
    e.email,
    e.social_media_account,
    e.profile_image,
    e.street,
    e.barangay,
    e.municipality,
    e.province,
    e.specialization,
    e.rate_per_hour
FROM 
    entertainer_account e
WHERE 
    e.entertainer_id = '$entertainer_id'";

// Execute the query
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    // Fetch the result as an associative array
    $entertainer = $result->fetch_assoc();
    
    // Check if an entertainer was found
    if ($entertainer) {
        // Remove any sensitive information if needed
        unset($entertainer['password']); // Example of removing sensitive data
        
        // Return the entertainer details as JSON
        header('Content-Type: application/json');
        echo json_encode($entertainer);
    } else {
        // No entertainer found with the given ID
        http_response_code(404);
        echo json_encode(['error' => 'Entertainer not found']);
    }
} else {
    // Database query error
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

// Close the database connection
$conn->close();
?>