<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$orderId = (int) ($_GET['id'] ?? 0);
$order = null;

if ($pdo instanceof PDO && $orderId > 0) {
    $order = order_with_items($pdo, $orderId);
}

render_header('Order Details', 'orders', cart_count(), $flashMessages, $dbError);
?>
<section class="container">
  <?php if (!$pdo instanceof PDO): ?>
    <div class="empty-card">
      <p>Order details are temporarily unavailable.</p>
    </div>
  <?php elseif ($order === null): ?>
    <div class="empty-card">
      <p class="page-kicker">Order details</p>
      <h2 class="title h2">No order selected</h2>
      <p>Choose an existing order from history or place a new one from checkout.</p>
      <div class="button-row">
        <a class="btn" href="./orders.php">Open history</a>
        <a class="btn btn--secondary" href="./checkout.php">Go to checkout</a>
      </div>
    </div>
  <?php else: ?>
    <div class="intro-card">
      <div class="detail-topline">
        <div>
          <p class="page-kicker">Order summary</p>
          <h1 class="title h1"><?= e($order['order_code']) ?></h1>
        </div>
        <span class="status-pill"><?= e($order['status']) ?></span>
      </div>
      <p class="system-note">Placed at <?= e((string) $order['created_at']) ?></p>
    </div>

    <div class="detail-grid" style="margin-top: 24px;">
      <section class="detail-card">
        <h2 class="title h2">Pickup and customer details</h2>
        <div class="detail-meta">
          <p><strong>Customer:</strong> <?= e($order['customer_name']) ?></p>
          <p><strong>Phone:</strong> <?= e($order['phone']) ?></p>
          <p><strong>Pickup:</strong> <?= e($order['pickup_date']) ?> at <?= e($order['pickup_slot_label']) ?></p>
          <p><strong>Payment:</strong> <?= e($order['payment_method']) ?></p>
          <p><strong>Note:</strong> <?= e($order['note'] ?: 'No special request') ?></p>
        </div>

        <h2 class="title h2" style="margin-top: 24px;">Order items</h2>
        <div class="detail-items">
          <?php foreach ($order['items'] as $item): ?>
            <div class="detail-item">
              <div>
                <strong><?= e($item['meal_name']) ?></strong>
                <p class="line-muted">Qty <?= (int) $item['quantity'] ?> | <?= money((float) $item['meal_price']) ?> each</p>
              </div>
              <strong><?= money((float) $item['line_total']) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <aside class="detail-card">
        <h2 class="title h2">Cost breakdown</h2>
        <div class="price-stack" style="margin-top: 24px;">
          <div><span>Subtotal</span><strong><?= money((float) $order['subtotal']) ?></strong></div>
          <div><span>Service fee</span><strong><?= money((float) $order['service_fee']) ?></strong></div>
          <div class="total-line"><span>Total</span><strong><?= money((float) $order['total']) ?></strong></div>
        </div>

        <div class="button-row" style="margin-top: 24px;">
          <a class="btn" href="./orders.php">Back to history</a>
          <a class="btn btn--secondary" href="./checkout.php">Create another order</a>
        </div>

        <form method="post" action="./delete-order.php" style="margin-top: 20px;" onsubmit="return confirm('Delete this order record permanently?');">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
          <button class="btn danger-button" type="submit">Delete this order</button>
        </form>
      </aside>
    </div>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
