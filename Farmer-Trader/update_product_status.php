<?php
require_once '../Connection/connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Extract variables from decoded JSON data
    $product_id = $data['product_id'] ?? null;
    $status = $data['status'] ?? null;

    if ($product_id && $status) {
        // Update the product status in the database
        $query = "UPDATE products SET status = ? WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $product_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Status updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update status."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Missing product_id or status."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>