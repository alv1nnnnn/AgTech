<?php
session_start();

// Database connection parameters
require_once '../Connection/connection.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $reset_sql = "UPDATE userverification SET login_attempts=0, is_locked=0, lock_time=NULL WHERE user_id='$user_id'";
    if ($conn->query($reset_sql) === TRUE) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reset login attempts.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No user session found.']);
}

$conn->close();
?>
