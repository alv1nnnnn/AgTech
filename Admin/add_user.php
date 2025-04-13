<?php
// add_user.php
session_start();
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mysqli_real_escape_string($conn, $_POST['newFirstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['newLastName']);
    $email = mysqli_real_escape_string($conn, $_POST['newEmail']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['newPhoneNumber']);
    $password = mysqli_real_escape_string($conn, $_POST['newPassword']); // Get the password input
    
    // Encrypt the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Assign user type value to admin
    $userType = 'admin';

    // Prepare the insert query
    $query = "INSERT INTO user (first_name, last_name, email, phone_number, password, user_type) VALUES ('$firstName', '$lastName', '$email', '$phoneNumber', '$hashedPassword', '$userType')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }

    mysqli_close($conn);
}
?>
