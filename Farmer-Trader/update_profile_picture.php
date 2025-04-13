<?php
session_start();

// Include the database connection file
require_once '../Connection/connection.php'; // Adjust the path if needed

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User is not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ensure a file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or an upload error occurred.']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$upload_dir = 'uploads/profile_pictures/'; // Directory to store profile pictures
$max_file_size = 2 * 1024 * 1024; // Max file size (2MB)

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG and PNG are allowed.']);
    exit;
}

// Validate file size
if ($file['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds the 2MB limit.']);
    exit;
}

// Create the upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate a unique file name
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_file_name = uniqid('profile_', true) . '.' . $extension;
$file_path = $upload_dir . $new_file_name;

// Move the file to the upload directory
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    exit;
}

// Update the user's profile in the database (use "profile" column)
$stmt = $conn->prepare("UPDATE users SET profile = ? WHERE user_id = ?");
$stmt->bind_param('si', $file_path, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully.', 'profile' => $file_path]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update profile picture in the database.']);
}

// Clean up
$stmt->close();
$conn->close();
?>
