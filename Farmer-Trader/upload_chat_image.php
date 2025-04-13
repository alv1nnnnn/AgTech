<?php
session_start();

date_default_timezone_set('Asia/Manila');

require '../Connection/connection.php'; // Adjust the path as necessary

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $receiverId = $_POST['receiver_id'];
    $productId = $_POST['product_id'];

    // Check if files are uploaded
    if (isset($_FILES['productImage'])) {
        $uploadDir = '../uploads/'; // Updated to the new upload directory

        // Ensure that the uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
        }

        $imageNames = []; // Array to store uploaded image names

        // Handle each uploaded file
        foreach ($_FILES['productImage']['name'] as $key => $imageName) {
            // Check for upload errors
            if ($_FILES['productImage']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['productImage']['tmp_name'][$key];
                $targetFilePath = $uploadDir . basename($imageName);

                // Move the uploaded file to the target directory
                if (move_uploaded_file($tmp_name, $targetFilePath)) {
                    $imageNames[] = $targetFilePath; // Store the path of the uploaded image
                    $sentAt = date('Y-m-d H:i:s'); // Get current time in Asia/Manila timezone

                    // Insert a new record into the chat table with the image path
                    $stmt = $conn->prepare("INSERT INTO chat (sender_id, receiver_id, product_id, message, sent_at, role, message_type) VALUES (?, ?, ?, ?, ?, ?, 'image')");
                    $role = 'seller'; // Example role, set as necessary

                    // Use the path of the uploaded image as the message
                    $stmt->bind_param("iiisss", $userId, $receiverId, $productId, $targetFilePath, $sentAt, $role);

                    if (!$stmt->execute()) {
                        echo json_encode(['success' => false, 'message' => 'Failed to insert chat record for: ' . htmlspecialchars(basename($imageName))]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error uploading file: ' . htmlspecialchars(basename($imageName))]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error with file upload: ' . $_FILES['productImage']['error'][$key]]);
            }
        }

        // If all uploads are successful
        if (!empty($imageNames)) {
            echo json_encode(['success' => true, 'message' => 'Images uploaded and chat records created.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No images were successfully uploaded.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No images uploaded or there was an upload error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
