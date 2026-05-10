<?php
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function sendVerificationEmail(string $to, string $name, string $token, string $siteUrl): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'CrispyTeam05@gmail.com';
        $mail->Password   = 'vrxixiokfkwqdmyn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('CrispyTeam05@gmail.com', 'Crispy Meals');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email';
        $verifyLink = rtrim($siteUrl, '/') . '/verify.php?token=' . urlencode($token);
        $mail->Body = "<h2>Welcome to Crispy Meals!</h2><p>Click <a href='{$verifyLink}'>here</a> to verify your email. Link expires in 24 hours.</p>";
        return $mail->send();
    } catch (PHPMailerException $e) {
        error_log("Verification mail error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail(string $to, string $name, string $token, string $siteUrl): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'CrispyTeam05@gmail.com';
        $mail->Password   = 'vrxixiokfkwqdmyn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('CrispyTeam05@gmail.com', 'Crispy Meals');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $resetLink = rtrim($siteUrl, '/') . '/reset-password.php?token=' . urlencode($token);
        $mail->Body = "<h2>Password Reset Request</h2><p>Click <a href='{$resetLink}'>here</a> to reset your password. Link expires in 1 hour.</p>";
        return $mail->send();
    } catch (PHPMailerException $e) {
        error_log("Reset mail error: " . $mail->ErrorInfo);
        return false;
    }
}