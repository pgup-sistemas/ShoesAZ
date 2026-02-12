<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['user'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        if (!$user) {
            return null;
        }
        return isset($user['perfil']) ? (string) $user['perfil'] : null;
    }

    /** @param array<int, string> $roles */
    public static function hasRole(array $roles): bool
    {
        $role = self::role();
        return $role !== null && in_array($role, $roles, true);
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'login' => $user['login'],
            'perfil' => $user['perfil'],
        ];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        unset($_SESSION['user'], $_SESSION['last_activity']);
    }

    public static function enforceTimeout(): void
    {
        if (!self::check()) {
            return;
        }

        $timeout = (int) App::config('session_timeout_seconds', 7200);
        $last = (int) ($_SESSION['last_activity'] ?? 0);

        if ($last > 0 && (time() - $last) > $timeout) {
            self::logout();
            Flash::add('warning', 'Sessão expirada. Faça login novamente.');
            Response::redirect('/login');
        }

        $_SESSION['last_activity'] = time();
    }
}
