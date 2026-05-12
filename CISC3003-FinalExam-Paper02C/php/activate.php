<?php
/**
 * Scenario C: Email Confirmation Functionality (C.08)
 */
require 'connect.php';

$token = $_GET['token'] ?? '';
$token_hash = hash("sha256", $token);

// 1. Identify the user by the activation hash
$sql = "SELECT id FROM users WHERE account_activation_hash = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // 2. Set is_active to 1 and clear the activation hash
    $sql = "UPDATE users SET is_active = 1, account_activation_hash = NULL WHERE id = ?";
    $update_stmt = $conn->prepare($sql);
    $update_stmt->bind_param("i", $user['id']);
    
    if ($update_stmt->execute()) {
        $status = "success";
    } else {
        $status = "error";
    }
} else {
    $status = "invalid";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Activation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    <h1>Activation Result</h1>
    <?php if ($status == "success"): ?>
        <p style="color: green; font-weight: bold;">Your account has been activated! You can now log in.</p>
        <a href="login.php">Go to Login</a>
    <?php elseif ($status == "invalid"): ?>
        <p style="color: orange;">Invalid activation link or account already activated.</p>
        <a href="login.php">Go to Login</a>
    <?php else: ?>
        <p style="color: red;">Something went wrong. Please contact support.</p>
    <?php endif; ?>

    <hr>
    <footer>
        <p>CISC3003 Web Programming: LAM KA WAI + DC325629 + 2026</p>
    </footer>
</body>
</html>