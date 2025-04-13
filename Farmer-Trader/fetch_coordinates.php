<?php
// Include the database connection file
include '../Connection/connection.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Get product_id from the URL
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : 0;

if ($product_id == 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

// Fetch location of the product from the database
$sql = "SELECT product_id, location FROM products WHERE product_id = $product_id";
$result = $conn->query($sql);

$product = null;
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo json_encode(['error' => 'Product not found']);
    exit();
}

// Close the database connection
$conn->close();

// Return the product's location as JSON
echo json_encode($product);
?>
