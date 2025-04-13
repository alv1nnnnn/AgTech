<?php
header('Content-Type: application/json');

require_once '../Connection/connection.php'; // Include your database connection

try {
    // Get the product ID from the request
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['productId'];

    // Begin a transaction
    $conn->begin_transaction();

    // Delete dependent rows in the `productdetails` table
    $stmt1 = $conn->prepare("DELETE FROM productdetails WHERE product_id = ?");
    $stmt1->bind_param('i', $productId);
    $stmt1->execute();

    // Delete dependent rows in the `productimages` table
    $stmt2 = $conn->prepare("DELETE FROM productimages WHERE product_id = ?");
    $stmt2->bind_param('i', $productId);
    $stmt2->execute();

    // Delete dependent rows in the `userproducts` table
    $stmt3 = $conn->prepare("DELETE FROM userproducts WHERE product_id = ?");
    $stmt3->bind_param('i', $productId);
    $stmt3->execute();

    // Delete dependent rows in the `chat` table
    $stmtChat = $conn->prepare("DELETE FROM chat WHERE product_id = ?");
    $stmtChat->bind_param('i', $productId);
    $stmtChat->execute();

    // Delete dependent rows in the `review` table
    $stmtReview = $conn->prepare("DELETE FROM review WHERE product_id = ?");
    $stmtReview->bind_param('i', $productId);
    $stmtReview->execute();

    // Delete dependent rows in the `productprices` table
    $stmtproductprices = $conn->prepare("DELETE FROM productprices WHERE product_id = ?");
    $stmtproductprices->bind_param('i', $productId);
    $stmtproductprices->execute();

    // Delete dependent rows in the `wishlist_item` table
    $stmtwishlist = $conn->prepare("DELETE FROM wishlist_item WHERE product_id = ?");
    $stmtwishlist->bind_param('i', $productId);
    $stmtwishlist->execute();

    // Finally, delete from the `products` table
    $stmt4 = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt4->bind_param('i', $productId);
    $stmt4->execute();

    // Commit the transaction if all queries succeed
    $conn->commit();

    // Send a success response
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback the transaction if there is an error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Close the connection
$conn->close();
?>
