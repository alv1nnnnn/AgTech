<?php
session_start();
require_once '../Connection/connection.php';

header('Content-Type: application/json'); // Make sure to return JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

// Function to generate a random 11-digit transaction ID
function generateTransactionId($conn) {
    $transaction_id = null;

    // Keep trying until a unique ID is found
    do {
        $transaction_id = rand(10000000000, 99999999999); // Generates a random 11-digit number

        // Ensure the generated ID is unique
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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offer_id = intval($_POST['offer_id']);
    $action = $_POST['action'];
    $status = '';

    // Process the accept/reject action
    if ($action === 'accept') {
        // Step 1: Get the offer details
        $offerSql = "SELECT * FROM tradeoffer WHERE offer_id = ? AND receiver_id = ?";
        $stmtOffer = $conn->prepare($offerSql);
        $stmtOffer->bind_param("ii", $offer_id, $_SESSION['user_id']);
        $stmtOffer->execute();
        $offerResult = $stmtOffer->get_result();

        if ($offerResult->num_rows > 0) {
            $offer = $offerResult->fetch_assoc();
            $sender_id = $offer['sender_id'];
            $product_id = $offer['product_id'];
            $offer_amount = $offer['offer_amount'];

            // Step 2: Update the offer status
            $updateOfferSql = "UPDATE tradeoffer SET offer_status = 'Accepted' WHERE offer_id = ?";
            $updateStmt = $conn->prepare($updateOfferSql);
            $updateStmt->bind_param("i", $offer_id);
            $updateStmt->execute();

            // Step 3: Generate a unique transaction ID
            $transaction_id = generateTransactionId($conn);

            // Step 4: Insert into transactions table with buyer_id and seller_id
            $transactionSql = "INSERT INTO transaction (transaction_id, buyer_id, seller_id, status, timestamp) VALUES (?, ?, ?, 'Pending', NOW())";
            $transactionStmt = $conn->prepare($transactionSql);
            $transactionStmt->bind_param("iii", $transaction_id, $sender_id, $_SESSION['user_id']); // Receiver as buyer, sender as seller
            $transactionStmt->execute();

            // Step 5: Insert into transaction_details table
            $transactionDetailsSql = "INSERT INTO transaction_details (transaction_id, product_id, amount) VALUES (?, ?, ?)";
            $transactionDetailsStmt = $conn->prepare($transactionDetailsSql);
            $transactionDetailsStmt->bind_param("iii", $transaction_id, $product_id, $offer_amount);
            $transactionDetailsStmt->execute();

            // Step 6: Clean up and return success
            echo json_encode(['success' => true, 'status' => 'Accepted', 'transaction_id' => $transaction_id]);
        } else {
            echo json_encode(['error' => 'Offer not found or invalid.']);
        }

        // Close prepared statements
        $stmtOffer->close();
        $updateStmt->close();
        $transactionStmt->close();
        $transactionDetailsStmt->close();

    } elseif ($action === 'reject') {
        // Update the offer status
        $updateOfferSql = "UPDATE tradeoffer SET offer_status = 'Rejected' WHERE offer_id = ?";
        $updateStmt = $conn->prepare($updateOfferSql);
        $updateStmt->bind_param("i", $offer_id);
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'status' => 'Rejected']);
        } else {
            echo json_encode(['error' => 'Failed to update offer.']);
        }

        // Close prepared statement
        $updateStmt->close();
    } else {
        echo json_encode(['error' => 'Invalid action.']);
    }
}

$conn->close();
?>
