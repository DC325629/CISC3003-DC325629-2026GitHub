<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/repositories.php';

start_app_session();

$flashMessages = consume_flash_messages();
$dbError = null;
$pdo = null;

try {
    $pdo = create_database_connection();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}