<?php
require_once __DIR__ . '/includes/bootstrap.php';

// 已登录则跳转首页
if (isLoggedIn()) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$error = '';
$success = getFlash(); // 获取注册成功等消息

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in both fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, full_name, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;

                // 更新最后登录时间
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // 重定向到首页
                header('Location: ' . app_url('index.php'));
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Crispy College Meals</title>
    <link rel="shortcut icon" href="./assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Forum&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body>
    <header class="header" data-header>
        <div class="container">
            <a href="index.php" class="logo">
                <img src="./assets/images/logo.svg" width="130" height="45" alt="Crispy home">
            </a>
        </div>
    </header>

    <main>
        <div class="auth-container">
            <h1 class="auth-title">Login</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success && $success['type'] === 'success'): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success['message']); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="forgot-password-link" style="text-align: right; margin-top: 10px; margin-bottom: 20px;">
                    <a href="forgot-password.php" style="color: #d49b3a; font-size: 1.25rem;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn-auth">Login</button>
            </form>

            <div class="auth-link">
                Don't have an account? <a href="register.php">Register now</a>
            </div>
        </div>
    </main>

    <script src="./assets/js/script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>