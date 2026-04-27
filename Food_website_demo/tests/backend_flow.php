<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/schema.php';
require_once __DIR__ . '/../includes/repositories.php';

start_app_session();

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$server = create_server_connection();
install_schema($server);
$pdo = create_database_connection();

$pdo->exec('DELETE FROM order_items');
$pdo->exec('DELETE FROM orders');
clear_cart();

$meals = active_meals($pdo);
assert_true(count($meals) === 4, 'Expected 4 seeded meals.');

add_to_cart((int) $meals[0]['id'], 1);
add_to_cart((int) $meals[1]['id'], 2);

$cart = cart_snapshot($pdo);
assert_true(count($cart['items']) === 2, 'Expected 2 cart items.');
assert_true(abs($cart['total'] - 154.0) < 0.001, 'Expected total to equal 154.00.');

$slots = pickup_slots_with_availability($pdo, date('Y-m-d', strtotime('+1 day')));
assert_true(count($slots) === 6, 'Expected 6 pickup slots.');

$orderId = create_order_from_cart($pdo, [
    'customer_name' => 'Wang Yufeng',
    'phone' => '+85360001234',
    'pickup_date' => date('Y-m-d', strtotime('+1 day')),
    'pickup_slot_id' => $slots[1]['id'],
    'payment_method' => 'Campus wallet',
    'note' => 'No onion',
]);

$order = order_with_items($pdo, $orderId);
assert_true($order !== null, 'Expected inserted order to be found.');
assert_true($order['order_code'] !== '', 'Expected order code to exist.');
assert_true(count($order['items']) === 2, 'Expected 2 order items.');
assert_true(abs((float) $order['total'] - 154.0) < 0.001, 'Expected stored order total to equal 154.00.');

$orders = orders_with_search($pdo, 'Wang');
assert_true(count($orders) === 1, 'Expected search to find the inserted order.');

$deleted = delete_order_record($pdo, $orderId);
assert_true($deleted === true, 'Expected delete operation to report success.');
assert_true(order_with_items($pdo, $orderId) === null, 'Expected deleted order to disappear.');

echo "Backend flow test passed.\n";
