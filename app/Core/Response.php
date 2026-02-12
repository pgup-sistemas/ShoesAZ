<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): void
    {
        $basePath = (string) App::config('base_path', '/ShoesAZ');
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && $basePath[0] !== '/') {
            $basePath = '/' . $basePath;
        }

        if ($basePath !== '' && !str_starts_with($path, $basePath . '/')) {
            $path = $basePath . $path;
        }
        header('Location: ' . $path);
        exit;
    }
}
