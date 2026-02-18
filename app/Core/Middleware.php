<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware para proteção de rotas
 */
final class Middleware
{
    private static array $publicRoutes = [
        '/login',
        '/recuperar-senha',
        '/nova-senha',
        '/public',
    ];

    /**
     * Verifica se a rota requer autenticação
     */
    public static function requireAuth(string $path): bool
    {
        foreach (self::$publicRoutes as $publicRoute) {
            if ($path === $publicRoute || str_starts_with($path, $publicRoute . '/')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Executa verificação de autenticação
     */
    public static function checkAuth(string $path): void
    {
        if (self::requireAuth($path) && !Auth::check()) {
            Response::redirect('/login');
            exit;
        }
    }

    /**
     * Session timeout check (15 minutos de inatividade)
     */
    public static function checkSessionTimeout(int $timeout = 900): void
    {
        if (!Auth::check()) {
            return;
        }

        $lastActivity = (int) ($_SESSION['last_activity'] ?? time());
        if (time() - $lastActivity > $timeout) {
            Auth::logout();
            Response::redirect('/login?timeout=1');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }
}
