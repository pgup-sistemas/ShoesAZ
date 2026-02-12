<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function boot(): void
    {
        $key = (string) App::config('csrf_token_key', '_csrf');
        if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key]) || $_SESSION[$key] === '') {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
    }

    public static function token(): string
    {
        $key = (string) App::config('csrf_token_key', '_csrf');
        return (string) ($_SESSION[$key] ?? '');
    }

    public static function validate(?string $token): bool
    {
        return hash_equals(self::token(), (string) $token);
    }
}
