<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in both fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['email_verified']) {
                $error = 'Please verify your email before logging in. Check your inbox for the verification link.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;
                
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$flashMessages = consume_flash_messages();
$dbError = null;

render_header('Login', 'login', 0, $flashMessages, $dbError);
?>
<div class="auth-container">
    <h1 class="auth-title">Login</h1>
    
    <?php if ($error): ?>
        <div class="error-msg"><?= e($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <div class="forgot-password-link" style="text-align: right; margin-top: 10px; margin-bottom: 20px;">
            <a href="forgot-password.php" style="color: #d49b3a; font-size: 1rem;">Forgot Password?</a>
        </div>
        <button type="submit" class="btn-auth">Login</button>
    </form>
    
    <div class="auth-link">
        Don't have an account? <a href="register.php">Register now</a>
    </div>
</div>
<?php render_footer(); ?>