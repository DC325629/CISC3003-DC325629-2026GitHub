<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/repositories.php';

// 获取当前时段（早餐早餐 / 午晚餐）
function getCurrentTimeSlot(): string {
    $hour = (int) date('H');
    $minute = (int) date('i');
    // 早餐 07:30 - 10:30
    if (($hour == 7 && $minute >= 30) || ($hour >= 8 && $hour <= 9) || ($hour == 10 && $minute <= 30)) {
        return 'breakfast';
    }
    // 午/晚餐 11:30 - 21:00
    if (($hour == 11 && $minute >= 30) || ($hour >= 12 && $hour <= 20) || ($hour == 21 && $minute == 0)) {
        return 'lunch_dinner';
    }
    else {
        return 'breakfast';
    }
}

// 判断是否隐藏图片（1肉1菜等）
function shouldHideImage(string $name): bool {
    $keywords = ['1肉1菜', '2肉1菜', '3肉1菜'];
    foreach ($keywords as $keyword) {
        if (strpos($name, $keyword) !== false) return true;
    }
    return false;
}

// ========== AJAX 购物车处理（无刷新） ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    ensure_csrf_token();

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'You must login to modify your cart.', 'need_login' => true]);
        exit;
    }

    $response = ['success' => false, 'message' => '', 'cart_html' => '', 'cart_count' => 0];
    try {
        $action = $_POST['action'] ?? '';
        $mealId = (int) ($_POST['meal_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($action === 'add') {
            $quantity = max(1, $quantity);
            add_to_cart($mealId, $quantity);
        } elseif ($action === 'update') {
            set_cart_quantity($mealId, $quantity);
        } elseif ($action === 'remove') {
            set_cart_quantity($mealId, 0);
        } else {
            throw new Exception('Invalid action');
        }

        if ($pdo instanceof PDO) {
            $cart = cart_snapshot($pdo);
            $cartHtml = renderCartSidebar($cart);
            $response['success'] = true;
            $response['cart_html'] = $cartHtml;
            $response['cart_count'] = cart_count();
        } else {
            throw new Exception('Database error');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

// ========== 正常页面请求 ==========
if (!isLoggedIn()) {
    clear_cart();
}

$timeSlot = getCurrentTimeSlot();
$recommendedMeals = [];
if ($pdo instanceof PDO) {
    $stmt = $pdo->prepare("
        SELECT id, name, category, description, price, image_path, available_time
        FROM meals
        WHERE is_active = 1 AND available_time = ?
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([$timeSlot]);
    $recommendedMeals = $stmt->fetchAll();

    if (empty($recommendedMeals)) {
        $fallbackSlot = ($timeSlot === 'breakfast') ? 'lunch_dinner' : 'breakfast';
        $stmt = $pdo->prepare("
            SELECT id, name, category, description, price, image_path, available_time
            FROM meals
            WHERE is_active = 1 AND available_time = ?
            ORDER BY RAND()
            LIMIT 3
        ");
        $stmt->execute([$fallbackSlot]);
        $recommendedMeals = $stmt->fetchAll();
    }
}

$cart = ['items' => [], 'subtotal' => 0.0, 'service_fee' => 0.0, 'total' => 0.0];
if ($pdo instanceof PDO) $cart = cart_snapshot($pdo);

render_header('Home', 'home', cart_count(), $flashMessages, $dbError);
?>

<style>
    .meal-card { transition: transform 0.2s; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 3px 12px rgba(0,0,0,0.08); }
    .meal-card:hover { transform: translateY(-3px); }
    .meal-card .meal-card__title { font-size: 1.6rem; font-weight: 600; margin: 0 0 4px 0; }
    .meal-card .meal-price { font-size: 1.5rem; font-weight: 700; color: var(--text-sinopia); }
    .meal-card .qty-input { width: 65px; padding: 6px 8px; border-radius: 30px; border: 1px solid #ddd; text-align: center; font-size: 1.3rem; }
    .meal-card .add-to-cart-btn { padding: 6px 14px; font-size: 1.3rem; }

    /* ========== 购物车侧边栏 + 滚动区域 ========== */
    #cart-sidebar {
        background: #fff;
        border-radius: 24px;
        padding: 20px;
        border: 1px solid rgba(0,0,0,0.1);
        box-shadow: 0 6px 14px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 120px);
    }
    .cart-preview {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .cart-preview__list {
        flex: 1;
        overflow-y: auto;
        max-height: 400px;
        padding-right: 8px;
        margin-bottom: 16px;
    }
    .cart-preview__list::-webkit-scrollbar {
        width: 6px;
    }
    .cart-preview__list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .cart-preview__list::-webkit-scrollbar-thumb {
        background: #d49b3a;
        border-radius: 10px;
    }
    .cart-item-name { font-size: 1.5rem; font-weight: 600; }
    .cart-item-price { font-size: 1.3rem; color: #555; }
    .cart-item-line-total { font-size: 1.5rem; font-weight: 700; }
    .cart-item-controls input { width: 60px; padding: 5px; font-size: 1.3rem; }
    .cart-item-controls button { padding: 5px 10px; font-size: 1.2rem; }
    .price-stack { font-size: 1.4rem; margin-top: auto; }
    .total-line { font-size: 1.8rem; font-weight: 800; }

    @media (max-width: 992px) {
        .menu-layout { grid-template-columns: 1fr !important; }
        #cart-sidebar { margin-top: 40px; position: static !important; max-height: none; }
        .cart-preview__list { max-height: 300px; }
    }
</style>

<section class="hero landing-hero" aria-label="home">
  <div class="container">
    <div class="hero-content">
      <h1 class="h1 title hero-title">College Meals – Smart Meal Reservation</h1>
      <p class="section-text">Fresh campus favorites, quick pickup times, and a smooth ordering flow for lunch and dinner.</p>
      <div class="wrapper">
        <img src="./assets/images/down-arrow.png" width="40" height="40" alt="arrow" class="arrow">
        <a href="./checkout.php" class="btn"><span class="span">Checkout Now</span><ion-icon name="arrow-forward" aria-hidden="true"></ion-icon></a>
      </div>
    </div>
    <figure class="hero-banner img-holder" style="--width: 632; --height: 606;">
      <img src="./assets/images/hero-1.jpg" width="632" height="606" alt="Burger meal" class="img-cover">
    </figure>
    <img src="./assets/images/hero-shape-1.png" width="490" height="455" alt="shape" class="shape shape-1">
    <img src="./assets/images/hero-shape-2.png" width="512" height="512" alt="shape" class="shape shape-2">
    <img src="./assets/images/line-1.png" width="630" height="506" alt="line" class="shape shape-3">
  </div>
</section>

<section class="container">
  <?php if (!$pdo instanceof PDO): ?>
    <div class="empty-card" style="margin-top: 24px;"><h2 class="title h2">Service unavailable</h2><p>The menu is temporarily unavailable. Please try again shortly.</p><div class="button-row"><a class="btn" href="./index.php">Refresh</a></div></div>
  <?php else: ?>
    <section class="section menu landing-menu" id="menu" aria-labelledby="menu-label">
      <div class="landing-section-head text-center">
        <p class="section-subtitle" id="menu-label">
          <?= $timeSlot === 'breakfast' ? '🍳 Breakfast Time' : '🍽️ Lunch & Dinner Time' ?>
        </p>
        <h2 class="title h2 section-title">RECOMMENDED FOR YOU</h2>
        <div style="margin-top: 20px;"><a href="menu_by_time.php?time=<?= e(getCurrentTimeSlot()) ?>" class="btn btn--secondary" style="display: inline-block;">View Full Menu by Time <ion-icon name="arrow-forward-outline"></ion-icon></a></div>
      </div>
      <div class="menu-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: 32px; margin-top: 32px;">
        <div>
          <?php if (empty($recommendedMeals)): ?>
            <div class="empty-card">No meals available for this time period.</div>
          <?php else: ?>
            <div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
              <?php foreach ($recommendedMeals as $meal): ?>
                <article class="meal-card" data-meal-id="<?= (int) $meal['id'] ?>">
                  <div class="meal-card__image" style="aspect-ratio: 1; overflow: hidden; background: #f7f3ef; display: flex; align-items: center; justify-content: center;">
                    <?php if (!shouldHideImage($meal['name'])): ?>
                      <img src="<?= e($meal['image_path'] ?: './assets/images/placeholder.jpg') ?>" alt="<?= e($meal['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php endif; ?>
                  </div>
                  <div class="meal-card__meta" style="padding: 14px 14px 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                      <h3 class="meal-card__title"><?= e($meal['name']) ?></h3>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                      <span class="meal-price"><?= money((float) $meal['price']) ?></span>
                    </div>
                    <div class="add-to-cart-form" style="margin-top: 12px; display: flex; gap: 8px; align-items: center;">
                      <input type="number" class="qty-input" value="1" min="1" max="20">
                      <button class="btn add-to-cart-btn" data-id="<?= (int) $meal['id'] ?>">Add to Cart</button>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <aside id="cart-sidebar">
          <?= renderCartSidebar($cart) ?>
        </aside>
      </div>
    </section>
  <?php endif; ?>
</section>

<section class="section schedule landing-hours" id="hours" aria-labelledby="hours-label">
  <div class="container">
    <div class="schedule-content">
      <p class="section-subtitle" id="hours-label">Opening Hours</p>
      <h2 class="h2 title section-title">Serving You Every Day</h2>
      <p style="text-align: center; font-weight: var(--weight-semiBold); color: var(--text-rich-black-fogra-29); margin-bottom: 20px;">Monday – Sunday</p>
      <ul class="schedule-list">
        <li class="schedule-item"><p class="h4 title">Breakfast</p><div class="separator"></div><a href="menu_by_time.php?time=breakfast" class="time title schedule-link">07:30 – 10:30</a></li>
        <li class="schedule-item"><p class="h4 title">Lunch</p><div class="separator"></div><a href="menu_by_time.php?time=lunch_dinner" class="time title schedule-link">11:30 – 14:30</a></li>
        <li class="schedule-item"><p class="h4 title">Dinner</p><div class="separator"></div><a href="menu_by_time.php?time=lunch_dinner" class="time title schedule-link">17:30 – 21:00</a></li>
      </ul>
    </div>
    <div class="schedule-banner">
      <figure class="img-holder"><img src="./assets/images/schedule-banner.jpg" width="960" height="640" loading="lazy" alt="Restaurant service hours" class="img-cover"></figure>
    </div>
  </div>
</section>

<script>
let csrfToken = document.querySelector('input[name="_csrf"]')?.value || '<?= csrf_token() ?>';

async function post(action, mealId, quantity) {
    const formData = new URLSearchParams();
    formData.append('_csrf', csrfToken);
    formData.append('action', action);
    formData.append('meal_id', mealId);
    if (quantity !== undefined) formData.append('quantity', quantity);
    const response = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    });
    const data = await response.json();
    if (data.success) {
        document.getElementById('cart-sidebar').innerHTML = data.cart_html;
        const newToken = document.querySelector('#cart-sidebar input[name="_csrf"]');
        if (newToken) csrfToken = newToken.value;
        const checkoutSpan = document.querySelector('.header-action .btn .span');
        if (checkoutSpan) checkoutSpan.textContent = data.cart_count > 0 ? 'Start Checkout (' + data.cart_count + ')' : 'Start Checkout';
    } else {
        if (data.need_login) {
            if (confirm('You need to login first. Click OK to go to login page.')) window.location.href = 'login.php';
        } else {
            alert(data.message || 'Operation failed');
        }
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-btn')) {
        e.preventDefault();
        const mealId = e.target.getAttribute('data-id');
        const card = e.target.closest('.meal-card');
        const qtyInput = card ? card.querySelector('.qty-input') : null;
        const quantity = qtyInput ? qtyInput.value : 1;
        post('add', mealId, quantity);
    }
    if (e.target.classList.contains('update-cart-btn')) {
        e.preventDefault();
        const mealId = e.target.getAttribute('data-id');
        const qtyInput = document.getElementById('qty-' + mealId);
        const quantity = qtyInput ? qtyInput.value : 0;
        if (quantity > 0) post('update', mealId, quantity);
        else post('remove', mealId, 0);
    }
    if (e.target.classList.contains('remove-cart-btn')) {
        e.preventDefault();
        const mealId = e.target.getAttribute('data-id');
        post('remove', mealId, 0);
    }
});

const userLoggedIn = <?= json_encode(isLoggedIn()) ?>;
document.addEventListener('click', function(e) {
    let link = e.target.closest('a');
    if (!link) return;
    let href = link.getAttribute('href');
    if (!href) return;
    const protected = ['checkout.php', 'orders.php', 'order-details.php'];
    for (let path of protected) {
        if (href.includes(path)) {
            if (!userLoggedIn) {
                e.preventDefault();
                if (confirm('You need to login first. Click OK to go to login page.')) {
                    window.location.href = 'login.php';
                }
                return;
            }
        }
    }
});
</script>

<?php
function renderCartSidebar(array $cart): string {
    ob_start(); ?>
    <div class="cart-preview">
        <div><p class="page-kicker">Order Summary</p><h2 class="title h2">Ready to order</h2><p class="panel-copy" style="font-size:1.3rem;">Adjust quantities or remove items below.</p></div>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-card" style="text-align: center; padding: 30px 16px;"><p style="font-size:1.5rem;">🛒 Your cart is empty</p><p style="font-size:1.3rem;">Add delicious meals from the menu.</p></div>
        <?php else: ?>
            <div class="cart-preview__list">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="cart-preview__item" style="border-bottom: 1px solid #eee; padding-bottom: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between;">
                            <div><div class="cart-item-name"><?= e($item['name']) ?></div><div class="cart-item-price"><?= money((float) $item['price']) ?> each</div></div>
                            <div class="cart-item-line-total"><?= money((float) $item['line_total']) ?></div>
                        </div>
                        <div class="cart-item-controls" style="margin-top: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <label style="font-size:1.2rem;">Qty:</label>
                            <input type="number" id="qty-<?= $item['meal_id'] ?>" value="<?= (int) $item['quantity'] ?>" min="0" max="20" step="1">
                            <button class="btn btn--secondary update-cart-btn" data-id="<?= $item['meal_id'] ?>">Update</button>
                            <button class="btn remove-cart-btn" data-id="<?= $item['meal_id'] ?>" style="background:#c62828; color:white;">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="price-stack" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between;"><span>Subtotal</span><strong><?= money((float) $cart['subtotal']) ?></strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Service fee</span><strong><?= money((float) $cart['service_fee']) ?></strong></div>
                <div class="total-line" style="display: flex; justify-content: space-between;"><span>Total</span><strong><?= money((float) $cart['total']) ?></strong></div>
            </div>
        <?php endif; ?>
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="button-row" style="margin-top: 20px; display: flex; gap: 12px; justify-content: space-between;">
            <a class="btn" href="./checkout.php">Checkout</a>
            <a class="btn btn--secondary" href="./orders.php">History</a>
        </div>
    </div>
    <?php return ob_get_clean();
}
render_footer();
?>