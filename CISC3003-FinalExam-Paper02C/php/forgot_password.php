<?php
/**
 * Scenario C: Secure Password Reset Request (C.07)
 */
// 強制設定為澳門時區，確保時間一致！
date_default_timezone_set('Asia/Macau');
require 'connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    
    // 用 PHP 算出 30 分鐘後的時間
    $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    // 寫入資料庫
    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token_hash, $expiry, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'CrispyTeam05@gmail.com';
            $mail->Password   = 'vrxixiokfkwqdmyn';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('CrispyTeam05@gmail.com', 'CISC3003 Security');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            
            $reset_link = "http://localhost/CISC3003-FinalExam-Paper02C/php/reset_password.php?token=$token";
            
            $mail->Body = "
                <h2>Password Reset</h2>
                <p>A password reset was requested for your account. Please click the link below to set a new password. This link will expire in 30 minutes.</p>
                <p><a href='$reset_link'>Reset Password</a></p>
            ";

            $mail->send();
            $message = "A reset link has been sent to your email inbox.";
            $message_type = "success";
        } catch (Exception $e) {
            $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            $message_type = "error";
        }
    } else {
        $message = "If that email address exists, we have sent a reset link.";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    <h1>Forgot Password</h1>
    <?php if ($message): ?>
        <p style="color: <?= ($message_type == 'success') ? 'green' : 'red' ?>; font-weight:bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Enter your registered Email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <p><a href="login.php">Back to Login</a></p>

    <hr>
    <footer>
        <p>CISC3003 Web Programming: LAM KA WAI + DC325629 + 2026</p>
    </footer>
</body>
</html>