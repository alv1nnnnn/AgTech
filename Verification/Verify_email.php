<?php
session_start();
require '../vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Generate a 6-digit OTP code
    $otp_code = rand(100000, 999999);
    
    // Store the OTP code in the session
    $_SESSION['otp_code'] = $otp_code;
    $_SESSION['otp_email'] = $email;
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;
        $mail->Username = 'theagtechteam@gmail.com'; // SMTP username
        $mail->Password = 'nqtqbognkhvtbkxg'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('theagtechteam@gmail.com', 'AgTech Verification Code');
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is <b>$otp_code</b>";
        $mail->AltBody = "Your OTP code is $otp_code";

        // Send the email
        $mail->send();
        header('Location: Otp_email.php');
        exit();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Send OTP</title>
    <link rel="stylesheet" href="../css/login_register.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>
    <div class="verify-container">
        <h2 class="verification-header">OTP VERIFICATION</h2>
        <p>Enter your email to get a verification code.</p>
        <form class="verification-form" action="Verify_email.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
            </div>
            <button type="submit">SEND CODE</button>
            <p><a href="Verify_phone.php" class="use-phone-link">Use my phone number instead</a></p>
        </form>
    </div>
</body>
</html>
