<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'][0];

    if ($entered_otp == $_SESSION['otp']) {
        // Redirect to Reset_pass.php
        $hashed_phone = hash('sha256', $_SESSION['phone']); // Hashing the phone number
        header("Location: Reset_pass.php?" . urlencode($hashed_phone));
        exit(); // Ensure no further code is executed after the redirection
    } else {
        $_SESSION['error'] = "Invalid OTP. Please try again.";
    }
}

// Assume $_SESSION['phone'] initially contains the full phone number with country code, e.g., +639123456789
// Extract the phone number without the country code
$phone_with_country_code = $_SESSION['phone'];
$phone_without_country_code = substr($phone_with_country_code, 3); // Assuming country code is +63 (3 characters)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="../css/login_register.css?v=<?php echo time(); ?>">
    <script src="../js/verify.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="icon" href="../images/logo.ico" type="image/x-icon">
</head>
<body>
    <div class="verification-container">
        <h2 class="verification-header">ENTER SECURITY CODE</h2>
        <p>We sent a code to your phone number. This code is needed before proceeding in resetting password.</p>
        <form class="verification-form" action="Reset_code.php" method="POST">
            <div class="otp-inputs">
                <input type="text" name="otp[]" maxlength="6" class="otp-input" placeholder="xxxxxx" required>
            </div>
            <button type="submit">Verify</button>
            <p>Didn't receive a code? <a href="Reset_code.php" class="resend-link">Resend</a></p>
            <p><a href="Forgot_pass.php" class="use-email-link">Try another way</a></p>
        </form>
    </div>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: '" . $_SESSION['error'] . "',
                icon: 'error',
                button: {
                        className: 'swal-button--confirm'
                    }
            });
        </script>";
        unset($_SESSION['error']); // Clear the error after displaying it
    }
    ?>
</body>
</html>
