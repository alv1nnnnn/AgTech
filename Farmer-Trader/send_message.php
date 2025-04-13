<?php
session_start();

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Include the database connection file
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming session is already started
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $message_type = isset($_POST['message_type']) ? $_POST['message_type'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : ''; // Default role if explicitly sent
    $tab = isset($_POST['tab']) ? $_POST['tab'] : ''; // The tab value from frontend

    // Assign role based on the tab if role is not passed
    if (empty($role)) {
        if ($tab === 'selling') {
            $role = 'seller';
        } elseif ($tab === 'buying') {
            $role = 'buyer';
        } else {
            // Default case if tab is unknown
            $role = 'unknown';
            error_log("Unrecognized tab value: " . $tab); // Debugging
        }
    }

    // Validate product_id
    $productCheckSql = "SELECT COUNT(*) FROM products WHERE product_id = ?";
    $stmtProductCheck = $conn->prepare($productCheckSql);
    $stmtProductCheck->bind_param("i", $product_id);
    $stmtProductCheck->execute();
    $stmtProductCheck->bind_result($productExists);
    $stmtProductCheck->fetch();
    $stmtProductCheck->close();

    if ($productExists > 0) {
        // Insert chat message with the role passed or determined by tab
        $insertChatSql = "INSERT INTO chat (sender_id, receiver_id, product_id, message, sent_at, role, message_type)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertChat = $conn->prepare($insertChatSql);
        if ($stmtInsertChat) {
            $currentTimestamp = date('Y-m-d H:i:s'); // Current timestamp in Asia/Manila timezone
            $stmtInsertChat->bind_param("iiissss", $sender_id, $receiver_id, $product_id, $message, $currentTimestamp, $role, $message_type);
            if ($stmtInsertChat->execute()) {
                // Retrieve the latest message preview
                $latestMessagePreviewSql = "
                    SELECT sender_id, message 
                    FROM chat 
                    WHERE product_id = ? 
                    AND ((sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)) 
                    ORDER BY sent_at DESC 
                    LIMIT 1";
                
                $stmtLatestMessagePreview = $conn->prepare($latestMessagePreviewSql);
                $stmtLatestMessagePreview->bind_param("iiiii", $product_id, $sender_id, $receiver_id, $receiver_id, $sender_id);
                $stmtLatestMessagePreview->execute();
                $stmtLatestMessagePreview->bind_result($senderId, $latestMessagePreview);
                $stmtLatestMessagePreview->fetch();
                
                // Prepare preview text
                $previewText = htmlspecialchars(substr($latestMessagePreview, 0, 30)) . (strlen($latestMessagePreview) > 30 ? '...' : '');
                $isSender = ($senderId == $sender_id);
                $previewMessage = $isSender ? "You: $previewText" : $previewText;

                // Prepare response with new message and updated preview
                $response = [
                    'status' => 'success',
                    'message' => 'Message sent successfully.',
                    'new_message' => '', // Placeholder for new message HTML, if needed
                    'preview' => $previewMessage // Updated preview text
                ];
                
                // Send the response back to the AJAX call
                echo json_encode($response);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error inserting message: ' . $stmtInsertChat->error]);
            }
            $stmtInsertChat->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error preparing statement: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: The specified product ID does not exist.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error: Invalid request method.']);
}
?>
