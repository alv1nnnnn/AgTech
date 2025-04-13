<?php
session_start();
require '../vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email exists in the session
    if (isset($_SESSION['otp_email'])) {
        $email = $_SESSION['otp_email'];
        
        // Generate a new 6-digit OTP code
        $otp_code = rand(100000, 999999);
        
        // Update the OTP code in the session
        $_SESSION['otp_code'] = $otp_code;
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'theagtechteam@gmail.com';
            $mail->Password = 'nqtqbognkhvtbkxg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('theagtechteam@gmail.com', 'AgTech Verification Code');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Resent OTP Code';
            $mail->Body    = "Your new OTP code is <b>$otp_code</b>";
            $mail->AltBody = "Your new OTP code is $otp_code";

            // Send the email
            $mail->send();
            echo json_encode(['status' => 'success', 'message' => 'OTP resent successfully.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => "Mailer Error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found in session.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
