<?php
session_start();

require_once '../Connection/connection.php';

function sanitize_data($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = implode('', $_POST['verification_code']); // Combine the OTP code parts into one string
    
    // Retrieve OTP code from session
    $otp_code = $_SESSION['otp_code'];

    if ($otp_input == $otp_code) {
        // Retrieve registration data from session
        $registration_data = $_SESSION['registration_data'];

        // Insert data into the user table
        $sql_user = "INSERT INTO user (first_name, last_name, password, phone_number, email, user_type, valid_id, age, birthdate, province, municipality, barangay, postal_code)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param(
            'sssssssssssss',
            $registration_data['first_name'],
            $registration_data['last_name'],
            $registration_data['password'],
            $registration_data['phone_number'],
            $registration_data['email'],
            $registration_data['user_type'],
            $registration_data['valid_id'],
            $registration_data['age'], // Age field
            $registration_data['birthdate'], // Birthdate field
            $registration_data['province-name'], // Province field
            $registration_data['municipality-name'], // Municipality field
            $registration_data['barangay-name'], // Barangay field
            $registration_data['postal_code'] // Postal Code field
        );

        if ($stmt_user->execute()) {
            $message = "success";
            // Clear session data
            unset($_SESSION['registration_data']);
            unset($_SESSION['otp_code']);
        } else {
            $message = "error";
        }
        $stmt_user->close();
        $conn->close();
    } else {
        $message = "invalid_otp";
    }

    // Pass message and redirect URL to JavaScript
    echo "<script>
        var message = '$message';
        var redirectUrl = 'Otp_email.php';
        window.onload = function() {
            if (message === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Registration completed successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../Login/Login.php';
                    }
                });
            } else if (message === 'error') {
                Swal.fire({
                    title: 'Error!',
                    text: 'There was an error processing your registration.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = redirectUrl;
                    }
                });
            } else if (message === 'invalid_otp') {
                Swal.fire({
                    title: 'Invalid OTP!',
                    text: 'The OTP you entered is incorrect. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = redirectUrl;
                    }
                });
            }
        }
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Verify Your OTP</title>
    <link rel="stylesheet" href="../css/login_register.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>
    <div class="verification-container">
        <h2 class="verification-header">VERIFICATION CODE</h2>
        <p>We sent a code to your email account. This helps keep your account safe.</p>
        <form class="verification-form" action="Otp_email.php" method="POST">
            <div class="otp-inputs">
                <input type="text" name="verification_code[]" maxlength="6" class="otp-input" placeholder="xxxxxx" required>
            </div>
            <button type="submit">Verify</button>
            <p>Didn't receive a code? <a href="#" id="resend-link" class="resend-link">Resend</a></p>
            <p><a href="Verify_phone.php" class="use-phone-link">Use my phone number instead</a></p>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#resend-link').click(function(e) {
            e.preventDefault(); // Prevent default link behavior

            $.ajax({
                url: 'Resend_otp.php', // URL to your resend OTP PHP script
                type: 'POST',
                success: function(response) {
                    Swal.fire({
                        title: 'OTP Resent!',
                        text: 'A new OTP has been sent to your email.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'There was an error resending the OTP.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    });
    </script>
</body>
</html>
