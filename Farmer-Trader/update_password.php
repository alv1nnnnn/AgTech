<?php
// Include the database connection
require_once '../Connection/connection.php';

// Start session to retrieve current user info
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $userId = $_SESSION['user_id']; // Assumes user_id is stored in session

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
        exit;
    }

    // Password complexity check
    if (strlen($newPassword) < 8 || 
        !preg_match('/[A-Z]/', $newPassword) || 
        !preg_match('/[a-z]/', $newPassword) || 
        !preg_match('/[0-9]/', $newPassword) || 
        !preg_match('/[\W]/', $newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'Password does not meet requirements.']);
        exit;
    }

    // Fetch current password from database
    $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($currentPassword, $hashedPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Hash the new password
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in database
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
    $stmt->bind_param('si', $newHashedPassword, $userId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
