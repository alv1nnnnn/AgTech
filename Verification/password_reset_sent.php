<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Password Reset Sent</title>
    <link rel="stylesheet" href="../css/login_register.css?<?php echo time(); ?>">
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>
    <div class="reset-container">
        <form id="loginForm" class="password-reset-container" action="../Login/Login.php" method="POST">
            <div class="password-reset-logo">
                <img src="../images/AgTech-Logo.png" alt="AgTech Logo" class="logo">
            </div>
            <h2 style="color:green; text-align:center;">Reset your password</h2>
            <p style="margin:40px; ">Check your email for a link to reset your password. If it doesn’t appear within a few minutes, check your spam folder.</p>
            <a href="../Login/Login.php" style="text-decoration: none;">
            <button type="button">Return to login</button>
            </a>
        </form>
    </div>
</body>
</html>
