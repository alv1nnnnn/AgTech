<?php
// Include your database connection file
include('../Connection/connection.php');

// Check if the product ID is provided and is a valid integer
if (isset($_GET['product_id']) && filter_var($_GET['product_id'], FILTER_VALIDATE_INT)) {
    $product_id = (int)$_GET['product_id'];

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Define delete queries
        $queries = [
            "DELETE FROM wishlist_item WHERE product_id = ?",
            "DELETE FROM productimages WHERE product_id = ?",
            "DELETE FROM productdetails WHERE product_id = ?",
            "DELETE FROM productprices WHERE product_id = ?",
            "DELETE FROM userproducts WHERE product_id = ?",
            "DELETE FROM chat WHERE product_id = ?",
            "DELETE FROM review WHERE product_id = ?",
            "DELETE FROM tradeoffer WHERE product_id = ?",
            "DELETE FROM products WHERE product_id = ?"
        ];

        // Execute each query
        foreach ($queries as $query) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Product and related data deleted successfully!", "redirect" => "Farmer.php"]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "Failed to delete product."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid product ID!"]);
}
?>
