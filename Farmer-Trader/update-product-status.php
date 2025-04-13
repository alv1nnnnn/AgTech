<?php
session_start();

// Include the database connection file
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming the product_id and action are passed through POST
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Validate that the action is either "sold" or "available"
    if ($product_id > 0 && in_array($action, ['sold', 'available'])) {
        // Set the appropriate product status based on the action
        if ($action === 'sold') {
            $status = 'sold';
        } elseif ($action === 'available') {
            $status = 'active'; // Change to active when marking as available
        }

        // Update the product status in the database
        $updateSql = "UPDATE products SET product_status = ? WHERE product_id = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->bind_param("si", $status, $product_id);

        if ($stmtUpdate->execute()) {
            // Successful update
            $response = [
                'status' => 'success',
                'message' => 'Product status updated successfully.',
            ];
        } else {
            // Error in updating the status
            $response = [
                'status' => 'error',
                'message' => 'Error updating product status.',
            ];
        }

        // Close the statement
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
