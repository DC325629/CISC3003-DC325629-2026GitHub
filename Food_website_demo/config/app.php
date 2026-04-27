<?php

declare(strict_types=1);

return [
    'app_name' => 'Crispy College Meals',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'cisc3003_team05',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'service_fee' => 3.00,
];
