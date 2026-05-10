<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;
$userId = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $validToken = true;
        $userId = $user['id'];
    } else {
        $error = 'Invalid or expired reset link.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken && $userId) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        if ($stmt->execute([$hash, $userId])) {
            $success = 'Password reset successfully! You can now login.';
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}

render_header('Reset Password', 'reset-password', 0, [], null);
?>
<div class="auth-container">
    <h1 class="auth-title">Reset Password</h1>
    <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success-msg"><?= e($success) ?></div>
        <div class="back-to-login">
            <a href="login.php">Go to Login</a>
        </div>
    <?php elseif ($validToken && $userId): ?>
        <form method="POST">
            <div class="form-group">
                <input type="password" name="password" placeholder="New Password (min 6 chars)" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <button type="submit" class="btn-auth-register-page">Reset Password</button>
        </form>
        <div class="back-to-login">
            <a href="forgot-password.php">Request new link</a>
        </div>
    <?php else: ?>
        <div class="error-msg"><?= e($error) ?></div>
        <div class="back-to-login">
            <a href="forgot-password.php">Request new reset link</a>
        </div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>