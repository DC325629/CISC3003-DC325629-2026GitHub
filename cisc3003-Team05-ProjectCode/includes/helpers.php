<?php

declare(strict_types=1);

function config(string $key = null)
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }

        $value = $value[$segment];
    }

    return $value;
}

function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $sessionPath = __DIR__ . '/../storage/sessions';

        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0777, true);
        }

        session_save_path($sessionPath);
        session_start();
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash_messages(): array
{
    $messages = $_SESSION['_flash_messages'] ?? [];
    unset($_SESSION['_flash_messages']);

    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function ensure_csrf_token(): void
{
    $submitted = $_POST['_csrf'] ?? '';

    if (!hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old_input'][$key] ?? $default);
}

function remember_old_input(array $input): void
{
    $_SESSION['_old_input'] = $input;
}

function consume_old_input(): void
{
    unset($_SESSION['_old_input']);
}

function today_plus_one_day(): string
{
    return (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
}

function is_valid_date_string(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function app_url(string $path): string
{
    return ltrim($path, '/');
}
