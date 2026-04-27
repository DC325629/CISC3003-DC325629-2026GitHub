<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_url('orders.php'));
}

ensure_csrf_token();

if (!$pdo instanceof PDO) {
    flash('error', 'Database connection is unavailable.');
    redirect(app_url('orders.php'));
}

$orderId = (int) ($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    flash('error', 'Invalid order id.');
    redirect(app_url('orders.php'));
}

if (delete_order_record($pdo, $orderId)) {
    flash('success', 'Order record deleted.');
} else {
    flash('error', 'Order record was not found.');
}

redirect(app_url('orders.php'));
