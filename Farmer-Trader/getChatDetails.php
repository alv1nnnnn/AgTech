<?php
require_once '../Connection/connection.php'; // Ensure your connection file is correctly included

if (isset($_GET['chat_id'])) {
    $chat_id = $_GET['chat_id'];

    // Prepare the SQL statement to retrieve the sender_id and product_id
    $stmt = $conn->prepare("SELECT sender_id, product_id FROM chat WHERE chat_id = ?");
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $chat_data = $result->fetch_assoc();
        $sender_id = $chat_data['sender_id'];
        $product_id = $chat_data['product_id'];

        // Now retrieve the seller's name based on sender_id
        $stmt = $conn->prepare("SELECT first_name, last_name FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $seller_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
            
            // Return sender_id and product_id along with seller_name
            echo json_encode([
                'success' => true,
                'sender_id' => $sender_id,
                'product_id' => $product_id,
                'seller_name' => $seller_name
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Seller not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Chat not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No chat_id provided']);
}
?>
