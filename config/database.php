<?php

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    /** @var array $cfg */
    $cfg = require $localFile;
    return $cfg;
}

$host = (string) (getenv('DB_HOST') ?: 'shoesaz.mysql.dbaas.com.br');
$name = (string) (getenv('DB_NAME') ?: 'shoesaz');
$charset = (string) (getenv('DB_CHARSET') ?: 'utf8mb4');
$username = (string) (getenv('DB_USER') ?: 'shoesaz');
$password = (string) (getenv('DB_PASS') ?: 'Shoesaz#2026');

return [
    'dsn' => "mysql:host={$host};dbname={$name};charset={$charset}",
    'username' => $username,
    'password' => $password,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
