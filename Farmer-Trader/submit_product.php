<?php
session_start();
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if files are uploaded
    if (isset($_FILES['productImage'])) {
        $targetDir = "../product_images/";
        $imageNames = [];

        // Handle each uploaded file
        foreach ($_FILES['productImage']['name'] as $key => $imageName) {
            // Check for upload errors
            if ($_FILES['productImage']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['productImage']['tmp_name'][$key];
                $targetFilePath = $targetDir . basename($imageName);

                // Move the uploaded file to the target directory
                if (move_uploaded_file($tmp_name, $targetFilePath)) {
                    $imageNames[] = basename($imageName);
                } else {
                    echo "Error uploading file: " . htmlspecialchars(basename($imageName)) . "<br>";
                }
            } else {
                echo "Error with file upload: " . $_FILES['productImage']['error'][$key] . "<br>";
            }
        }

        // Prepare and execute the database query
        if (!empty($imageNames)) {
            $stmt = $conn->prepare("INSERT INTO product_review (product_name, product_price, product_category, product_description, images, user_id, location, unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Reviewing')");
            $stmt->bind_param("sdsssiss", $productName, $productPrice, $productCategory, $productDescription, $images, $userId, $location, $productUnit);

            // Retrieve form data
            $productName = $_POST['productName'];
            $productPrice = $_POST['productPrice'];
            $productCategory = $_POST['productCategory'];
            $productDescription = $_POST['productDescription'];
            $images = json_encode($imageNames); // Store image names as JSON
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $location = $_POST['productLocation'];
            $productUnit = $_POST['productUnit'];

            if ($userId !== null) {
                if ($stmt->execute()) {
                    // Close the statement
                    $stmt->close();
                    
                    // Redirect to Farmer.php with a success message
                    header("Location: Farmer.php?message=Product successfully submitted for review.");
                    exit();
                } else {
                    echo "Error inserting product into database: " . htmlspecialchars($stmt->error);
                }
            } else {
                echo "User not logged in. Please log in first.";
            }
        } else {
            echo "No valid images uploaded.";
        }
    } else {
        echo "No files were uploaded.";
    }
} else {
    echo "Invalid request method.";
}

// Close the connection
$conn->close();
?>
