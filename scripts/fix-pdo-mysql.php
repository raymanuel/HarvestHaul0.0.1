<?php

/**
 * Patches Laravel's vendor database.php config to replace PHP 8.4-only
 * `use Pdo\Mysql` with PHP 8.2-compatible `constant('PDO::MYSQL_ATTR_SSL_CA')`.
 *
 * Laravel 12.64+ ships with `use Pdo\Mysql` which doesn't exist on PHP < 8.4.
 * This script runs as a composer post-install/post-update hook.
 */

$configPath = __DIR__ . '/../vendor/laravel/framework/config/database.php';

if (! file_exists($configPath)) {
    return;
}

$content = file_get_contents($configPath);

if (! str_contains($content, 'use Pdo\\Mysql')) {
    return;
}

$content = str_replace("use Pdo\\Mysql;\n", '', $content);
$content = str_replace("Mysql::ATTR_SSL_CA", "constant('PDO::MYSQL_ATTR_SSL_CA')", $content);

file_put_contents($configPath, $content);

echo "Patched vendor/laravel/framework/config/database.php for PHP 8.2 compatibility.\n";
