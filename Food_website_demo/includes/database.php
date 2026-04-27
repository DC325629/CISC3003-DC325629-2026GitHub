<?php

declare(strict_types=1);

function create_server_connection(): PDO
{
    $host = (string) config('db.host');
    $port = (int) config('db.port');
    $charset = (string) config('db.charset');

    return new PDO(
        "mysql:host={$host};port={$port};charset={$charset}",
        (string) config('db.username'),
        (string) config('db.password'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function create_database_connection(): PDO
{
    $host = (string) config('db.host');
    $port = (int) config('db.port');
    $charset = (string) config('db.charset');
    $database = (string) config('db.database');

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
        (string) config('db.username'),
        (string) config('db.password'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
