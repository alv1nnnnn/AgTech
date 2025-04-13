<?php
// Include your database connection file
include('../Connection/connection.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $product_id = $_POST['product_id'];
    $product_name = htmlspecialchars($_POST['productName']);
    $product_price = $_POST['productPrice'];
    $product_category = $_POST['productCategory']; // category_name from the form
    $product_description = htmlspecialchars($_POST['productDescription']);
    $product_location = htmlspecialchars($_POST['productLocation']);
    $price_id = $_POST['price_id']; // Price ID for updating the price

    // Get category_id based on category_name
    $sql_get_category_id = "SELECT category_id FROM category WHERE category_name = ?";
    $stmt = $conn->prepare($sql_get_category_id);
    $stmt->bind_param("s", $product_category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $category_row = $result->fetch_assoc();
        $category_id = $category_row['category_id']; // Fetch the category_id
    } else {
        // If category does not exist, handle the error
        echo "Category not found!";
        exit;
    }

    // Update the product information in the `products` table
    $sql_update_product = "UPDATE products SET product_name = ?, category_id = ?, location = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql_update_product);
    $stmt->bind_param("sssi", $product_name, $category_id, $product_location, $product_id);
    $stmt->execute();

    // Update the product price in the `productprices` table
    $sql_update_price = "UPDATE productprices SET current_price = ? WHERE price_id = ?";
    $stmt = $conn->prepare($sql_update_price);
    $stmt->bind_param("di", $product_price, $price_id);
    $stmt->execute();

    // Update product description in the `productdetails` table
    $sql_update_description = "UPDATE productdetails SET description = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql_update_description);
    $stmt->bind_param("si", $product_description, $product_id);
    $stmt->execute();

    // Handle image upload (multiple images)
    if (!empty($_FILES['productImage']['name'][0])) {
        // Process the uploaded images
        $uploaded_images = [];
        $image_urls = [];
        $upload_dir = '../product_images/'; // Directory to store the images
        
        // Loop through each uploaded image
        foreach ($_FILES['productImage']['name'] as $key => $image_name) {
            $image_tmp_name = $_FILES['productImage']['tmp_name'][$key];
            $image_path = $upload_dir . basename($image_name);

            // Move the uploaded image to the specified directory
            if (move_uploaded_file($image_tmp_name, $image_path)) {
                $uploaded_images[] = $image_name;
                $image_urls[] = $upload_dir . $image_name; // Save the path of the image
            }
        }

        // If there are new images, update the product_images table
        if (count($uploaded_images) > 0) {
            // Store the new images in the `productimages` table
            $sql_update_images = "UPDATE productimages SET image_url = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql_update_images);
            $json_images = json_encode($image_urls); // Convert image paths to JSON
            $stmt->bind_param("si", $json_images, $product_id);
            $stmt->execute();
        }
    }

    // Redirect or provide success message
    echo "Product updated successfully!";
    // Redirect back to the product page or product listing
    header("Location: Farmer.php?product_id=" . $product_id);
    exit;
} else {
    echo "Invalid request method.";
}
?>
