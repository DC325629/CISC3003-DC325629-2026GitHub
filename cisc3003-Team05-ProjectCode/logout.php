<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

session_destroy();

$flashMessages = [];
$dbError = null;

render_header('Logout', 'logout', 0, $flashMessages, $dbError);
?>
<div class="logout-container">
    <h1 class="auth-title">Logged Out</h1>
    <div class="logout-message">You have been successfully logged out.</div>
    <a href="index.php" class="btn-logout-return">Return to Home</a>
</div>
<?php render_footer(); ?>