<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$cart = ['items' => [], 'subtotal' => 0.0, 'service_fee' => 0.0, 'total' => 0.0];
$slots = [];
$errors = [];
$pickupDate = $_SESSION['_old_input']['pickup_date'] ?? today_plus_one_day();

if ($pdo instanceof PDO) {
    // 处理 POST 请求：更新购物车 或 下单
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ensure_csrf_token();
        $action = (string) ($_POST['action'] ?? '');

        // 更新商品数量（增/删/改）
        if ($action === 'update_quantity') {
            $mealId = (int) ($_POST['meal_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 0);
            set_cart_quantity($mealId, $quantity);
            flash('success', 'Cart updated.');
            redirect(app_url('checkout.php'));
        }

        // 下单
        if ($action === 'place_order') {
            remember_old_input($_POST);

            try {
                $orderId = create_order_from_cart($pdo, $_POST);
                consume_old_input();
                flash('success', 'Order placed successfully.');
                redirect(app_url('order-details.php?id=' . $orderId));
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
                $pickupDate = (string) ($_POST['pickup_date'] ?? $pickupDate);
            }
        }
    }

    // 获取最新购物车快照和可用时段
    $cart = cart_snapshot($pdo);
    $slots = pickup_slots_with_availability($pdo, $pickupDate);
}

render_header('Checkout', 'checkout', cart_count(), $flashMessages, $dbError);
?>

<section class="container">
    <div class="intro-card">
        <p class="page-kicker">Pickup reservation</p>
        <h1 class="title h1 page-title">Choose your pickup time and confirm your order.</h1>
        <p class="panel-copy">
            Review your cart, select a suitable pickup slot, and send your order to the kitchen in just a few steps.
        </p>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert--error" style="margin-top: 24px;">
            <?= e($error) ?>
        </div>
    <?php endforeach; ?>

    <div class="orders-layout" style="margin-top: 24px;">
        <!-- 左侧：购物车明细（支持数量更新） -->
        <section class="detail-card">
            <p class="page-kicker">Cart summary</p>
            <h2 class="title h2">Meals ready for pickup</h2>

            <?php if (!$pdo instanceof PDO): ?>
                <div class="empty-card">
                    <p>The ordering service is temporarily unavailable.</p>
                </div>
            <?php elseif ($cart['items'] === []): ?>
                <div class="empty-card">
                    <h2 class="title h3">Your cart is empty</h2>
                    <p>Add meals from the menu page before placing an order.</p>
                    <div class="button-row">
                        <a class="btn" href="./index.php">Back to menu</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="history-list" style="margin-top: 24px;">
                    <?php foreach ($cart['items'] as $item): ?>
                        <article class="cart-preview__item">
                            <div>
                                <strong><?= e($item['name']) ?></strong>
                                <p class="line-muted">
                                    <?= e($item['category']) ?> | <?= money((float) $item['price']) ?> each
                                </p>
                            </div>
                            <!-- 数量更新表单（增、删、改均通过此表单） -->
                            <form method="post" class="form-inline">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="hidden" name="meal_id" value="<?= (int) $item['meal_id'] ?>">
                                <input class="small-input" type="number" min="0" max="20" name="quantity"
                                       value="<?= (int) $item['quantity'] ?>">
                                <button class="btn btn--secondary" type="submit">Update</button>
                                <strong><?= money((float) $item['line_total']) ?></strong>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="price-stack" style="margin-top: 24px;">
                    <div><span>Subtotal</span><strong><?= money((float) $cart['subtotal']) ?></strong></div>
                    <div><span>Service fee</span><strong><?= money((float) $cart['service_fee']) ?></strong></div>
                    <div class="total-line"><span>Total</span><strong><?= money((float) $cart['total']) ?></strong></div>
                </div>
            <?php endif; ?>
        </section>

        <!-- 右侧：订单表单（个人信息 + 时段选择 + 下单） -->
        <section class="detail-card">
            <p class="page-kicker">Pickup arrangement</p>
            <h2 class="title h2">Place your order</h2>

            <form method="post" class="field-grid" style="margin-top: 24px;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="place_order">

                <label class="field-label">
                    Student name
                    <input type="text" name="customer_name" value="<?= old('customer_name') ?>" required>
                </label>
                <label class="field-label">
                    Contact number
                    <input type="tel" name="phone" value="<?= old('phone') ?>" required>
                </label>
                <label class="field-label">
                    Pickup date
                    <input type="date" name="pickup_date" value="<?= e($pickupDate) ?>" required>
                </label>
                <label class="field-label">
                    Payment method
                    <select name="payment_method">
                        <?php $paymentOptions = ['Pay at pickup', 'Campus wallet', 'Cash']; ?>
                        <?php foreach ($paymentOptions as $option): ?>
                            <option value="<?= e($option) ?>"<?= old('payment_method', 'Pay at pickup') === $option ? ' selected' : '' ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="field-label field-span">
                    Pickup slot
                    <div class="slot-grid">
                        <?php foreach ($slots as $slot): ?>
                            <label class="slot-option<?= $slot['available'] ? '' : ' slot-option--disabled' ?>">
                                <input type="radio" name="pickup_slot_id" value="<?= (int) $slot['id'] ?>"
                                    <?= old('pickup_slot_id') === (string) $slot['id'] ? ' checked' : '' ?>
                                    <?= $slot['available'] ? '' : ' disabled' ?>>
                                <strong><?= e($slot['label']) ?></strong>
                                <span class="line-muted">Capacity <?= (int) $slot['capacity'] ?> | <?= (int) $slot['remaining'] ?> left</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <label class="field-label field-span">
                    Note for canteen staff
                    <textarea name="note"><?= old('note') ?></textarea>
                </label>

                <div class="button-row field-span">
                    <button class="btn" type="submit" <?= $cart['items'] === [] ? ' disabled' : '' ?>>
                        Confirm order
                    </button>
                    <a class="btn btn--secondary" href="./orders.php">View order history</a>
                </div>
            </form>
        </section>
    </div>
</section>

<?php
render_footer();
consume_old_input();
?>