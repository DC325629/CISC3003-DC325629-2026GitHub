<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$error = '';
$success = '';
$full_name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf_token();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 验证输入
    if (empty($full_name)) {
        $error = 'Please enter your name';
    } elseif (empty($email)) {
        $error = 'Please enter your email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (empty($password)) {
        $error = 'Please enter a password';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // 检查邮箱是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered';
        }
    }

    if (empty($error)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (email, full_name, phone, password_hash)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$email, $full_name, $phone, $password_hash])) {
            flash('success', 'Registration successful! Please login.');
            header('Location: ' . app_url('login.php'));
            exit;
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Crispy College Meals</title>
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
            <h1 class="auth-title">Register</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Full Name *" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email *" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Phone (Optional)" value="<?php echo htmlspecialchars($phone); ?>">
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
    </main>

    <script src="./assets/js/script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>