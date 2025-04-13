<?php
require_once '../Connection/connection.php'; // Database connection

// Set the default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rater_id = $_SESSION['user_id'];  // Assuming the logged-in user ID is stored in the session
    $product_id = $_POST['product_id'];
    $ratedUserId = $_POST['rated_user_id']; // User being rated
    $rating_value = $_POST['rating_value']; // Rating value from JS
    $review = !empty($_POST['review']) ? $_POST['review'] : ''; // Optional review text
    $role = $_POST['role']; // Get the role from the POST data
    $created_at = date('Y-m-d H:i:s'); // Current timestamp in Asia/Manila timezone

    // Insert the rating into the `review` table
    $sql = "INSERT INTO review (rater_id, rated_user_id, role, product_id, rating_value, comment, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iisidss", $rater_id, $ratedUserId, $role, $product_id, $rating_value, $review, $created_at);
        
        if ($stmt->execute()) {
            // If the rating is successfully submitted, update the product's 'rated' column to 1
            $update_rated_sql = "UPDATE products SET rated = 1 WHERE product_id = ?";
            if ($update_stmt = $conn->prepare($update_rated_sql)) {
                $update_stmt->bind_param("i", $product_id);
                if ($update_stmt->execute()) {
                    // If the rating is successful, update the product status to 'sold'
                    $update_sql = "UPDATE products SET status = 'sold' WHERE product_id = ?";
                    if ($update_stmt2 = $conn->prepare($update_sql)) {
                        $update_stmt2->bind_param("i", $product_id);
                        if ($update_stmt2->execute()) {
                            // Update the transaction status to 'Completed' for the specific buyer
                            $transaction_update_sql = "
                                UPDATE transaction 
                                SET status = 'Completed' 
                                WHERE transaction_id IN (
                                    SELECT transaction_id 
                                    FROM transaction_details 
                                    WHERE product_id = ? 
                                ) AND buyer_id = ?";
                            
                            if ($transaction_update_stmt = $conn->prepare($transaction_update_sql)) {
                                $transaction_update_stmt->bind_param("ii", $product_id, $ratedUserId);
                                if ($transaction_update_stmt->execute()) {
                                    echo json_encode(['success' => true, 'message' => 'Rating submitted, product marked as sold, transaction completed for the specific buyer.']);
                                } else {
                                    echo json_encode(['success' => false, 'message' => 'Rating submitted and product marked as sold, but failed to update transaction status: ' . $transaction_update_stmt->error]);
                                }
                                $transaction_update_stmt->close();
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Error preparing transaction update statement: ' . $conn->error]);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Rating submitted, but failed to mark product as sold: ' . $update_stmt2->error]);
                        }
                        $update_stmt2->close();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error preparing product status update: ' . $conn->error]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating rated column: ' . $update_stmt->error]);
                }
                $update_stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error preparing rated column update statement: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error submitting rating: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing rating statement: ' . $conn->error]);
    }
}

$conn->close();
?>