<?php
session_start();
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productReviewId = $_POST['product_review_id'] ?? null;

    if ($productReviewId) {
        $stmt = $conn->prepare("UPDATE product_review SET status = 'Rejected' WHERE product_review_id = ?");
        $stmt->bind_param('i', $productReviewId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product has been denied.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error denying the product.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product review ID.']);
    }
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
