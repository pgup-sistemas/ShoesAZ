<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB
{
    private static array $config = [];
    private static ?PDO $pdo = null;

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        self::$pdo = new PDO(
            self::$config['dsn'],
            self::$config['username'],
            self::$config['password'],
            self::$config['options'] ?? []
        );

        return self::$pdo;
    }
}
