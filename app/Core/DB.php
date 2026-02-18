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

        $options = self::$config['options'] ?? [];
        
        // Adicionar timeouts e configurações otimizadas
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES] = false;
        $options[PDO::ATTR_TIMEOUT] = 10; // 10 segundos timeout
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";

        self::$pdo = new PDO(
            self::$config['dsn'],
            self::$config['username'],
            self::$config['password'],
            $options
        );

        return self::$pdo;
    }
}
