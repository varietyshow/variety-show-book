<?php
include("db_connect.php");

// SQL to create the location_list table
$sql = "CREATE TABLE IF NOT EXISTS location_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    province VARCHAR(100) NOT NULL,
    city_municipality VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_province (province),
    INDEX idx_city_municipality (city_municipality)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table location_list created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert some initial data for Mindanao provinces
$provinces = array(
    'Agusan del Norte' => array(
        'Butuan City' => array('Poblacion', 'Ampayon', 'Baan'),
        'Cabadbaran' => array('Poblacion', 'Calibunan', 'Comagascas')
    ),
    'Davao del Sur' => array(
        'Digos City' => array('Poblacion', 'Binaton', 'Cogon'),
        'Bansalan' => array('Poblacion', 'Anonang', 'Bitaug')
    ),
    'Zamboanga del Sur' => array(
        'Pagadian City' => array('Poblacion', 'Balangasan', 'Kawit'),
        'Molave' => array('Poblacion', 'Bagong Argao', 'Blancia')
    )
);

// Prepare insert statement
$stmt = $conn->prepare("INSERT INTO location_list (province, city_municipality, barangay) VALUES (?, ?, ?)");

// Insert data
foreach ($provinces as $province => $municipalities) {
    foreach ($municipalities as $municipality => $barangays) {
        foreach ($barangays as $barangay) {
            $stmt->bind_param("sss", $province, $municipality, $barangay);
            if (!$stmt->execute()) {
                echo "Error inserting data: " . $stmt->error . "\n";
            }
        }
    }
}

echo "Initial data inserted successfully\n";

$conn->close();
?>
