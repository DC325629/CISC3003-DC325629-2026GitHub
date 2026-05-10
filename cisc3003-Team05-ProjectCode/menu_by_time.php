<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/repositories.php';

// 未登录用户重定向到登录页
if (!isLoggedIn()) {
    flash('error', 'Please login to access the menu.');
    redirect(app_url('login.php'));
}

// 获取当前实际时段（基于服务器时间）
function getCurrentTimeSlot(): string {
    $hour = (int) date('H');
    $minute = (int) date('i');
    if (($hour == 7 && $minute >= 30) || ($hour >= 8 && $hour <= 9) || ($hour == 10 && $minute <= 30)) {
        return 'breakfast';
    }
    if (($hour == 11 && $minute >= 30) || ($hour >= 12 && $hour <= 20) || ($hour == 21 && $minute == 0)) {
        return 'lunch_dinner';
    }
    else {
        return 'breakfast';
    }
}
$currentTimeSlot = getCurrentTimeSlot();

// 检查是否需要隐藏图片（1肉1菜等）
function shouldHideImage(string $name): bool {
    $keywords = ['1肉1菜', '2肉1菜', '3肉1菜'];
    foreach ($keywords as $keyword) {
        if (strpos($name, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// ========== AJAX 购物车处理（无刷新）==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    ensure_csrf_token();

    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'You must login to modify your cart.',
            'need_login' => true
        ]);
        exit;
    }

    $response = ['success' => false, 'message' => '', 'cart_html' => '', 'cart_count' => 0];

    try {
        $action = $_POST['action'] ?? '';
        $mealId = (int) ($_POST['meal_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($action === 'add') {
            // 验证该菜品是否属于当前时段
            $stmt = $pdo->prepare("SELECT available_time FROM meals WHERE id = ? AND is_active = 1");
            $stmt->execute([$mealId]);
            $meal = $stmt->fetch();
            if (!$meal || $meal['available_time'] !== $currentTimeSlot) {
                throw new Exception('You can only order meals from the current time period.');
            }
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

$timeSlot = $_GET['time'] ?? 'lunch_dinner';
if (!in_array($timeSlot, ['breakfast', 'lunch_dinner'])) {
    $timeSlot = 'lunch_dinner';
}

$keyword = trim($_GET['keyword'] ?? '');
$category = trim($_GET['category'] ?? '');

$groupedMeals = [];
$allCategories = [];
$cart = ['items' => [], 'subtotal' => 0.0, 'service_fee' => 0.0, 'total' => 0.0];

if ($pdo instanceof PDO) {
    $cart = cart_snapshot($pdo);
    
    $catStmt = $pdo->prepare("
        SELECT DISTINCT category
        FROM meals
        WHERE is_active = 1 AND available_time = ?
        ORDER BY category
    ");
    $catStmt->execute([$timeSlot]);
    $allCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $sql = "
        SELECT id, name, category, description, price, image_path, available_time
        FROM meals
        WHERE is_active = 1 AND available_time = :time
    ";
    $params = [':time' => $timeSlot];
    
    if (!empty($category)) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($keyword)) {
        $sql .= " AND name LIKE :keyword";
        $params[':keyword'] = '%' . $keyword . '%';
    }
    
    $sql .= " ORDER BY category, name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();
    
    foreach ($meals as $meal) {
        $cat = $meal['category'];
        if (!isset($groupedMeals[$cat])) {
            $groupedMeals[$cat] = [];
        }
        $groupedMeals[$cat][] = $meal;
    }
}

$pageTitle = ($timeSlot === 'breakfast') ? 'Breakfast Menu' : 'Lunch & Dinner Menu';
$isCurrentPeriod = ($timeSlot === $currentTimeSlot);
$isLoggedIn = isLoggedIn();

render_header($pageTitle, 'menu_by_time', cart_count(), $flashMessages, $dbError);
?>
<style>
    .filter-bar {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    .filter-group label {
        display: block;
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }
    .filter-group input, .filter-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 30px;
        font-size: 1.4rem;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
    }
    .meal-card {
        transition: transform 0.2s;
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }
    .meal-card:hover { transform: translateY(-3px); }
    .meal-card .meal-card__title { font-size: 1.6rem; font-weight: 600; margin: 0 0 4px 0; }
    .meal-card .meal-price { font-size: 1.5rem; font-weight: 700; color: var(--text-sinopia); }
    .meal-card .qty-input { width: 65px; padding: 6px 8px; border-radius: 30px; border: 1px solid #ddd; text-align: center; font-size: 1.3rem; }
    .meal-card .add-to-cart-btn { padding: 6px 14px; font-size: 1.3rem; }
    .add-to-cart-btn.disabled, button.disabled {
        background-color: #ccc;
        cursor: not-allowed;
        opacity: 0.6;
        pointer-events: none;
    }
    .period-warning {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 1.4rem;
    }

    /* 购物车侧边栏样式 + 滚动区域 */
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

<section class="page-hero" style="background: var(--bg-seashell); padding-block: 100px 40px;">
    <div class="container">
        <p class="page-kicker">Freshly Prepared</p>
        <h1 class="title h1 page-title"><?= e($pageTitle) ?></h1>
        <p class="page-description" style="font-size: 1.5rem;">
            Browse meals by time, category, or name. Adjust quantities and order directly.
        </p>
    </div>
</section>

<div class="container" style="margin-block: 30px 60px;">
    <!-- 时段切换选项卡 -->
    <div class="time-tabs" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 30px;">
        <a href="?time=breakfast&<?= http_build_query(['keyword' => $keyword, 'category' => $category]) ?>" 
           class="btn <?= $timeSlot === 'breakfast' ? '' : 'btn--secondary' ?>" style="padding: 8px 28px; font-size: 1.4rem;">🍳 Breakfast</a>
        <a href="?time=lunch_dinner&<?= http_build_query(['keyword' => $keyword, 'category' => $category]) ?>" 
           class="btn <?= $timeSlot === 'lunch_dinner' ? '' : 'btn--secondary' ?>" style="padding: 8px 28px; font-size: 1.4rem;">🍽️ Lunch & Dinner</a>
    </div>

    <!-- 如果不是当前时段，显示警告提示 -->
    <?php if (!$isCurrentPeriod): ?>
        <div class="period-warning">
            ⚠️ You are viewing the <strong><?= $timeSlot === 'breakfast' ? 'Breakfast' : 'Lunch & Dinner' ?></strong> menu.
            However, the current time is <strong><?= $currentTimeSlot === 'breakfast' ? 'Breakfast' : 'Lunch & Dinner' ?></strong>.
            You can only add items from the current time period to your cart.
        </div>
    <?php endif; ?>

    <!-- 筛选栏 -->
    <div class="filter-bar">
        <form method="get" class="filter-form">
            <input type="hidden" name="time" value="<?= e($timeSlot) ?>">
            <div class="filter-group">
                <label>🔍 Search by name</label>
                <input type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="e.g., Chicken, Rice, Noodle">
            </div>
            <div class="filter-group">
                <label>📂 Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn" style="padding: 10px 20px;">Apply Filters</button>
                <a href="?time=<?= e($timeSlot) ?>" class="btn btn--secondary" style="padding: 10px 20px;">Clear</a>
            </div>
        </form>
    </div>

    <?php if (!$pdo instanceof PDO): ?>
        <div class="empty-card"><p>Unable to load menu at this time.</p></div>
    <?php elseif (empty($groupedMeals)): ?>
        <div class="empty-card"><p>No meals found for the selected criteria. Try changing filters.</p></div>
    <?php else: ?>
        <div class="menu-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: 32px;">
            <div>
                <?php foreach ($groupedMeals as $categoryName => $mealsInCat): ?>
                    <section class="menu-category-section" style="margin-bottom: 50px;">
                        <h2 class="title h2" style="border-left: 6px solid var(--bg-sinopia); padding-left: 18px; margin-bottom: 24px; font-size: 2rem;"><?= e($categoryName) ?></h2>
                        <div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
                            <?php foreach ($mealsInCat as $meal): ?>
                                <article class="meal-card" data-meal-id="<?= (int) $meal['id'] ?>">
                                    <div class="meal-card__image" style="aspect-ratio: 1; overflow: hidden; background: #f7f3ef; display: flex; align-items: center; justify-content: center;">
                                        <?php if (shouldHideImage($meal['name'])): ?>
                                            <!-- 不显示图片 -->
                                        <?php else: ?>
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
                                            <?php if (!$isLoggedIn): ?>
                                                <input type="number" class="qty-input" value="1" min="1" max="20" disabled>
                                                <button class="btn add-to-cart-btn disabled" data-id="<?= (int) $meal['id'] ?>" disabled>Add to Cart</button>
                                                <small style="color:#c62828;">Login to order</small>
                                            <?php elseif (!$isCurrentPeriod): ?>
                                                <input type="number" class="qty-input" value="1" min="1" max="20" disabled>
                                                <button class="btn add-to-cart-btn disabled" data-id="<?= (int) $meal['id'] ?>" disabled>Add to Cart</button>
                                                <small style="color:#c62828;">Not available now</small>
                                            <?php else: ?>
                                                <input type="number" class="qty-input" value="1" min="1" max="20">
                                                <button class="btn add-to-cart-btn" data-id="<?= (int) $meal['id'] ?>">Add to Cart</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <aside id="cart-sidebar">
                <?= renderCartSidebar($cart) ?>
            </aside>
        </div>
    <?php endif; ?>
</div>

<script>
let csrfToken = document.querySelector('#cart-sidebar input[name="_csrf"]')?.value || '<?= csrf_token() ?>';

async function post(action, mealId, quantity) {
    const formData = new URLSearchParams();
    formData.append('_csrf', csrfToken);
    formData.append('action', action);
    formData.append('meal_id', mealId);
    if (quantity !== undefined) formData.append('quantity', quantity);

    const response = await fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    });
    const data = await response.json();
    if (data.success) {
        document.getElementById('cart-sidebar').innerHTML = data.cart_html;
        const newToken = document.querySelector('#cart-sidebar input[name="_csrf"]');
        if (newToken) csrfToken = newToken.value;
        const checkoutSpan = document.querySelector('.header-action .btn .span');
        if (checkoutSpan) {
            checkoutSpan.textContent = data.cart_count > 0 ? 'Start Checkout (' + data.cart_count + ')' : 'Start Checkout';
        }
    } else {
        if (data.need_login) {
            if (confirm('You need to login first. Click OK to go to login page.')) {
                window.location.href = 'login.php';
            }
        } else {
            alert(data.message || 'Operation failed');
        }
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-btn') && !e.target.disabled) {
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
        if (confirm('Remove this item from cart?')) {
            post('remove', mealId, 0);
        }
    }
});
</script>

<?php
function renderCartSidebar(array $cart): string {
    ob_start();
    ?>
    <div class="cart-preview">
        <div>
            <p class="page-kicker">Order Summary</p>
            <h2 class="title h2">Ready to order</h2>
            <p class="panel-copy" style="font-size:1.3rem;">Adjust quantities or remove items below.</p>
        </div>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-card" style="text-align: center; padding: 30px 16px;">
                <p style="font-size:1.5rem;">🛒 Your cart is empty</p>
                <p style="font-size:1.3rem;">Add delicious meals from the menu.</p>
            </div>
        <?php else: ?>
            <div class="cart-preview__list">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="cart-preview__item" style="border-bottom: 1px solid #eee; padding-bottom: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <div class="cart-item-name"><?= e($item['name']) ?></div>
                                <div class="cart-item-price"><?= money((float) $item['price']) ?> each</div>
                            </div>
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
    <?php
    return ob_get_clean();
}

render_footer();
?>