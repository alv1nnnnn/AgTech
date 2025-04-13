<?php
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_review_id'])) {
    $product_review_id = intval($_POST['product_review_id']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get the product details from the product_review table
        $stmt = $conn->prepare("SELECT * FROM product_review WHERE product_review_id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $product_review_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();

            // Get the category_id from the category table based on category_name
            $stmt = $conn->prepare("SELECT category_id FROM category WHERE category_name = ?");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("s", $product['product_category']);
            $stmt->execute();
            $category_result = $stmt->get_result();

            if ($category_result->num_rows > 0) {
                $category = $category_result->fetch_assoc();
                $category_id = $category['category_id'];

                // Insert into the products table (without unit)
                $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, location) VALUES (?, ?, ?)");
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                $stmt->bind_param("sis", $product['product_name'], $category_id, $product['location']);
                $stmt->execute();
                $new_product_id = $stmt->insert_id;

                // Insert into productprices table
                $stmt = $conn->prepare("INSERT INTO productprices (product_id, current_price, is_active, effective_date) VALUES (?, ?, 1, NOW())");
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                $stmt->bind_param("id", $new_product_id, $product['product_price']);
                $stmt->execute();

                // Ensure 'product_unit' is set, if not, use a default value like 'N/A'
                $product_unit = isset($product['unit']) ? $product['unit'] : 'N/A';
                
                $stmt = $conn->prepare("INSERT INTO productdetails (product_id, description, created_at, unit) VALUES (?, ?, NOW(), ?)");
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                $stmt->bind_param("iss", $new_product_id, $product['product_description'], $product_unit);
                $stmt->execute();

                // Insert into userproducts table
                $stmt = $conn->prepare("INSERT INTO userproducts (user_id, product_id) VALUES (?, ?)");
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                $stmt->bind_param("ii", $product['user_id'], $new_product_id);
                $stmt->execute();

                // Decode the images JSON into an array
                $images = json_decode($product['images'], true); // Decode as associative array
                if ($images && is_array($images)) {
                    $images_json = json_encode($images); // Encode the images array as a JSON string

                    // Insert all images in a single row
                    $stmt = $conn->prepare("INSERT INTO productimages (product_id, image_url) VALUES (?, ?)");
                    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                    $stmt->bind_param("is", $new_product_id, $images_json);
                    $stmt->execute();
                }

                // Update the status of the product_review to Approved
                $stmt = $conn->prepare("UPDATE product_review SET status = 'Approved' WHERE product_review_id = ?");
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                $stmt->bind_param("i", $product_review_id);
                $stmt->execute();

                // Commit transaction
                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Category not found');
            }
        } else {
            throw new Exception('Product review not found');
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
