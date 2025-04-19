<?php
include 'db_connect.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entertainer_id = $_POST['entertainer_id'];
    $target_dir = "../images/";
    $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is an image
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["profile_image"]["size"] > 500000) { // Size limit of 500KB
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
    if (!in_array($imageFileType, $allowedTypes)) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            echo "The file ". htmlspecialchars(basename($_FILES["profile_image"]["name"])). " has been uploaded.";

            // Insert or update the image path in the database
            $image_path = htmlspecialchars(basename($_FILES["profile_image"]["name"]));
            $query = "UPDATE entertainer_account SET profile_image = ? WHERE entertainer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $image_path, $entertainer_id);
            if ($stmt->execute()) {
                echo "Database updated successfully.";
            } else {
                echo "Error updating database.";
            }
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
    $conn->close();
}
?>
