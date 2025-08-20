<?php
require_once '../includes/session.php';
require_once '../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Save OTP and expiry in DB
            $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
            $stmt->execute([$otp, $otp_expiry, $email]);

            if ($stmt->rowCount() > 0) {
                // Email content
                $subject = "Your Password Reset OTP";
                $messageHtml = "
                <html>
                <head><title>Password Reset OTP</title></head>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Your OTP for password reset is: <strong>$otp</strong></p>
                    <p>This OTP is valid for 10 minutes.</p>
                </body>
                </html>";

                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                $headers .= "From: no-reply@yourdomain.com\r\n";

                if (mail($email, $subject, $messageHtml, $headers)) {
                    // store email in session and redirect to reset page
                    $_SESSION['reset_email'] = $email;
                    header('Location: reset_password.php');
                    exit();
                } else {
                    $message = "❌ Failed to send OTP. Please try again.";
                }
            } else {
                $message = "⚠️ No account found with this email.";
            }
        } catch (PDOException $e) {
            error_log("Database Error: {$e->getMessage()}", 3, '../logs/db_errors.log');
            $message = "❌ Error: Unable to process your request.";
        }
    } else {
        $message = "⚠️ Please enter a valid email address.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; }
        input[type="email"] { width: 100%; padding: 10px; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .msg { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h2>Forgot Password</h2>

    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" required placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        <button type="submit">Send OTP</button>
    </form>
</body>
</html>
