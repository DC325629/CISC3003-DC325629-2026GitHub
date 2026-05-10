<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/repositories.php';

if (!isLoggedIn()) {
    flash('error', 'Please login to access your account.');
    redirect(app_url('login.php'));
}

$userId = (int) $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// 获取用户余额
function getUserBalance(PDO $pdo, int $userId): float {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return (float) ($stmt->fetchColumn() ?? 0);
}

// 充值
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recharge') {
    ensure_csrf_token();
    $amount = (float) ($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        $errorMsg = 'Please enter a valid amount (> 0).';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        if ($stmt->execute([$amount, $userId])) {
            $successMsg = 'Successfully added $' . number_format($amount, 2) . ' to your balance.';
        } else {
            $errorMsg = 'Recharge failed. Please try again.';
        }
    }
}

// 更新个人资料
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    ensure_csrf_token();
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    $phoneError = false;
    if ($phone !== '') {
        if (!preg_match('/^\d{8,15}$/', $phone)) {
            $errorMsg = 'Phone number must contain 8 to 15 digits only (0-9).';
            $phoneError = true;
        }
    }

    if (empty($full_name) && !$phoneError) {
        $errorMsg = 'Full name cannot be empty.';
    } elseif (!$phoneError) {
        if (update_user_profile($pdo, $userId, $full_name, $phone)) {
            $_SESSION['user_name'] = $full_name;
            $successMsg = 'Profile updated successfully.';
        } else {
            $errorMsg = 'Failed to update profile. Please try again.';
        }
    }
}

// 修改密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    ensure_csrf_token();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $errorMsg = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMsg = 'New passwords do not match.';
    } else {
        $result = change_user_password($pdo, $userId, $currentPassword, $newPassword);
        if ($result === true) {
            $successMsg = 'Password changed successfully.';
        } elseif ($result === 'invalid_current') {
            $errorMsg = 'Current password is incorrect.';
        } else {
            $errorMsg = 'Failed to change password. Please try again.';
        }
    }
}

$stmt = $pdo->prepare("SELECT full_name, email, phone, balance, last_login FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$userOrders = [];
if ($pdo instanceof PDO) {
    $userOrders = orders_with_search($pdo, '', $userId);
    $userOrders = array_slice($userOrders, 0, 10);
}

render_header('My Account', 'account', cart_count(), $flashMessages, $dbError);
?>

<section class="page-hero" style="background: var(--bg-seashell); padding-block: 120px 40px;">
    <div class="container">
        <p class="page-kicker">User Dashboard</p>
        <h1 class="title h1 page-title">My Account</h1>
        <p class="page-description">Manage your profile, check balance, recharge, change password, and track orders.</p>
    </div>
</section>

<div class="container" style="margin-block: 40px 80px;">
    <?php if ($successMsg): ?>
        <div class="alert alert--success"><?= e($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert--error"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <div class="account-layout" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
        
        <!-- 左侧卡片：个人资料 + 修改密码 -->
        <div class="detail-card">
            <h2 class="title h2">Profile Information</h2>
            <form method="post" class="stack-form" style="margin-top: 20px;" id="profileForm">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <label class="field-label">
                    Full Name *
                    <input type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>" required>
                </label>
                <label class="field-label">
                    Email (cannot be changed)
                    <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled style="background: #f0f0f0;">
                </label>
                <label class="field-label">
                    Phone Number <span style="font-size: 1.2rem; color: #666;">(Optional, 8-15 digits)</span>
                    <input type="tel" name="phone" id="phoneInput" value="<?= e($user['phone'] ?? '') ?>" placeholder="e.g., 12345678">
                    <small id="phoneError" style="color: #c62828; display: none; font-size: 1.2rem;">Phone must be 8-15 digits only.</small>
                </label>
                <label class="field-label">
                    Account Balance
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 2rem; color: var(--text-sinopia);">$<?= number_format((float)($user['balance'] ?? 0), 2) ?></strong>
                        <span style="font-size: 1.3rem;">* You can recharge below</span>
                    </div>
                </label>
                <label class="field-label">
                    Last Login
                    <input type="text" value="<?= e($user['last_login'] ?? 'Never') ?>" disabled style="background: #f0f0f0;">
                </label>
                <div class="button-row">
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>

            <hr style="margin: 32px 0; border-color: rgba(104,94,85,0.1);">
            
            <!-- 充值区域 -->
            <h2 class="title h2">Recharge Balance</h2>
            <form method="post" class="stack-form" style="margin-top: 20px;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="recharge">
                <label class="field-label">
                    Amount ($)
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="e.g., 50.00">
                </label>
                <div class="button-row">
                    <button type="submit" class="btn" style="background: #2e7d32;">Add Money</button>
                </div>
            </form>

            <hr style="margin: 32px 0; border-color: rgba(104,94,85,0.1);">
            
            <h2 class="title h2">Change Password</h2>
            <form method="post" class="stack-form" style="margin-top: 20px;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="change_password">
                
                <label class="field-label">
                    Current Password *
                    <input type="password" name="current_password" required>
                </label>
                <label class="field-label">
                    New Password (min 6 chars) *
                    <input type="password" name="new_password" required>
                </label>
                <label class="field-label">
                    Confirm New Password *
                    <input type="password" name="confirm_password" required>
                </label>
                <div class="button-row">
                    <button type="submit" class="btn">Update Password</button>
                </div>
            </form>
            
            <div class="back-to-login" style="margin-top: 20px;">
                <a href="forgot-password.php">Forgot password? (Reset via email)</a>
            </div>
        </div>

        <!-- 右侧卡片：我的订单 -->
        <div class="detail-card">
            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap;">
                <h2 class="title h2">My Recent Orders</h2>
                <a href="orders.php" class="btn btn--secondary" style="padding: 8px 20px;">View All →</a>
            </div>
            
            <?php if (empty($userOrders)): ?>
                <div class="empty-card" style="margin-top: 24px;">
                    <p>You haven't placed any orders yet.</p>
                    <a href="checkout.php" class="btn" style="margin-top: 15px;">Start Ordering</a>
                </div>
            <?php else: ?>
                <div class="history-list" style="margin-top: 24px;">
                    <?php foreach ($userOrders as $order): ?>
                        <article class="history-card" style="margin-bottom: 16px;">
                            <div class="history-card__header">
                                <div>
                                    <p class="eyebrow">Order code</p>
                                    <h2 class="title h3"><?= e($order['order_code']) ?></h2>
                                </div>
                                <span class="status-pill"><?= e($order['status']) ?></span>
                            </div>
                            <div class="history-card__meta" style="font-size: 1.4rem;">
                                <p><strong>Pickup:</strong> <?= e($order['pickup_date']) ?> at <?= e($order['pickup_slot_label']) ?></p>
                                <p><strong>Items:</strong> <?= (int)($order['item_count'] ?? 0) ?> &nbsp;|&nbsp; 
                                <strong>Total:</strong> <?= money((float) $order['total']) ?></p>
                                <p><strong>Placed:</strong> <?= e($order['created_at']) ?></p>
                            </div>
                            <div class="history-card__footer" style="margin-top: 12px;">
                                <a class="btn" href="order-details.php?id=<?= (int) $order['id'] ?>">View Details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="button-row" style="margin-top: 20px; justify-content: center;">
                    <a href="orders.php" class="btn btn--secondary">See Complete History</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 退出区块 -->
    <div style="margin-top: 48px; text-align: center; padding: 20px 0;">
        <hr style="margin: 20px auto; width: 80px; border-color: rgba(104,94,85,0.2);">
        <p class="section-text" style="margin-bottom: 12px;">Need to sign out?</p>
        <a href="logout.php" class="btn danger-button" style="background: #c62828; border-color: #c62828; display: inline-block;">Logout</a>
    </div>
</div>

<script>
    const phoneInput = document.getElementById('phoneInput');
    const phoneErrorSpan = document.getElementById('phoneError');
    const profileForm = document.getElementById('profileForm');

    function validatePhone(phone) {
        if (phone === '') return true;
        return /^\d{8,15}$/.test(phone);
    }

    phoneInput.addEventListener('input', function() {
        const phone = this.value.trim();
        if (phone !== '' && !validatePhone(phone)) {
            phoneErrorSpan.style.display = 'block';
        } else {
            phoneErrorSpan.style.display = 'none';
        }
    });

    profileForm.addEventListener('submit', function(e) {
        const phone = phoneInput.value.trim();
        if (phone !== '' && !validatePhone(phone)) {
            e.preventDefault();
            phoneErrorSpan.style.display = 'block';
            phoneErrorSpan.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

<?php render_footer(); ?>