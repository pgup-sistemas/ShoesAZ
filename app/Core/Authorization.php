<?php

declare(strict_types=1);

namespace App\Core;

final class Authorization
{
    public static function requireLogin(): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
    }

    /** @param array<int, string> $roles */
    public static function requireRoles(array $roles): void
    {
        self::requireLogin();

        $user = Auth::user();
        $perfil = (string) ($user['perfil'] ?? '');

        if (!in_array($perfil, $roles, true)) {
            http_response_code(403);
            View::render('errors/403', [
                'pageTitle' => 'Acesso negado',
            ]);
            exit;
        }
    }
}
