<?php
require_once __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    flash('error', 'Invalid verification link');
    header('Location: login.html');
    exit;
}

$users = getUsers();
$found = false;

foreach ($users as $index => $user) {
    if (isset($user['verification_token']) && $user['verification_token'] === $token) {
        $expires = strtotime($user['verification_token_expires']);
        if ($expires > time()) {
            $users[$index]['email_verified'] = true;
            $users[$index]['verification_token'] = null;
            $users[$index]['verification_token_expires'] = null;
            saveUsers($users);
            $found = true;
            flash('success', 'Email verified successfully! You can now login.');
        } else {
            flash('error', 'Verification link has expired.');
        }
        break;
    }
}

if (!$found) {
    flash('error', 'Invalid verification link');
}

header('Location: login.html');
exit;
?>