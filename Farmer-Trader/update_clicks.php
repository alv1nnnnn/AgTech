<?php
// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Include database connection
include('../Connection/connection.php'); // Adjust this to your actual connection file

// Get product_id from the POST request
$product_id = $_POST['product_id'];
$user_id = $_SESSION['user_id']; // Assuming you're storing user_id in session

// Query the userproducts table to get the owner_user_id based on the product_id
$query = "SELECT user_id FROM userproducts WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    $owner_user_id = $product['user_id'];

    // Check if the logged-in user is not the owner of the product
    if ($owner_user_id != $user_id) {
        // Insert or update the clicks count in product_performance table
        $performance_query = "SELECT * FROM product_performance WHERE product_id = ? AND performance_date = CURDATE()";
        $performance_stmt = $conn->prepare($performance_query);
        $performance_stmt->bind_param("i", $product_id);
        $performance_stmt->execute();
        $performance_result = $performance_stmt->get_result();

        if ($performance_result->num_rows > 0) {
            // Product performance entry exists, increment the clicks count
            $update_query = "UPDATE product_performance SET clicks_count = clicks_count + 1 WHERE product_id = ? AND performance_date = CURDATE()";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $product_id);
            $update_stmt->execute();
        } else {
            // Product performance entry does not exist, create a new entry
            $insert_query = "INSERT INTO product_performance (product_id, clicks_count, performance_date) VALUES (?, 1, CURDATE())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("i", $product_id);
            $insert_stmt->execute();
        }
    }
}

// Close connection
$stmt->close();
if (isset($performance_stmt)) $performance_stmt->close();
if (isset($insert_stmt)) $insert_stmt->close();
if (isset($update_stmt)) $update_stmt->close();
$conn->close();
?>
