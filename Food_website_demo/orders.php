<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$search = trim((string) ($_GET['q'] ?? ''));
$orders = [];
$stats = ['count' => 0, 'revenue' => 0.0];

if ($pdo instanceof PDO) {
    $orders = orders_with_search($pdo, $search);
    $stats = order_stats($orders);
}

render_header('Order History', 'orders', cart_count(), $flashMessages, $dbError);
?>
<section class="container">
  <div class="intro-card">
    <p class="page-kicker">Order history</p>
    <h1 class="title h1 page-title">Track, inspect, and manage your recent orders.</h1>
    <p class="panel-copy">
      Review past orders, check pickup details, and remove records you no longer need to keep.
    </p>
  </div>

  <div class="stats-grid" style="margin-top: 24px;">
    <article class="stats-card">
      <span class="stats-caption">Orders found</span>
      <strong><?= (int) $stats['count'] ?></strong>
    </article>
    <article class="stats-card">
      <span class="stats-caption">Revenue in view</span>
      <strong><?= money((float) $stats['revenue']) ?></strong>
    </article>
  </div>

  <div class="toolbar">
    <form method="get" class="form-inline" style="width: 100%;">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by order code, customer, phone, meal, or date">
      <button class="btn" type="submit">Search</button>
      <a class="btn btn--secondary" href="./orders.php">Reset</a>
    </form>
  </div>

  <?php if (!$pdo instanceof PDO): ?>
    <div class="empty-card">
      <p>The order list is temporarily unavailable.</p>
    </div>
  <?php elseif ($orders === []): ?>
    <div class="empty-card">
      <h2 class="title h3">No orders found</h2>
      <p>Place an order from checkout, or clear the search term to see all records.</p>
      <div class="button-row">
        <a class="btn" href="./checkout.php">Go to checkout</a>
      </div>
    </div>
  <?php else: ?>
    <div class="history-list">
      <?php foreach ($orders as $order): ?>
        <article class="history-card">
          <div class="history-card__header">
            <div>
              <p class="page-kicker">Order code</p>
              <h2 class="title h2"><?= e($order['order_code']) ?></h2>
            </div>
            <span class="status-pill"><?= e($order['status']) ?></span>
          </div>
          <div class="history-card__meta">
            <p><strong>Customer:</strong> <?= e($order['customer_name']) ?></p>
            <p><strong>Pickup:</strong> <?= e($order['pickup_date']) ?> at <?= e($order['pickup_slot_label']) ?></p>
            <p><strong>Items:</strong> <?= (int) ($order['item_count'] ?? 0) ?></p>
            <p><strong>Placed:</strong> <?= e((string) $order['created_at']) ?></p>
          </div>
          <div class="history-card__footer">
            <strong><?= money((float) $order['total']) ?></strong>
            <div class="button-row">
              <a class="btn" href="./order-details.php?id=<?= (int) $order['id'] ?>">View details</a>
              <form method="post" action="./delete-order.php" onsubmit="return confirm('Delete this order record permanently?');">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <button class="btn danger-button" type="submit">Delete</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
