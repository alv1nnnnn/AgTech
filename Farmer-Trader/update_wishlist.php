<?php
session_start();
require '../Connection/connection.php'; // Include your database connection file

header('Content-Type: application/json'); // Ensure the response is JSON

// Check if the request is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'];
    $product_id = $data['product_id'];
    $status = $data['status']; // Get the status to update (active or inactive)

    // Check if the wishlist item already exists
    $stmt = $conn->prepare("SELECT wi.wishlist_item_id, wi.status FROM wishlist w
                            JOIN wishlist_item wi ON w.wishlist_id = wi.wishlist_id
                            WHERE w.user_id = ? AND wi.product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If the wishlist item exists, update the status
        $row = $result->fetch_assoc();
        $wishlist_item_id = $row['wishlist_item_id'];

        // Toggle status if it's currently 'active', set it to 'inactive', and vice versa
        $new_status = ($row['status'] === 'active') ? 'inactive' : 'active';

        $update_item_stmt = $conn->prepare("UPDATE wishlist_item SET status = ? WHERE wishlist_item_id = ?");
        $update_item_stmt->bind_param("si", $new_status, $wishlist_item_id);

        if ($update_item_stmt->execute()) {
            // Update product_performance based on the new status
            if ($new_status === 'active') {
                $performance_stmt = $conn->prepare("UPDATE product_performance SET wishlist_count = wishlist_count + 1 WHERE product_id = ?");
            } else {
                $performance_stmt = $conn->prepare("UPDATE product_performance SET wishlist_count = wishlist_count - 1 WHERE product_id = ?");
            }
            $performance_stmt->bind_param("i", $product_id);
            $performance_stmt->execute();
            $performance_stmt->close();

            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update wishlist item status.']);
        }

        $update_item_stmt->close();
    } else {
        // If the wishlist item does not exist, add it to the wishlist
        // First, check if the user has a wishlist
        $wishlist_stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ?");
        $wishlist_stmt->bind_param("i", $user_id);
        $wishlist_stmt->execute();
        $wishlist_result = $wishlist_stmt->get_result();

        if ($wishlist_result->num_rows > 0) {
            // User already has a wishlist, use the existing wishlist_id
            $wishlist_row = $wishlist_result->fetch_assoc();
            $wishlist_id = $wishlist_row['wishlist_id'];
        } else {
            // Create a new wishlist if it doesn't exist
            $insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, created_at) VALUES (?, NOW())");
            $insert_stmt->bind_param("i", $user_id);

            if ($insert_stmt->execute()) {
                $wishlist_id = $insert_stmt->insert_id; // Get the last inserted wishlist_id
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create wishlist.']);
                exit;
            }

            $insert_stmt->close();
        }

        // Now add the item to the wishlist_item table with an initial status
        $insert_item_stmt = $conn->prepare("INSERT INTO wishlist_item (wishlist_id, product_id, status) VALUES (?, ?, 'active')");
        $insert_item_stmt->bind_param("ii", $wishlist_id, $product_id);

        if ($insert_item_stmt->execute()) {
            // Increment wishlist_count in product_performance
            $performance_stmt = $conn->prepare("UPDATE product_performance SET wishlist_count = wishlist_count + 1 WHERE product_id = ?");
            $performance_stmt->bind_param("i", $product_id);
            $performance_stmt->execute();
            $performance_stmt->close();

            echo json_encode(['success' => true, 'status' => 'active']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add item to wishlist.']);
        }

        $insert_item_stmt->close();
    }

    $stmt->close();
    $conn->close();
}
?>
