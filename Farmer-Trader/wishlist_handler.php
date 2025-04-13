<?php
include '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $wishlist_id = intval($data['wishlist_id']);
    $product_id = intval($data['product_id']);

    $sql = "UPDATE wishlist_item SET status = 'inactive' WHERE wishlist_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $wishlist_id, $product_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item removed from wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }

    $stmt->close();
    $conn->close();
}
?>
