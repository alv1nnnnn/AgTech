<?php
header('Content-Type: application/json'); // Ensure response is JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Invalid Email',
            'message' => 'Please provide a valid email address.'
        ]);
        exit;
    }

    // Set the recipient and headers for sending email
    $to = "theagtechteam@gmail.com";
    $subject = "Message from $name";
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: $email";

    // Attempt to send email and return response
    if (mail($to, $subject, $body, $headers)) {
        echo json_encode([
            'status' => 'success',
            'title' => 'Message Sent',
            'message' => 'Thank you for contacting us! Your message has been sent.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'title' => 'Message Failed',
            'message' => 'Unable to send your message. Please try again later.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'title' => 'Invalid Request',
        'message' => 'This request is not valid.'
    ]);
}
?>
