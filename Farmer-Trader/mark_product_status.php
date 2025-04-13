<?php
session_start();

// Include the database connection file
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure product_id and action are passed through POST
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Validate action to be either "sold" or "available"
    if ($product_id > 0 && in_array($action, ['sold', 'available'])) {
        // Set the appropriate status based on action
        if ($action === 'sold') {
            $status = 'sold'; // Product is sold
        } elseif ($action === 'available') {
            $status = 'active'; // Product is available (active)
        }

        // Update the product status in the database
        $updateSql = "UPDATE products SET status = ? WHERE product_id = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->bind_param("si", $status, $product_id);

        if ($stmtUpdate->execute()) {
            // If the action is "available", also update the transaction status to "Pending"
            if ($action === 'available') {
                // Update the transaction status to "Pending" based on the product_id in the transaction_details
                $updateTransactionSql = "UPDATE transaction t 
                                          JOIN transaction_details td ON t.transaction_id = td.transaction_id
                                          SET t.status = 'Pending' 
                                          WHERE td.product_id = ?";
                $stmtUpdateTransaction = $conn->prepare($updateTransactionSql);
                $stmtUpdateTransaction->bind_param("i", $product_id);

                if ($stmtUpdateTransaction->execute()) {
                    // Successfully updated transaction status to Pending
                    $response = [
                        'status' => 'success',
                        'message' => 'Product status updated to available, and transaction status updated to Pending.',
                    ];
                } else {
                    // Error updating transaction status
                    $response = [
                        'status' => 'error',
                        'message' => 'Error updating transaction status.',
                    ];
                }

                // Close the transaction update statement
                $stmtUpdateTransaction->close();
            } else {
                // Successfully updated product status
                $response = [
                    'status' => 'success',
                    'message' => 'Product status updated to sold.',
                ];
            }
        } else {
            // Error updating product status
            $response = [
                'status' => 'error',
                'message' => 'Error updating product status.',
            ];
        }

        // Close the product status update statement
        $stmtUpdate->close();
    } else {
        // Invalid request parameters
        $response = [
            'status' => 'error',
            'message' => 'Invalid product ID or action.',
        ];
    }

    // Return the response as JSON
    echo json_encode($response);
}
?>
