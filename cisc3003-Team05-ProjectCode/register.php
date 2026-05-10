<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/mail.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$full_name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 后端验证电话号码：如果填写，必须是 8-15 位纯数字
    $phoneError = false;
    if ($phone !== '') {
        if (!preg_match('/^\d{8,15}$/', $phone)) {
            $error = 'Phone number must contain 8 to 15 digits only (0-9).';
            $phoneError = true;
        }
    }
    
    if (empty($full_name) && !$phoneError) {
        $error = 'Please enter your name';
    } elseif (empty($email) && !$phoneError) {
        $error = 'Please enter your email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !$phoneError) {
        $error = 'Please enter a valid email address';
    } elseif (empty($password) && !$phoneError) {
        $error = 'Please enter a password';
    } elseif (strlen($password) < 6 && !$phoneError) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password && !$phoneError) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered';
        }
    }
    
    if (empty($error)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("
            INSERT INTO users (email, full_name, phone, password_hash, verification_token, verification_expires)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$email, $full_name, $phone, $password_hash, $token, $expires])) {
            $siteUrl = defined('DYNAMIC_SITE_URL') ? DYNAMIC_SITE_URL : 'http://localhost/Food%20website%20demo';
            $mailSent = sendVerificationEmail($email, $full_name, $token, $siteUrl);
            if ($mailSent) {
                $success = 'Registration successful! Please check your email to verify your account.';
            } else {
                $success = 'Account created, but verification email could not be sent. Please contact support.';
                error_log("Mail failed for $email");
            }
            $full_name = $email = $phone = '';
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

$flashMessages = consume_flash_messages();
$dbError = null;

render_header('Register', 'register', 0, $flashMessages, $dbError);
?>
<div class="auth-container">
    <h1 class="auth-title">Register</h1>
    
    <?php if ($error): ?>
        <div class="error-msg"><?= e($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-msg"><?= e($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <input type="text" name="full_name" placeholder="Full Name *" value="<?= e($full_name) ?>" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email *" value="<?= e($email) ?>" required>
        </div>
        <div class="form-group">
            <input type="tel" name="phone" placeholder="Phone (Optional, 8-15 digits)" value="<?= e($phone) ?>">
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password (min 6 chars) *" required>
        </div>
        <div class="form-group">
            <input type="password" name="confirm_password" placeholder="Confirm Password *" required>
        </div>
        <button type="submit" class="btn-auth-register-page">Register</button>
    </form>
    
    <div class="auth-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>
<?php render_footer(); ?>