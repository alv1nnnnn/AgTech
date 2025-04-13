<?php
require '../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];

    // Ensure the phone number is in E.164 format
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters
    $phone = '+63' . $phone; // Prepend the country code (e.g., +63 for the Philippines)

    // Infobip API credentials
    $baseUrl = 'https://api.infobip.com';
    $apiKey = '829883cf2b1e6a45695230e6446500e6-7b0ddb52-3291-401b-899d-b245a899ab36';
    $sender = ''; // Set the sender ID as approved by Infobip

    // Generate a random 6-digit verification code
    $otp = rand(100000, 999999);

    // Infobip API endpoint
    $url = $baseUrl . "/sms/2/text/advanced";

    // Prepare the request payload
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

    // Setup the HTTP headers with the API key for authentication
    $headers = [
        "Authorization: App $apiKey",
        "Content-Type: application/json",
        "Accept: application/json"
    ];

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request and handle the response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode == 200) {
        // Start the session and store the OTP
        session_start();
        $_SESSION['otp'] = $otp;
        $_SESSION['phone'] = $phone;

        // Redirect to OtpPN.php
        header("Location: OtpPN.php");
        exit(); // Ensure no further code is executed after the redirection
    } else {
        echo "Error: Unable to send SMS. Response: " . $response;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>
    <div class="verify-container">
        <h2 class="verification-header">OTP VERIFICATION</h2>
        <p>Enter your phone to get a verification code.</p>
        <form class="verification-form" action="Verify_phone.php" method="POST">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="phone" id="phone" name="phone" placeholder="09xxxxxxxxx" required>
            </div>
            <button type="submit">SEND CODE</button>
            <p><a href="Verify_email.php" class="use-phone-link">Use my email instead</a></p>
        </form>
    </div>
</body>
</html>