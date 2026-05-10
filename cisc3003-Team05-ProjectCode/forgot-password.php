<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            $siteUrl = defined('DYNAMIC_SITE_URL') ? DYNAMIC_SITE_URL : 'http://localhost/Food%20website%20demo';
            $mailSent = sendPasswordResetEmail($email, $user['full_name'], $token, $siteUrl);
            if ($mailSent) {
                $success = 'If that email is registered, you will receive a reset link.';
            } else {
                $success = 'Unable to send reset email. Please try again later.';
                error_log("Password reset mail failed for $email");
            }
        } else {
            $success = 'If that email is registered, you will receive a reset link.';
        }
    }
}

render_header('Forgot Password', 'forgot-password', 0, [], null);
?>
<div class="auth-container">
    <h1 class="auth-title">Forgot Password</h1>
    <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg"><?= e($success) ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Your Email Address" required>
        </div>
        <button type="submit" class="btn-auth">Send Reset Link</button>
    </form>
    <div class="back-to-login">
        <a href="login.php">← Back to Login</a>
    </div>
</div>
<?php render_footer(); ?>