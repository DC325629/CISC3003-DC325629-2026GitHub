<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Macau');

require_once __DIR__ . '/helpers.php';

$config = config();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/\\');
if ($basePath == '/' || $basePath == '\\') {
    $basePath = '';
}
$dynamicSiteUrl = $protocol . $host . $basePath;

define('DYNAMIC_SITE_URL', $dynamicSiteUrl);

$pdo = null;
$dbError = null;
try {
    $dbConfig = $config['db'];
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$flashMessages = consume_flash_messages();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true;
}