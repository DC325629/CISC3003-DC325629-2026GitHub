function add_to_cart(int $mealId, int $quantity = 1): void
{
    $cart = cart_map();
    $cart[$mealId] = max(0, (int) ($cart[$mealId] ?? 0) + $quantity);
    if ($cart[$mealId] <= 0) {
        unset($cart[$mealId]);
    }
    $_SESSION['cart'] = $cart;
}

function cart_map(): array
{
    return $_SESSION['cart'] ?? [];
}