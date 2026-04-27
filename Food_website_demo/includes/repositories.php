<?php

declare(strict_types=1);

function cart_map(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart_map());
}

function add_to_cart(int $mealId, int $quantity = 1): void
{
    $cart = cart_map();
    $cart[$mealId] = max(0, (int) ($cart[$mealId] ?? 0) + $quantity);

    if ($cart[$mealId] <= 0) {
        unset($cart[$mealId]);
    }

    $_SESSION['cart'] = $cart;
}

function set_cart_quantity(int $mealId, int $quantity): void
{
    $cart = cart_map();

    if ($quantity <= 0) {
        unset($cart[$mealId]);
    } else {
        $cart[$mealId] = min(20, $quantity);
    }

    $_SESSION['cart'] = $cart;
}

function clear_cart(): void
{
    $_SESSION['cart'] = [];
}

function active_meals(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT id, slug, name, category, description, price, image_path
         FROM meals
         WHERE is_active = 1
         ORDER BY id'
    );

    return $statement->fetchAll();
}

function meal_index(PDO $pdo): array
{
    $index = [];

    foreach (active_meals($pdo) as $meal) {
        $index[(int) $meal['id']] = $meal;
    }

    return $index;
}

function cart_snapshot(PDO $pdo): array
{
    $cart = cart_map();

    if ($cart === []) {
        return [
            'items' => [],
            'subtotal' => 0.0,
            'service_fee' => 0.0,
            'total' => 0.0,
        ];
    }

    $mealIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($mealIds), '?'));
    $statement = $pdo->prepare(
        "SELECT id, slug, name, category, description, price, image_path
         FROM meals
         WHERE id IN ({$placeholders}) AND is_active = 1"
    );
    $statement->execute($mealIds);
    $meals = [];

    foreach ($statement->fetchAll() as $meal) {
        $meals[(int) $meal['id']] = $meal;
    }

    $items = [];
    $subtotal = 0.0;

    foreach ($cart as $mealId => $quantity) {
        $mealId = (int) $mealId;

        if (!isset($meals[$mealId])) {
            continue;
        }

        $meal = $meals[$mealId];
        $lineTotal = (float) $meal['price'] * $quantity;
        $subtotal += $lineTotal;
        $items[] = [
            'meal_id' => $mealId,
            'slug' => $meal['slug'],
            'name' => $meal['name'],
            'category' => $meal['category'],
            'description' => $meal['description'],
            'price' => (float) $meal['price'],
            'image_path' => $meal['image_path'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    $serviceFee = $items === [] ? 0.0 : (float) config('service_fee');

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'service_fee' => $serviceFee,
        'total' => $subtotal + $serviceFee,
    ];
}

function pickup_slots_with_availability(PDO $pdo, string $pickupDate): array
{
    $statement = $pdo->prepare(
        'SELECT
            ps.id,
            ps.slot_value,
            ps.label,
            ps.capacity,
            ps.sort_order,
            COUNT(o.id) AS booked_count
         FROM pickup_slots ps
         LEFT JOIN orders o
            ON o.pickup_slot_id = ps.id
           AND o.pickup_date = :pickup_date
         GROUP BY ps.id, ps.slot_value, ps.label, ps.capacity, ps.sort_order
         ORDER BY ps.sort_order'
    );
    $statement->execute([
        'pickup_date' => $pickupDate,
    ]);

    $slots = [];

    foreach ($statement->fetchAll() as $slot) {
        $booked = (int) $slot['booked_count'];
        $remaining = max((int) $slot['capacity'] - $booked, 0);
        $slot['booked_count'] = $booked;
        $slot['remaining'] = $remaining;
        $slot['available'] = $remaining > 0;
        $slots[] = $slot;
    }

    return $slots;
}

function generate_order_code(PDO $pdo): string
{
    do {
        $code = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
        $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_code = ?');
        $statement->execute([$code]);
        $exists = (int) $statement->fetchColumn() > 0;
    } while ($exists);

    return $code;
}

function create_order_from_cart(PDO $pdo, array $payload): int
{
    $customerName = trim((string) ($payload['customer_name'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $pickupDate = trim((string) ($payload['pickup_date'] ?? ''));
    $paymentMethod = trim((string) ($payload['payment_method'] ?? 'Pay at pickup'));
    $note = trim((string) ($payload['note'] ?? ''));
    $pickupSlotId = (int) ($payload['pickup_slot_id'] ?? 0);
    $cart = cart_snapshot($pdo);

    if ($cart['items'] === []) {
        throw new RuntimeException('Your cart is empty.');
    }

    if ($customerName === '' || $phone === '' || $pickupDate === '' || $pickupSlotId <= 0) {
        throw new RuntimeException('Please complete the checkout form.');
    }

    if (!is_valid_date_string($pickupDate)) {
        throw new RuntimeException('Pickup date format is invalid.');
    }

    $pdo->beginTransaction();

    try {
        $slotStatement = $pdo->prepare(
            'SELECT
                ps.id,
                ps.label,
                ps.capacity,
                (
                    SELECT COUNT(*)
                    FROM orders o
                    WHERE o.pickup_date = :pickup_date
                      AND o.pickup_slot_id = ps.id
                ) AS booked_count
             FROM pickup_slots ps
             WHERE ps.id = :slot_id
             FOR UPDATE'
        );
        $slotStatement->execute([
            'pickup_date' => $pickupDate,
            'slot_id' => $pickupSlotId,
        ]);
        $slot = $slotStatement->fetch();

        if (!$slot) {
            throw new RuntimeException('The selected pickup slot was not found.');
        }

        if ((int) $slot['booked_count'] >= (int) $slot['capacity']) {
            throw new RuntimeException('The selected pickup slot is full.');
        }

        $insertOrder = $pdo->prepare(
            'INSERT INTO orders (
                order_code,
                customer_name,
                phone,
                pickup_date,
                pickup_slot_id,
                payment_method,
                note,
                subtotal,
                service_fee,
                total
            ) VALUES (
                :order_code,
                :customer_name,
                :phone,
                :pickup_date,
                :pickup_slot_id,
                :payment_method,
                :note,
                :subtotal,
                :service_fee,
                :total
            )'
        );
        $insertOrder->execute([
            'order_code' => generate_order_code($pdo),
            'customer_name' => $customerName,
            'phone' => $phone,
            'pickup_date' => $pickupDate,
            'pickup_slot_id' => $pickupSlotId,
            'payment_method' => $paymentMethod,
            'note' => $note !== '' ? $note : null,
            'subtotal' => $cart['subtotal'],
            'service_fee' => $cart['service_fee'],
            'total' => $cart['total'],
        ]);

        $orderId = (int) $pdo->lastInsertId();
        $insertItem = $pdo->prepare(
            'INSERT INTO order_items (
                order_id,
                meal_id,
                meal_name,
                meal_price,
                quantity,
                line_total
            ) VALUES (
                :order_id,
                :meal_id,
                :meal_name,
                :meal_price,
                :quantity,
                :line_total
            )'
        );

        foreach ($cart['items'] as $item) {
            $insertItem->execute([
                'order_id' => $orderId,
                'meal_id' => $item['meal_id'],
                'meal_name' => $item['name'],
                'meal_price' => $item['price'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ]);
        }

        $pdo->commit();
        clear_cart();

        return $orderId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function orders_with_search(PDO $pdo, string $search = ''): array
{
    $like = '%' . $search . '%';
    $statement = $pdo->prepare(
        'SELECT
            o.id,
            o.order_code,
            o.customer_name,
            o.phone,
            o.pickup_date,
            o.payment_method,
            o.note,
            o.status,
            o.subtotal,
            o.service_fee,
            o.total,
            o.created_at,
            ps.label AS pickup_slot_label,
            (
                SELECT SUM(oi.quantity)
                FROM order_items oi
                WHERE oi.order_id = o.id
            ) AS item_count
         FROM orders o
         INNER JOIN pickup_slots ps ON ps.id = o.pickup_slot_id
         WHERE (
            ? = ""
            OR o.order_code LIKE ?
            OR o.customer_name LIKE ?
            OR o.phone LIKE ?
            OR CAST(o.pickup_date AS CHAR) LIKE ?
            OR EXISTS (
                SELECT 1
                FROM order_items oi
                WHERE oi.order_id = o.id
                  AND oi.meal_name LIKE ?
            )
         )
         ORDER BY o.created_at DESC, o.id DESC'
    );
    $statement->execute([$search, $like, $like, $like, $like, $like]);

    return $statement->fetchAll();
}

function order_stats(array $orders): array
{
    return [
        'count' => count($orders),
        'revenue' => array_reduce(
            $orders,
            static fn (float $carry, array $order): float => $carry + (float) $order['total'],
            0.0
        ),
    ];
}

function order_with_items(PDO $pdo, int $orderId): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            o.*,
            ps.label AS pickup_slot_label
         FROM orders o
         INNER JOIN pickup_slots ps ON ps.id = o.pickup_slot_id
         WHERE o.id = ?'
    );
    $statement->execute([$orderId]);
    $order = $statement->fetch();

    if (!$order) {
        return null;
    }

    $itemsStatement = $pdo->prepare(
        'SELECT meal_name, meal_price, quantity, line_total
         FROM order_items
         WHERE order_id = ?
         ORDER BY id'
    );
    $itemsStatement->execute([$orderId]);
    $order['items'] = $itemsStatement->fetchAll();

    return $order;
}

function delete_order_record(PDO $pdo, int $orderId): bool
{
    $statement = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    $statement->execute([$orderId]);

    return $statement->rowCount() > 0;
}