<?php
require_once '../includes/session.php';
require_once '../config/database.php';

$error = '';
$success = '';

// Ensure we have an email from session (set after OTP sent)
if (empty($_SESSION['reset_email'])) {
    $error = 'No reset requested. Please request an OTP first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $email = $_SESSION['reset_email'];
    $otp = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Account not found.';
            } elseif ($user['otp'] !== $otp) {
                $error = 'Invalid OTP.';
            } elseif (empty($user['otp_expiry']) || strtotime($user['otp_expiry']) < time()) {
                $error = 'OTP has expired. Please request a new one.';
            } else {
                // NOTE: existing app stores plaintext passwords; keep consistent.
                // For production, use password_hash().
                $stmt = $pdo->prepare("UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
                $stmt->execute([$new_password, $email]);

                // clear session and redirect to login with message
                unset($_SESSION['reset_email']);
                header('Location: login.php?message=reset_success');
                exit();
            }
        } catch (PDOException $e) {
            error_log("DB Error: {$e->getMessage()}", 3, '../logs/db_errors.log');
            $error = 'An error occurred. Try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; }
        input { width: 100%; padding: 10px; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Reset Password</h2>

    <?php if ($error): ?>
        <div style="color: red; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <input type="password" name="new_password" placeholder="New password" required>
        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        <button type="submit">Reset Password</button>
    </form>

    <p style="margin-top:1rem;"><a href="forgot_password_request.php">Request new OTP</a></p>
</body>
</html>
