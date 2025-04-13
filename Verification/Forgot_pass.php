<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require "../Connection/connection.php";

// Start session to use session variables
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_POST['method'] === 'email') {
        // Email method selected
        $email = $conn->real_escape_string($_POST['email']);
        
        // Check if email exists in the user table
        $sql = "SELECT user_id FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Email exists
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Store reset token and expiration in the passwordreset table
            $insert_sql = "
                INSERT INTO passwordreset (reset_id, user_id, expires_at)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    reset_id = VALUES(reset_id), 
                    expires_at = VALUES(expires_at)
            ";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sis", $reset_token, $user_id, $expires_at);

            if ($insert_stmt->execute()) {
                // Send email with reset link using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'theagtechteam@gmail.com';
                    $mail->Password = 'nqtqbognkhvtbkxg'; // Use environment variables for security
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('alvinnario07@gmail.com', 'AgTech Password Reset');
                    $mail->addAddress($email);

                    $reset_link = 'https://agtech-livestock.com/Verification/Reset_pass.php?token=' . $reset_token;

                    $mail->isHTML(true);
                    $mail->Subject = '[AgTech] Please reset your password';
                    $mail->Body = '
                        <div style="text-align: center;">
                            <img src="../images/AgTech-Logo.png" 
                                alt="AgTech Logo" 
                                style="width:100px; height:auto;">
                            <h3>Reset your AgTech password</h3>
                            <a href="' . $reset_link . '" 
                               style="padding: 10px 20px; font-size: 16px; color: #fff; background-color: #007bff; border-radius: 5px; text-decoration: none;">
                               Reset your password
                            </a>
                        </div>
                    ';

                    $mail->send();
                    header("Location: password_reset_sent.php");
                    exit;
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $_SESSION['error'] = "Could not initiate the password reset process. Please try again.";
            }

            $insert_stmt->close();
        } else {
            $_SESSION['error'] = "The provided email address is not registered.";
        }
        $stmt->close();
    
    } elseif ($_POST['method'] == 'phone') {
        // Phone method selected
        $phone = $_POST['phone'];

        // Ensure the phone number is in E.164 format
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = '+63' . $phone;

        // Infobip API credentials
        $baseUrl = 'https://api.infobip.com';
        $apiKey = '829883cf2b1e6a45695230e6446500e6-7b0ddb52-3291-401b-899d-b245a899ab36';
        $sender = ''; // Set the sender ID as approved by Infobip

        $otp = rand(100000, 999999);

        $url = $baseUrl . "/sms/2/text/advanced";

        $payload = [
            "messages" => [
                [
                    "from" => $sender,
                    "destinations" => [
                        ["to" => $phone]
                    ],
                    "text" => "Your verification code is: $otp"
                ]
            ]
        ];

        $headers = [
            "Authorization: App $apiKey",
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            session_start();
            $_SESSION['otp'] = $otp;
            $_SESSION['phone'] = $phone;

            // Hash the phone number
            $hashed_phone = hash('sha256', $phone_without_country_code);

            // Redirect to Reset_code.php with the hashed phone number
            header("Location: Reset_code.php?$hashed_phone");
            exit();
        } else {
            echo "Error: Unable to send SMS. Response: " . $response;
        }
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/forgot_reset.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<style>
    .swal-button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: auto;
            width: 100px;
        }
        .swal-button--confirm {
            background-color: #DC143C;
        }
        .swal-button--confirm:hover {
            background-color: darkred !important;
        }
</style>
<body>
    <div class="forgot-password-container">
        <div class="logo-container">
            <img src="../images/AgTech-Logo.png" alt="Logo" class="logo"> 
        </div>
        <h2>Forgot Password?</h2>
        <p>Enter your email address or phone number and we'll send you instructions to reset your password.</p>
        <form action="Forgot_pass.php" method="POST" class="forgot-password-form">
            <div class="form-group">
                <label for="method">Select Method:</label>
                <select id="method" name="method" onchange="toggleInputFields()">
                    <option value="email">Email</option>
                    <option value="phone">Phone Number</option>
                </select>
            </div>
            <div class="form-group" id="emailField">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group" id="phoneField" style="display: none;">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <button type="submit">Continue</button>
        </form>
        <p><a href="../Login/Login.php" class="back-to-login">Back to Login</a></p>
    </div>
    <script>
        function toggleInputFields() {
            var method = document.getElementById("method").value;
            var emailField = document.getElementById("emailField");
            var phoneField = document.getElementById("phoneField");

            if (method === "email") {
                emailField.style.display = "block";
                phoneField.style.display = "none";
            } else {
                emailField.style.display = "none";
                phoneField.style.display = "block";
            }
        }

        <?php
        if (isset($_SESSION['error'])) {
            echo "swal({
                    title: 'Error!',
                    text: '" . $_SESSION['error'] . "',
                    icon: 'error',
                    button: {
                        className: 'swal-button--confirm'
                    }
                });";
            unset($_SESSION['error']); // Clear the error message after displaying it
        }
        ?>
    </script>
</body>
</html>
