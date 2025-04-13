<?php
// update_user.php
session_start();
require_once '../Connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['userId']);
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);

    $query = "UPDATE user SET first_name = '$firstName', last_name = '$lastName', email = '$email', phone_number = '$phoneNumber' WHERE user_id = $userId";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }

    mysqli_close($conn);
}
?>
