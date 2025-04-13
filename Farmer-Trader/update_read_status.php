<?php
session_start(); 

require_once '../Connection/connection.php'; // Include your database connection

header('Content-Type: application/json'); // Ensure JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;

    if ($userId && $productId) {
        // Prepare and execute the SQL statement to update read_status
        $stmt = $conn->prepare("UPDATE chat SET read_status = 'read' WHERE receiver_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Status updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user_id or product_id'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>
