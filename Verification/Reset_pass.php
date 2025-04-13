<?php
session_start();
include("../Connection/connection.php");

function sanitize_data($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$valid_request = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = sanitize_data($_GET['token']);
    $stmt = $conn->prepare("
        SELECT pr.user_id, u.email
        FROM passwordreset pr
        JOIN user u ON pr.user_id = u.user_id
        WHERE pr.reset_id = ? AND pr.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $user_id = $data['user_id'];
        $email = $data['email'];
        $valid_request = true;
    } else {
        $_SESSION['error'] = "Invalid or expired reset link. Please request a new password reset.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new-password'], $_POST['confirm-password'])) {
    $new_password = sanitize_data($_POST['new-password']);
    $confirm_password = sanitize_data($_POST['confirm-password']);
    $email = sanitize_data($_POST['email']);

    if ($new_password === $confirm_password) {
        if (strlen($new_password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Password reset successful.";
                header("Location: Reset_pass.php?status=success");
                exit();
            } else {
                $_SESSION['error'] = "Error updating password. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "Passwords do not match.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Reset Password</title>
    <link rel="stylesheet" href="../css/forgot_reset.css?<?php echo time(); ?>">
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="reset-container">
        <div class="logo-container">
            <img src="../images/AgTech-Logo.png" alt="Logo" class="logo">
        </div>
        <h2>Reset Password</h2>
        <form action="Reset_pass.php" method="POST" class="reset-password-form">
            <?php if ($valid_request): ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new-password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <button type="submit">Reset Password</button>
            <?php else: ?>
                <p>Invalid or expired reset link. <a href="../RequestPasswordReset.php">Request a new one.</a></p>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Try Again'
        });
        <?php unset($_SESSION['error']); ?>
    });
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $_SESSION['success']; ?>',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Log In'
        }).then(() => {
            window.location.href = "../Login/Login.php";
        });
        <?php unset($_SESSION['success']); ?>
    });
</script>
<?php endif; ?>


</body>
</html>
