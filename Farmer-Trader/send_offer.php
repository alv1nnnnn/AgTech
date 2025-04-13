<?php
session_start();  // Start the session to access $_SESSION
require_once '../Connection/connection.php';

// Set the default time zone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Get the current time in the Asia/Manila time zone
$manila_time = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user is logged in and session is set
    if (!isset($_SESSION['user_id'])) {
        die("User not logged in. Please log in to continue.");
    }

    // Get the form data
    $product_id = $_POST['product_id'];
    $receiver_id = $_POST['receiver_id']; // Ensure this exists in user table
    $price_offer = $_POST['price_offer'];
    $sender_id = $_SESSION['user_id']; // Now $_SESSION['user_id'] will work

    // Check if receiver_id exists in the user table
    $check_user_sql = "SELECT * FROM user WHERE user_id = ?";
    $check_user_stmt = $conn->prepare($check_user_sql);
    $check_user_stmt->bind_param("i", $receiver_id);
    $check_user_stmt->execute();
    $result = $check_user_stmt->get_result();

    if ($result->num_rows === 0) {
        die("Receiver ID does not exist in the user table.");
    }

    // Insert into tradeoffer table
    $offer_sql = "INSERT INTO tradeoffer (sender_id, receiver_id, product_id, offer_amount, offer_status, status, timestamp)
                  VALUES (?, ?, ?, ?, 'Pending', 'Active', NOW())";
    
    $stmt = $conn->prepare($offer_sql);
    $stmt->bind_param("iiii", $sender_id, $receiver_id, $product_id, $price_offer);
    
    if ($stmt->execute()) {
        $offer_id = $conn->insert_id; // Get the last inserted offer_id

        // For offer messages
        $message_type = 'offer';

        // Insert into chat table with offer_id
        $message = "I sent an offer of ₱" . $price_offer . " for the product.";
        $chat_sql = "INSERT INTO chat (sender_id, receiver_id, message, sent_at, product_id, offer_id, role, read_status, message_type) 
                    VALUES (?, ?, ?, ?, ?, ?, 'buyer', 'unread', ?)";

        $chat_stmt = $conn->prepare($chat_sql);
        $chat_stmt->bind_param("iississ", $sender_id, $receiver_id, $message, $manila_time, $product_id, $offer_id, $message_type);

        if ($chat_stmt->execute()) {
    // Prepare data for the redirect
    $redirectData = [
        'user_id' => $receiver_id,
        'product_id' => $product_id,
        'first_name' => '', // Fetch the first_name from the database if needed
        'last_name' => ''   // Fetch the last_name from the database if needed
    ];

    // Fetch first_name and last_name of the receiver (optional)
    $userSql = "SELECT first_name, last_name FROM user WHERE user_id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $receiver_id);
    $userStmt->execute();
    $userStmt->bind_result($first_name, $last_name);
    if ($userStmt->fetch()) {
        $redirectData['first_name'] = $first_name;
        $redirectData['last_name'] = $last_name;
    }
    $userStmt->close();

    // Encode the data
    $encodedData = base64_encode(json_encode($redirectData));

    // Redirect to Farmer-inbox.php with the Buying tab active and data
    header("Location: farmer-inbox.php?tab=buying&data=$encodedData");
    exit();
        } else {
            echo "Error inserting chat: " . $conn->error;
        }
    } else {
        echo "Error inserting offer: " . $conn->error;
    }

    $stmt->close();
    $chat_stmt->close();
}

$conn->close();
?>
