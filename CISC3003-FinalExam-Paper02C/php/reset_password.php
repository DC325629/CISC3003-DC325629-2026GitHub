<?php
/**
 * Scenario C: Secure Password Reset Execution (C.07)
 */
// 強制設定為澳門時區
date_default_timezone_set('Asia/Macau');
require 'connect.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;
$userId = null;

if ($token) {
    $token_hash = hash("sha256", $token);
    
    // 先不查時間，先看 Hash 在不在資料庫裡
    $sql = "SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $current_time = date("Y-m-d H:i:s");
        // 用 PHP 比對時間
        if ($user['reset_token_expires_at'] > $current_time) {
            $validToken = true;
            $userId = $user['id'];
        } else {
            $error = "Token expired! (DB Time: {$user['reset_token_expires_at']}, Current Time: {$current_time})";
        }
    } else {
        $error = 'Invalid reset link. (資料庫中找不到此 Token，請檢查資料庫欄位長度是否大於 64)';
    }
} else {
    $error = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken && $userId) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($sql);
        $update_stmt->bind_param("si", $new_hash, $userId);

        if ($update_stmt->execute()) {
            $success = 'Password reset successfully! You can now login.';
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    <h1>Reset Password</h1>
    
    <?php if ($error): ?>
        <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green; font-weight: bold;"><?= htmlspecialchars($success) ?></p>
        <p><a href="login.php">Go to Login</a></p>

    <?php elseif ($validToken && $userId): ?>
        <form method="POST">
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required placeholder="Min 6 characters">

            <label for="password_confirmation">Confirm New Password:</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>

            <button type="submit">Update Password</button>
        </form>

    <?php else: ?>
        <p><a href="forgot_password.php">Request a new reset link</a></p>
    <?php endif; ?>

    <hr>
    <footer>
        <p>CISC3003 Web Programming: LAM KA WAI + DC325629 + 2026</p>
    </footer>
</body>
</html>