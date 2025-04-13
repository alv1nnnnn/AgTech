<?php
session_start();
include '../Connection/connection.php'; // Include your database connection

$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

if ($user_id) {
    $query = "SELECT first_name, last_name, phone_number, email, age, birthdate, province, municipality, barangay, postal_code, profile FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    // Check if user data was found
    if ($userData) {
        echo json_encode($userData);
    } else {
        echo json_encode(["error" => "User not found"]);
    }
} else {
    echo json_encode(["error" => "User not logged in"]);
}
?>
