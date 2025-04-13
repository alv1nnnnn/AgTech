<?php
require_once '../Connection/connection.php'; // Include your database connection

session_start();

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Prepare and sanitize input data
$productId = $data['productId'];
$productName = $data['productName'];
$selectedCategoryName = $data['categoryId']; // Assuming this is the category name from dropdown
$currentPrice = $data['currentPrice'];
$location = $data['location'];
$ownerFirstName = $data['ownerFirstName'];
$ownerLastName = $data['ownerLastName'];
$userId = $_SESSION['user_id']; // Get the logged-in user's ID

// Step 1: Fetch category_id based on the selected category name
$fetchCategoryIdQuery = "
    SELECT category_id FROM Category
    WHERE category_name = ?
";

$stmt = $conn->prepare($fetchCategoryIdQuery);
$stmt->bind_param('s', $selectedCategoryName);
$stmt->execute();
$stmt->bind_result($categoryId);
$stmt->fetch();
$stmt->close();

// Step 2: Update Products table with the fetched category_id
$updateProductQuery = "
    UPDATE Products
    SET product_name = ?, category_id = ?, location = ?
    WHERE product_id = ?
";

$stmt = $conn->prepare($updateProductQuery);
$stmt->bind_param('sssi', $productName, $categoryId, $location, $productId);
$productUpdateSuccess = $stmt->execute();

// Update ProductPrices table
$updatePriceQuery = "
    UPDATE ProductPrices
    SET current_price = ?, is_active = ?, effective_date = NOW()
    WHERE product_id = ?
";

$isActive = 1; // Adjust this based on your logic

$stmt = $conn->prepare($updatePriceQuery);
$stmt->bind_param('isi', $currentPrice, $isActive, $productId);
$priceUpdateSuccess = $stmt->execute();

// Update owner's first and last name based on user_id from User table
$updateOwnerQuery = "
    UPDATE User
    SET first_name = ?, last_name = ?
    WHERE user_id = ?
";

$stmt = $conn->prepare($updateOwnerQuery);
$stmt->bind_param('ssi', $ownerFirstName, $ownerLastName, $userId);
$ownerUpdateSuccess = $stmt->execute();

// Check if all updates were successful
if ($productUpdateSuccess && $priceUpdateSuccess && $ownerUpdateSuccess) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
