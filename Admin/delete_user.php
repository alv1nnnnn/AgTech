<?php
// delete_user.php
session_start();
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = mysqli_real_escape_string($conn, $data['id']); // Ensure user_id is sanitized

    // Prepare the delete query
    $query = "DELETE FROM user WHERE user_id = '$userId'";

    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]); // Return error message for debugging
    }

    mysqli_close($conn);
}
?>
