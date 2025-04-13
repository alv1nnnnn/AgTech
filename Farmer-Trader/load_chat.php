<?php
session_start();

// Database connection
require_once '../Connection/connection.php';

// Check session user_id
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Validate input data
if (!isset($_POST['participant_id'], $_POST['product_id'])) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$participant_id = intval($_POST['participant_id']);
$product_id = intval($_POST['product_id']);

// Check if the product is rated
$ratedCheckSql = "SELECT rated FROM products WHERE product_id = ?";
$stmtRatedCheck = $conn->prepare($ratedCheckSql);

if ($stmtRatedCheck === false) {
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmtRatedCheck->bind_param("i", $product_id);
$stmtRatedCheck->execute();
$stmtRatedCheck->bind_result($productRated);
$stmtRatedCheck->fetch();
$stmtRatedCheck->close();

// SQL query with DISTINCT to avoid duplicates
$chatSql = "SELECT DISTINCT c.chat_id, c.sender_id, c.receiver_id, c.message, c.sent_at, c.message_type, 
                   c.offer_id, t.offer_status, pi.image_url, pp.current_price, c.read_status
            FROM chat c
            LEFT JOIN tradeoffer t ON c.offer_id = t.offer_id
            LEFT JOIN (
                SELECT product_id, MIN(image_url) AS image_url 
                FROM productimages 
                GROUP BY product_id
            ) pi ON c.product_id = pi.product_id
            LEFT JOIN productprices pp ON c.product_id = pp.product_id 
            WHERE (c.sender_id = ? AND c.receiver_id = ? AND c.product_id = ?) 
            OR (c.sender_id = ? AND c.receiver_id = ? AND c.product_id = ?) 
            ORDER BY c.sent_at ASC";
$stmtChat = $conn->prepare($chatSql);

if ($stmtChat === false) {
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmtChat->bind_param("iiiiii", $_SESSION['user_id'], $participant_id, $product_id, $participant_id, $_SESSION['user_id'], $product_id);
$stmtChat->execute();
$resultChat = $stmtChat->get_result();

$chats = '';
$displayedMessages = []; // Array to track displayed chat IDs
$latestReadChatId = null; // Variable to track the latest "read" chat sent by the current user

while ($row = $resultChat->fetch_assoc()) {
    // Skip duplicate messages based on chat_id
    if (in_array($row['chat_id'], $displayedMessages)) {
        continue;
    }
    
     $displayedMessages[] = $row['chat_id']; // Mark chat_id as displayed
    $isCurrentUser = $row['sender_id'] == $_SESSION['user_id'];
    $messageClass = $isCurrentUser ? 'me' : 'you';
    $buttonClass = $isCurrentUser ? 'rate-seller-button-me' : 'rate-seller-button-you';

    if ($row['message_type'] === 'normal') {
        // Normal message display
        $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '</div>';
    } elseif ($row['message_type'] === 'inquiry') {
        // Decode the JSON-encoded image URL as an associative array
        $images = json_decode($row['image_url'], true);
        $firstImage = !empty($images) ? $images[0] : 'default.jpg'; // Fallback to a default image if none

        $chats .= '<div class="bubble ' . $messageClass . '">'
            . '<img src="../product_images/' . $firstImage . '" alt="Product Image" class="inquiry-image">'
            . '<div class="price">Price: ₱' . number_format($row['current_price'], 2) . '</div>'  // Format price to two decimal places
            . '<div class="message-text">' . htmlspecialchars($row['message']) . '</div>'
            . '</div>';
    } elseif ($row['message_type'] === 'transaction') {
        $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '</div>';
    } elseif ($row['message_type'] === 'image') {
        // Decode the image path
        $imageFilename = htmlspecialchars($row['message']); // Assuming $row['message'] contains the image filename
        $imagePath = '../uploads/' . $imageFilename; // Relative path to the uploads folder

        // Append the image to the chat output
        $chats .= '<div class="bubble ' . $messageClass . '"><img src="' . $imagePath . '" alt="Image" class="send-image"></div>';
    } elseif ($row['message_type'] === 'offer') {
    // Determine the class for the offer status based on its value
    $statusClass = '';
    if ($row['offer_status'] === 'Pending') {
        $statusClass = 'pending';
    } elseif ($row['offer_status'] === 'Accepted') {
        $statusClass = 'accepted';
    } elseif ($row['offer_status'] === 'Rejected') {
        $statusClass = 'rejected';
    }

    // If the current user is the receiver, check the offer status
    if ($row['receiver_id'] == $_SESSION['user_id']) {
        if ($row['offer_status'] === 'Accepted' || $row['offer_status'] === 'Rejected') {
            // Display the status without buttons
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '
            <span class="offer-status ' . $statusClass . '">' . htmlspecialchars($row['offer_status']) . '</span></div>';
        } else {
            // Offer message display with accept/reject buttons for Pending status
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '
            <div class="action">
            <button class="accept-button" data-action="accept" data-offer-id="' . htmlspecialchars($row['offer_id']) . '" data-chat-id="' . htmlspecialchars($row['chat_id']) . '">Accept</button>
            <button class="reject-button" data-action="reject" data-offer-id="' . htmlspecialchars($row['offer_id']) . '" data-chat-id="' . htmlspecialchars($row['chat_id']) . '">Reject</button>
            </div></div>';
        }
    } else {
        // If the user is the sender (buyer), just show the message without buttons
        $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '
        <span class="offer-status ' . $statusClass . '">' . htmlspecialchars($row['offer_status']) . '</span></div>';
    }
    } elseif ($row['message_type'] === 'rate') {
    // Show "Rate Seller" button only if the product is not rated
    if ($productRated == 0) { // Only show button if product is not rated
        if ($row['receiver_id'] == $_SESSION['user_id']) {
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '
                <div class="rate-action">
                    <button class="' . $buttonClass . '" data-chat-id="' . htmlspecialchars($row['chat_id']) . '" onclick="openRatingModal(' . htmlspecialchars($row['chat_id']) . ')">Rate Seller</button>
                </div>
            </div>';
        } else {
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '</div>';
        }
    } else {
        // If the product is already rated, display a different message or button
        if ($row['receiver_id'] == $_SESSION['user_id']) {
            // Seller already rated, display "Thank You" or "Rated" message
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '
                <div class="rate-action">
                    <span class="rated-message">Thank you for your rating!</span>
                </div>
            </div>';
        } else {
            $chats .= '<div class="bubble ' . $messageClass . '">' . htmlspecialchars($row['message']) . '</div>';
        }
    }
}

    // Update the latest read chat ID if the current user is the sender and the message is read
    if ($isCurrentUser && $row['read_status'] === 'read') {
        $latestReadChatId = $row['chat_id'];
    }
}

$stmtChat->close();

// Retrieve product name
$productSql = "SELECT product_name FROM products WHERE product_id = ?";
$stmtProduct = $conn->prepare($productSql);

if ($stmtProduct === false) {
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmtProduct->bind_param("i", $product_id);
$stmtProduct->execute();
$stmtProduct->bind_result($product_name);
$stmtProduct->fetch();
$stmtProduct->close();

// Close connection
$conn->close();

// Return JSON response
$response = json_encode([
    'chats' => $chats,
    'product_name' => $product_name
]);

if ($response === false) {
    echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
} else {
    echo $response;
}
?>
