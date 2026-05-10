<?php

declare(strict_types=1);

// ==================== 购物车基础操作 ====================

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

// ==================== 菜品查询 ====================

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

/**
 * 购物车快照，包含菜品的 available_time
 */
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
        "SELECT id, slug, name, category, description, price, image_path, available_time
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
            'available_time' => $meal['available_time'],
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

// ==================== 取餐时段相关 ====================

/**
 * 根据 slot_value 判断属于早餐还是午餐/晚餐
 * 早餐：07:30 ~ 10:30（含边界，450-630分钟）
 * 午餐/晚餐：11:00 ~ 21:00（660-1260分钟）
 */
function get_slot_time_category(string $slotValue): string
{
    $parts = explode(':', $slotValue);
    if (count($parts) < 2) {
        throw new RuntimeException("Invalid pickup slot time format: {$slotValue}");
    }
    $hour = (int) $parts[0];
    $minute = (int) $parts[1];
    $totalMinutes = $hour * 60 + $minute;

    // 早餐 07:30 (450) ~ 10:30 (630)
    if ($totalMinutes >= 450 && $totalMinutes <= 630) {
        return 'breakfast';
    }
    // 午餐/晚餐 11:30 (690) ~ 21:00 (1260)
    if ($totalMinutes >= 690 && $totalMinutes <= 1260) {
        return 'lunch_dinner';
    }
    throw new RuntimeException("Invalid pickup slot time: {$slotValue}");
}

/**
 * 获取指定日期的 pickup slots 可用性，并考虑已过时段
 * 返回的每个 slot 包含 slot_value 字段
 */
function pickup_slots_with_availability(PDO $pdo, string $pickupDate, ?DateTimeImmutable $currentDateTime = null): array
{
    if ($currentDateTime === null) {
        $currentDateTime = new DateTimeImmutable();
    }
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
    $statement->execute(['pickup_date' => $pickupDate]);
    $slots = [];
    $isToday = ($pickupDate === $currentDateTime->format('Y-m-d'));
    foreach ($statement->fetchAll() as $slot) {
        $booked = (int) $slot['booked_count'];
        $remaining = max((int) $slot['capacity'] - $booked, 0);
        $availableByCapacity = $remaining > 0;
        $timeAvailable = true;
        if ($isToday) {
            $slotTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $slot['slot_value']);
            if ($slotTime && $slotTime <= $currentDateTime) {
                $timeAvailable = false;
            }
        }
        $slot['booked_count'] = $booked;
        $slot['remaining'] = $remaining;
        $slot['available'] = $availableByCapacity && $timeAvailable;
        $slots[] = $slot;
    }
    return $slots;
}

// ==================== 订单操作 ====================

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

/**
 * 获取用户余额
 */
function get_user_balance(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return (float) ($stmt->fetchColumn() ?? 0);
}

/**
 * 扣除用户余额（仅当余额足够时才会成功）
 */
function deduct_user_balance(PDO $pdo, int $userId, float $amount): bool
{
    if ($amount <= 0) {
        return true;
    }
    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
    $stmt->execute([$amount, $userId, $amount]);
    return $stmt->rowCount() > 0;
}

/**
 * 创建订单（包含时段匹配、余额扣款）
 */
function create_order_from_cart(PDO $pdo, array $payload, ?int $userId = null): int
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
    if ($customerName === '' || $pickupDate === '' || $pickupSlotId <= 0) {
        throw new RuntimeException('Please complete the checkout form.');
    }
    if (!is_valid_date_string($pickupDate)) {
        throw new RuntimeException('Pickup date format is invalid.');
    }
    $today = (new DateTimeImmutable())->setTime(0, 0, 0);
    $pickupDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $pickupDate);
    if (!$pickupDateObj || $pickupDateObj < $today) {
        throw new RuntimeException('Pickup date must be today or a future date.');
    }

    $pdo->beginTransaction();

    try {
        // 1. 余额扣款（仅当用户已登录且支付方式不是 "Cash" 时）
        if ($userId !== null && strtolower($paymentMethod) !== 'cash') {
            $currentBalance = get_user_balance($pdo, $userId);
            $totalAmount = $cart['total'];
            if ($currentBalance < $totalAmount) {
                throw new RuntimeException(
                    "Insufficient balance. Your balance: $" . number_format($currentBalance, 2) .
                    ", Total: $" . number_format($totalAmount, 2)
                );
            }
            if (!deduct_user_balance($pdo, $userId, $totalAmount)) {
                throw new RuntimeException("Balance deduction failed. Please ensure you have enough funds.");
            }
        }

        // 2. 锁定并检查 slot 容量
        $slotStatement = $pdo->prepare(
            'SELECT ps.id, ps.capacity, ps.slot_value,
                (SELECT COUNT(*) FROM orders o WHERE o.pickup_date = :pickup_date AND o.pickup_slot_id = ps.id) AS booked_count
             FROM pickup_slots ps
             WHERE ps.id = :slot_id
             FOR UPDATE'
        );
        $slotStatement->execute(['pickup_date' => $pickupDate, 'slot_id' => $pickupSlotId]);
        $slot = $slotStatement->fetch();
        if (!$slot) {
            throw new RuntimeException('The selected pickup slot was not found.');
        }
        if ((int) $slot['booked_count'] >= (int) $slot['capacity']) {
            throw new RuntimeException('The selected pickup slot is full.');
        }

        // 3. 检查当天已过时段
        $todayDate = (new DateTimeImmutable())->setTime(0, 0, 0);
        $pickupDateObj2 = new DateTimeImmutable($pickupDate);
        if ($pickupDateObj2->format('Y-m-d') === $todayDate->format('Y-m-d')) {
            $slotTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $slot['slot_value']);
            if ($slotTime && $slotTime <= new DateTimeImmutable()) {
                throw new RuntimeException('The selected pickup time has already passed. Please choose a later slot.');
            }
        }

        // 4. 购物车菜品时段匹配验证
        $slotCategory = get_slot_time_category($slot['slot_value']);
        foreach ($cart['items'] as $item) {
            if ($item['available_time'] !== $slotCategory) {
                throw new RuntimeException(
                    "Item '{$item['name']}' is not available for the selected pickup time. " .
                    ($slotCategory === 'breakfast'
                        ? 'Breakfast slots only allow breakfast items.'
                        : 'Lunch/Dinner slots only allow lunch/dinner items.')
                );
            }
        }

        // 5. 插入订单
        $insertOrder = $pdo->prepare(
            'INSERT INTO orders (
                order_code,
                user_id,
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
                :user_id,
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
            'user_id' => $userId,
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

        // 6. 插入订单明细
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

// ==================== 订单查询 ====================

function orders_with_search(PDO $pdo, string $search = '', ?int $userId = null): array
{
    $like = '%' . $search . '%';
    $sql = 'SELECT
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
            )';
    $params = [$search, $like, $like, $like, $like, $like];
    if ($userId !== null) {
        $sql .= ' AND o.user_id = ?';
        $params[] = $userId;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
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
    $stmt = $pdo->prepare("
        SELECT o.*, ps.label as pickup_slot_label
        FROM orders o
        JOIN pickup_slots ps ON o.pickup_slot_id = ps.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }
    $stmt = $pdo->prepare("
        SELECT meal_name, meal_price, quantity, line_total
        FROM order_items
        WHERE order_id = ?
        ORDER BY id
    ");
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();
    return $order;
}

function delete_order_record(PDO $pdo, int $orderId): bool
{
    $statement = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    $statement->execute([$orderId]);
    return $statement->rowCount() > 0;
}

// ==================== 用户资料操作 ====================

function update_user_profile(PDO $pdo, int $userId, string $full_name, string $phone): bool
{
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    return $stmt->execute([$full_name, $phone, $userId]);
}

function change_user_password(PDO $pdo, int $userId, string $currentPassword, string $newPassword)
{
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if (!password_verify($currentPassword, $user['password_hash'])) {
        return 'invalid_current';
    }
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $success = $updateStmt->execute([$newHash, $userId]);
    return $success ? true : false;
}