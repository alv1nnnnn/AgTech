<?php
require_once '../Connection/connection.php';

// Get email from POST data
$email = isset($_POST['email']) ? $_POST['email'] : '';

// Check if email exists in the database
$query = "SELECT COUNT(*) AS count FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Respond with the result (whether the email exists or not)
echo json_encode(['exists' => $row['count'] > 0]);
?>
