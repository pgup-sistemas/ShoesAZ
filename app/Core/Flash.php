<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function add(string $type, string $message): void
    {
        $key = (string) App::config('flash_key', '_flash');
        $_SESSION[$key][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function pull(): array
    {
        $key = (string) App::config('flash_key', '_flash');
        $items = $_SESSION[$key] ?? [];
        unset($_SESSION[$key]);
        return is_array($items) ? $items : [];
    }
}
