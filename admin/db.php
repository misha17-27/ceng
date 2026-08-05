<?php
/*
 * Returns a shared PDO connection. Used by the admin panel and by
 * contact-handler.php (to store form submissions).
 * Requires admin/config.php (copy it from config.sample.php).
 */
static $pdo = null;
if ($pdo instanceof PDO) return $pdo;

$cfgFile = __DIR__ . '/config.php';
if (!is_file($cfgFile)) {
    throw new RuntimeException('admin/config.php not found — copy config.sample.php to config.php and fill in DB details.');
}
$cfg = require $cfgFile;
$dsn = "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=" . ($cfg['db_charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
return $pdo;
