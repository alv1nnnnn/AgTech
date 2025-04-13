<?php
// get_user.php
session_start();
require_once '../Connection/connection.php';

if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $query = "SELECT user_id, first_name, last_name, email, phone_number FROM user WHERE user_id = $userId";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $user = mysqli_fetch_assoc($result);
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    mysqli_close($conn);
}
?>
