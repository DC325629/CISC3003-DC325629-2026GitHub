<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/repositories.php';

if (!isLoggedIn()) {
    flash('error', 'Please login to proceed to checkout.');
    redirect(app_url('login.php'));
}

$userId = (int) $_SESSION['user_id'];
$userProfile = ['full_name' => '', 'phone' => ''];

if ($userId && $pdo instanceof PDO) {
    $stmt = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userProfile = $stmt->fetch() ?: ['full_name' => '', 'phone' => ''];
}

$cart = ['items' => [], 'subtotal' => 0.0, 'service_fee' => 0.0, 'total' => 0.0];
$slots = [];
$errors = [];
$pickupDate = date('Y-m-d');

/**
 * 生成购物车侧边栏 HTML（包含菜品时段信息 data-available-time）
 */
function renderCheckoutCartHtml(array $cart, string $csrfToken): string
{
    ob_start();
    ?>
    <div class="cart-preview" data-cart-container>
        <div>
            <p class="page-kicker">Cart summary</p>
            <h2 class="title h2">Meals ready for pickup</h2>
        </div>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-card">
                <h2 class="title h3">Your cart is empty</h2>
                <p>Add meals from the menu page before placing an order.</p>
                <div class="button-row"><a class="btn" href="./index.php">Back to menu</a></div>
            </div>
        <?php else: ?>
            <div class="history-list" style="margin-top: 24px;">
                <?php foreach ($cart['items'] as $item): ?>
                    <article class="cart-preview__item"
                             data-meal-id="<?= (int) $item['meal_id'] ?>"
                             data-available-time="<?= e($item['available_time']) ?>">
                        <div>
                            <strong><?= e($item['name']) ?></strong>
                            <p class="line-muted"><?= e($item['category']) ?> | <?= money((float) $item['price']) ?> each</p>
                        </div>
                        <div class="cart-item-controls" style="margin-top: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size:1.2rem;">Qty:</label>
                                <input type="number" class="cart-qty-input" value="<?= (int) $item['quantity'] ?>" min="0" max="20" step="1" style="width: 70px; padding: 8px; border-radius: 30px; border: 1px solid #ddd; text-align: center;">
                                <button class="btn btn--secondary update-cart-btn" data-id="<?= (int) $item['meal_id'] ?>" type="button">Update</button>
                                <button class="btn remove-cart-btn" data-id="<?= (int) $item['meal_id'] ?>" type="button" style="background:#c62828; color:white;">Remove</button>
                            </div>
                            <strong><?= money((float) $item['line_total']) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="price-stack" style="margin-top: 24px;">
                <div><span>Subtotal</span><strong data-checkout-subtotal><?= money((float) $cart['subtotal']) ?></strong></div>
                <div><span>Service fee</span><strong data-checkout-fee><?= money((float) $cart['service_fee']) ?></strong></div>
                <div class="total-line"><span>Total</span><strong data-checkout-total><?= money((float) $cart['total']) ?></strong></div>
            </div>
        <?php endif; ?>
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <div class="button-row" style="margin-top: 20px;">
            <a class="btn" href="./checkout.php">Refresh cart</a>
            <a class="btn btn--secondary" href="./orders.php">History</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 生成 slots 列表 HTML（包含 data-slot-category 和基础禁用）
 */
function renderSlotsHtml(array $slots, string $selectedSlotId = ''): string
{
    if (empty($slots)) {
        return '<p>No pickup slots available for the selected date.</p>';
    }
    $html = '';
    foreach ($slots as $slot) {
        try {
            $slotCategory = get_slot_time_category($slot['slot_value']);
        } catch (RuntimeException $e) {
            continue;
        }
        $baseDisabled = !$slot['available'];
        $checkedAttr = ($selectedSlotId === (string) $slot['id']) ? 'checked' : '';
        $html .= sprintf(
            '<label class="slot-option%s" data-slot-category="%s" data-slot-id="%d">
                <input type="radio" name="pickup_slot_id" value="%d" %s %s>
                <strong>%s</strong>
                <span class="line-muted">Capacity %d | %d left</span>
            </label>',
            $baseDisabled ? ' slot-option--disabled' : '',
            e($slotCategory),
            (int) $slot['id'],
            (int) $slot['id'],
            $checkedAttr,
            $baseDisabled ? 'disabled' : '',
            e($slot['label']),
            (int) $slot['capacity'],
            (int) $slot['remaining']
        );
    }
    return $html;
}

// ========== AJAX 处理（购物车更新 & 时段加载） ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    ensure_csrf_token();

    $action = $_POST['action'] ?? '';

    // 购物车更新
    if ($action === 'update_quantity' || $action === 'remove_item') {
        $response = ['success' => false, 'message' => '', 'cartHtml' => '', 'cartCount' => 0];
        try {
            $mealId = (int) ($_POST['meal_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 0);
            if ($action === 'update_quantity') {
                set_cart_quantity($mealId, $quantity);
            } elseif ($action === 'remove_item') {
                set_cart_quantity($mealId, 0);
            }
            if ($pdo instanceof PDO) {
                $cart = cart_snapshot($pdo);
                $response['cartHtml'] = renderCheckoutCartHtml($cart, csrf_token());
                $response['cartCount'] = cart_count();
                $response['success'] = true;
            } else {
                throw new Exception('Database error');
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // 获取时段列表（无刷新）
    if ($action === 'get_slots') {
        $date = $_POST['pickup_date'] ?? date('Y-m-d');
        if (!is_valid_date_string($date)) {
            $date = date('Y-m-d');
        }
        $slotsHtml = '';
        $hasSlots = false;
        if ($pdo instanceof PDO) {
            $slots = pickup_slots_with_availability($pdo, $date, new DateTimeImmutable());
            $hasSlots = !empty($slots);
            $slotsHtml = renderSlotsHtml($slots);
        }
        echo json_encode([
            'success' => true,
            'slotsHtml' => $slotsHtml,
            'hasSlots' => $hasSlots,
        ]);
        exit;
    }
}

// ========== 正常页面请求（POST 订单提交 或 GET 显示页面） ==========
if ($pdo instanceof PDO) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
        ensure_csrf_token();
        remember_old_input($_POST);
        $submittedDate = trim($_POST['pickup_date'] ?? '');
        $today = date('Y-m-d');
        $dateValid = true;

        if (!is_valid_date_string($submittedDate)) {
            $errors[] = 'Invalid date format. Please select a valid pickup date.';
            $dateValid = false;
            $pickupDate = $today;
        } elseif ($submittedDate < $today) {
            $errors[] = 'Pickup date cannot be in the past. It has been set to today. Please re-select your pickup slot.';
            $dateValid = false;
            $pickupDate = $today;
        } else {
            $pickupDate = $submittedDate;
        }

        if (!$dateValid) {
            $_POST['pickup_date'] = $today;
            unset($_POST['pickup_slot_id']);
            remember_old_input($_POST);
        }

        if (empty($errors)) {
            try {
                $orderId = create_order_from_cart($pdo, $_POST, $userId);
                consume_old_input();
                flash('success', 'Order placed successfully.');
                redirect(app_url('order-details.php?id=' . $orderId));
            } catch (Throwable $exception) {
                $errors[] = 'Order failed: ' . $exception->getMessage();
                if ($dateValid) {
                    $pickupDate = $submittedDate;
                } else {
                    $pickupDate = $today;
                }
            }
        } else {
            $pickupDate = $today;
        }
    }

    $cart = cart_snapshot($pdo);
    $slots = pickup_slots_with_availability($pdo, $pickupDate, new DateTimeImmutable());
}

// 获取用户余额（用于显示）
$userBalance = 0;
if ($pdo instanceof PDO && $userId) {
    $userBalance = get_user_balance($pdo, $userId);
}

render_header('Checkout', 'checkout', cart_count(), $flashMessages, $dbError);
?>
<style>
    .slot-scrollable {
        max-height: 350px;
        overflow-y: auto;
        padding-right: 8px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
    }
    .slot-scrollable::-webkit-scrollbar {
        width: 6px;
    }
    .slot-scrollable::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .slot-scrollable::-webkit-scrollbar-thumb {
        background: #d49b3a;
        border-radius: 10px;
    }
    .slot-option {
        display: grid;
        gap: 8px;
        padding: 16px;
        border: 1px solid rgba(104, 94, 85, 0.12);
        border-radius: 18px;
        background-color: #fff;
        cursor: pointer;
        transition: all 0.2s;
    }
    .slot-option--disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #f5f5f5;
    }
    .cart-item-controls button {
        transition: all 0.2s ease;
    }
    .cart-preview__item {
        border-bottom: 1px solid #eee;
        padding-bottom: 14px;
        margin-bottom: 14px;
    }
    .cart-preview__item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    input[readonly] {
        background-color: #f0f0f0;
        cursor: default;
    }
    .balance-alert {
        background: #e8f5e9;
        border-left: 4px solid #2e7d32;
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .balance-warning {
        background: #fff3e0;
        border-left: 4px solid #ff9800;
    }
    .balance-insufficient {
        background: #ffebee;
        border-left: 4px solid #c62828;
    }
</style>
<section class="container">
  <div class="intro-card">
    <p class="page-kicker">Pickup reservation</p>
    <h1 class="title h1 page-title">Choose your pickup time and confirm your order.</h1>
    <p class="panel-copy">
      Review your cart, select a suitable pickup slot, and send your order to the kitchen in just a few steps.
    </p>
  </div>

  <!-- 显示余额信息 -->
  <?php if (isLoggedIn() && $pdo instanceof PDO): ?>
    <?php
    $totalAmount = $cart['total'] ?? 0;
    $balanceClass = 'balance-alert';
    if ($userBalance < $totalAmount && strtolower($_POST['payment_method'] ?? '') !== 'cash') {
        $balanceClass .= ' balance-insufficient';
    } elseif ($userBalance < 20) {
        $balanceClass .= ' balance-warning';
    }
    ?>
    <div class="<?= $balanceClass ?>" style="margin-bottom: 20px;">
        <strong>💰 Account Balance: $<?= number_format($userBalance, 2) ?></strong>
        <?php if ($userBalance < $totalAmount && (strtolower($_POST['payment_method'] ?? '') !== 'cash')): ?>
            <span style="display: block; margin-top: 5px; color: #c62828;">⚠️ Insufficient balance for this order. Please <a href="account.php">recharge</a> or choose "Cash" as payment method.</span>
        <?php elseif ($userBalance < $totalAmount): ?>
            <span style="display: block; margin-top: 5px;">💡 Tip: Your balance is lower than the order total. You can still pay by Cash.</span>
        <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert--error" style="margin-top: 24px;">
      <strong>Unable to place order:</strong>
      <?php foreach ($errors as $error): ?>
        <p><?= e($error) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="orders-layout" style="margin-top: 24px;">
    <section class="detail-card" id="checkout-cart-section">
      <?= renderCheckoutCartHtml($cart, csrf_token()) ?>
    </section>

    <section class="detail-card">
      <p class="page-kicker">Pickup arrangement</p>
      <h2 class="title h2">Place your order</h2>

      <form method="post" class="field-grid" style="margin-top: 24px;" id="checkout-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="place_order">

        <label class="field-label">
          Student name
          <input type="text" name="customer_name"
                 value="<?= e(old('customer_name', $userProfile['full_name'])) ?>"
                 readonly required>
        </label>
        <label class="field-label">
          Contact number
          <input type="tel" name="phone"
                 value="<?= e(old('phone', $userProfile['phone'])) ?>"
                 readonly>
        </label>
        <label class="field-label">
          Pickup date
          <input type="date" name="pickup_date" id="pickup_date"
                 value="<?= e(old('pickup_date', $pickupDate)) ?>"
                 min="<?= date('Y-m-d') ?>" required>
        </label>
        <label class="field-label">
          Payment method
          <select name="payment_method" id="payment_method">
            <option value="Balance" <?= old('payment_method') === 'Balance' ? 'selected' : '' ?>>Balance (Account Balance)</option>
            <option value="Cash" <?= old('payment_method') === 'Cash' ? 'selected' : '' ?>>Cash</option>
          </select>
        </label>

        <div class="field-label field-span">
          Pickup slot
          <div class="slot-scrollable" id="slot-container">
            <?= renderSlotsHtml($slots, old('pickup_slot_id')) ?>
          </div>
        </div>

        <label class="field-label field-span">
          Note for canteen staff
          <textarea name="note"><?= old('note') ?></textarea>
        </label>

        <div class="button-row field-span">
          <button class="btn" type="submit" id="confirm-order-btn" <?= $cart['items'] === [] ? ' disabled' : '' ?>>Confirm order</button>
          <a class="btn btn--secondary" href="./orders.php">View order history</a>
        </div>
      </form>
    </section>
  </div>
</section>

<script>
let currentCsrfToken = document.querySelector('#checkout-cart-section input[name="_csrf"]')?.value || '<?= csrf_token() ?>';

// 获取购物车内所有菜品的唯一时段类别
function getCartAvailableTimes() {
    const cartItems = document.querySelectorAll('#checkout-cart-section .cart-preview__item');
    if (cartItems.length === 0) return null;
    const times = new Set();
    for (let item of cartItems) {
        const time = item.getAttribute('data-available-time');
        if (time) times.add(time);
    }
    if (times.size > 1) return null;
    return times.size === 1 ? Array.from(times)[0] : null;
}

// 根据购物车内容动态禁用/启用时段选项（避免用户误选不匹配的时段）
function updateSlotsDisabledState() {
    const cartTime = getCartAvailableTimes();
    const slotLabels = document.querySelectorAll('#slot-container .slot-option');
    slotLabels.forEach(label => {
        const slotCategory = label.getAttribute('data-slot-category');
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        const isBaseDisabled = radio.hasAttribute('data-base-disabled');
        let shouldDisable = isBaseDisabled;
        if (cartTime !== null && slotCategory !== cartTime) {
            shouldDisable = true;
        }
        if (shouldDisable) {
            radio.disabled = true;
            label.classList.add('slot-option--disabled');
        } else {
            radio.disabled = false;
            label.classList.remove('slot-option--disabled');
        }
    });
}

// 保存基础禁用状态（容量或时间已过导致的禁用）
function storeBaseDisabledState() {
    const radios = document.querySelectorAll('#slot-container input[type="radio"]');
    radios.forEach(radio => {
        if (radio.disabled) {
            radio.setAttribute('data-base-disabled', 'true');
        } else {
            radio.removeAttribute('data-base-disabled');
        }
    });
}

// 更新购物车区域并重新评估时段禁用状态
function updateCartSection(html, newCartCount) {
    const cartSection = document.getElementById('checkout-cart-section');
    if (!cartSection) return;
    cartSection.innerHTML = html;
    const newTokenInput = cartSection.querySelector('input[name="_csrf"]');
    if (newTokenInput) currentCsrfToken = newTokenInput.value;
    const checkoutSpan = document.querySelector('.header-action .btn .span');
    if (checkoutSpan) {
        checkoutSpan.textContent = newCartCount > 0 ? 'Start Checkout (' + newCartCount + ')' : 'Start Checkout';
    }
    const confirmBtn = document.getElementById('confirm-order-btn');
    if (confirmBtn) {
        confirmBtn.disabled = (newCartCount === 0);
    }
    updateSlotsDisabledState();
}

// 发送购物车更新请求
async function updateCart(action, mealId, quantity) {
    const formData = new URLSearchParams();
    formData.append('_csrf', currentCsrfToken);
    formData.append('action', action);
    formData.append('meal_id', mealId);
    if (quantity !== undefined) formData.append('quantity', quantity);
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            updateCartSection(data.cartHtml, data.cartCount);
        } else {
            alert(data.message || 'Operation failed');
        }
    } catch (error) {
        console.error('AJAX error:', error);
        alert('Network error, please try again.');
    }
}

// 无刷新加载时段（日期变化时触发）
async function loadSlotsForDate(date) {
    const formData = new URLSearchParams();
    formData.append('_csrf', currentCsrfToken);
    formData.append('action', 'get_slots');
    formData.append('pickup_date', date);
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            const slotContainer = document.getElementById('slot-container');
            if (slotContainer) {
                slotContainer.innerHTML = data.slotsHtml;
                storeBaseDisabledState();
                updateSlotsDisabledState();
            }
        } else {
            console.error('Failed to load slots');
        }
    } catch (error) {
        console.error('Error loading slots:', error);
    }
}

// 页面初始化
storeBaseDisabledState();
updateSlotsDisabledState();

// 事件委托：购物车更新按钮
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('update-cart-btn')) {
        e.preventDefault();
        const mealId = e.target.getAttribute('data-id');
        const itemContainer = e.target.closest('[data-meal-id]');
        const qtyInput = itemContainer ? itemContainer.querySelector('.cart-qty-input') : null;
        let quantity = qtyInput ? parseInt(qtyInput.value, 10) : 0;
        if (isNaN(quantity)) quantity = 0;
        if (quantity >= 0) {
            updateCart('update_quantity', mealId, quantity);
        }
    }
    if (e.target.classList.contains('remove-cart-btn')) {
        e.preventDefault();
        const mealId = e.target.getAttribute('data-id');
        if (confirm('Remove this item from cart?')) {
            updateCart('remove_item', mealId, 0);
        }
    }
});

// 日期选择变化：无刷新加载时段
const pickupDateInput = document.getElementById('pickup_date');
if (pickupDateInput) {
    pickupDateInput.addEventListener('change', function() {
        loadSlotsForDate(this.value);
    });
}
</script>

<?php
render_footer();
consume_old_input();
?>