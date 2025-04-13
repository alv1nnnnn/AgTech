<?php
include '../Connection/connection.php';
session_start();

// Retrieve JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Extract data from JSON
$agreedPrice = $data['agreedPrice'];
$quantity = $data['quantity'];
$product_id = $data['product_id'];
$receiver_id = $data['receiver_id'];
$sender_id = $_SESSION['user_id']; // Logged-in user's ID

function generateTransactionId($conn) {
    do {
        $transaction_id = rand(10000000000, 99999999999); // Generates a random 11-digit number

        // Check if the generated ID is unique
        $checkSql = "SELECT COUNT(*) FROM transaction WHERE transaction_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $transaction_id);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

    } while ($count > 0); // Repeat until a unique ID is found

    return $transaction_id;
}

try {
    // Prepare chat message insert
    $message = "Agreed price: " . $agreedPrice . ", Quantity: " . $quantity;

    // Dynamically assign the role: 'seller' if the sender is the logged-in user, else 'buyer'
    $role = ($sender_id === $_SESSION['user_id']) ? 'seller' : 'buyer';

    // Insert chat message into the chat table
    $stmt = $conn->prepare("
        INSERT INTO chat (sender_id, receiver_id, product_id, message, sent_at, role, message_type)
        VALUES (?, ?, ?, ?, NOW(), ?, 'transaction')
    ");
    $stmt->bind_param("iiiss", $sender_id, $receiver_id, $product_id, $message, $role);
    $stmt->execute();

    $transaction_id = generateTransactionId($conn);

    // Get the current date and time in the Asia timezone
    $timezone = new DateTimeZone('Asia/Manila'); // Change 'Asia/Kolkata' to your preferred timezone
    $date = new DateTime('now', $timezone);
    $formattedTimestamp = $date->format('Y-m-d H:i:s');

    // Insert into transaction table
    $stmt = $conn->prepare("
        INSERT INTO transaction (transaction_id, buyer_id, seller_id, status, timestamp)
        VALUES (?, ?, ?, 'Completed', ?)
    ");
    $stmt->bind_param("iiis", $transaction_id, $receiver_id, $sender_id, $formattedTimestamp);
    $stmt->execute();

    // Insert into transaction_details table
    $amount = $agreedPrice * $quantity;
    $stmt = $conn->prepare("
        INSERT INTO transaction_details (transaction_id, product_id, AgreedPrice, quantity, amount)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iidid", $transaction_id, $product_id, $agreedPrice, $quantity, $amount);
    $stmt->execute();

    $conn->commit();
    $conn->autocommit(true);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(true);
    
    // Log the error for debugging
    error_log("Error in processTransaction.php: " . $e->getMessage());

    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
