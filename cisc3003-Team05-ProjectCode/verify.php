<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($token) {
    $stmt = $pdo->prepare("
        SELECT id FROM users
        WHERE verification_token = ? AND verification_expires > NOW() AND email_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $pdo->prepare("
            UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL
            WHERE id = ?
        ")->execute([$user['id']]);
        $success = 'Email verified successfully! You can now log in.';
    } else {
        $error = 'Invalid or expired verification link.';
    }
} else {
    $error = 'No verification token provided.';
}

render_header('Email Verification', 'verify', 0, [], null);
?>
<div class="auth-container">
    <h1 class="auth-title">Email Verification</h1>
    <?php if ($error): ?>
        <div class="error-msg"><?= e($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success-msg"><?= e($success) ?></div>
    <?php endif; ?>
    <div class="auth-link">
        <a href="login.php">Go to Login</a>
    </div>
</div>
<?php render_footer(); ?>