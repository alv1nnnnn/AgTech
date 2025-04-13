<?php
session_start();
header('Content-Type: application/json');

// Database connection details
require_once '../Connection/connection.php';

if (isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);

    // Prepare and execute SQL query to get product name
    $sql = "SELECT product_name FROM Products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($product_name);
    $stmt->fetch();
    
    // Return product name
    echo $product_name;

    // Close statement
    $stmt->close();
}

$conn->close();
?>
