<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['product_id'];

    // Connect to the database
    $conn = new mysqli('localhost', 'root', '', 'agtech');

    // Check connection
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed']));
    }

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT user_id FROM userproducts WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Return the user_id as a JSON response
    echo json_encode(['user_id' => $user_id]);
}
?>
