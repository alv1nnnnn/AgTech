<?php
session_start(); // Start the session
include "../Connection/connection.php"; // Include the database connection

header('Content-Type: application/json'); // Set response type to JSON

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id']; // Get the user ID from the session

try {
    // SQL query to count unread messages
    $query = "SELECT COUNT(*) AS unread_count FROM chat WHERE receiver_id = ? AND read_status = 'unread'";
    $stmt = $conn->prepare($query); // Prepare the statement
    $stmt->bind_param("i", $user_id); // Bind the user ID parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Return the unread message count
    echo json_encode(['status' => 'success', 'unread_count' => $row['unread_count']]);
    
    $stmt->close(); // Close the statement
} catch (Exception $e) {
    // Handle errors
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn->close(); // Close the database connection
}
