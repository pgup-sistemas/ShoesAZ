<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\DB;

final class LoginThrottleService
{
    public static function isLocked(string $login, ?string $ip): bool
    {
        $row = self::getRow($login, $ip);
        if (!$row) {
            return false;
        }

        $lockedUntil = $row['locked_until'] ?? null;
        if (!is_string($lockedUntil) || $lockedUntil === '') {
            return false;
        }

        return strtotime($lockedUntil) > time();
    }

    public static function registerFailure(string $login, ?string $ip): void
    {
        $maxAttempts = (int) App::config('login_max_attempts', 5);
        $windowSeconds = (int) App::config('login_window_seconds', 15 * 60);

        $now = date('Y-m-d H:i:s');
        $row = self::getRow($login, $ip);

        if (!$row) {
            if ($ip === null) {
                $stmt = DB::pdo()->prepare(
                    'INSERT INTO login_tentativas (login, ip, attempts, first_attempt_at, last_attempt_at, locked_until) VALUES (:login, NULL, 1, NOW(), NOW(), NULL)'
                );
                $stmt->execute(['login' => $login]);
            } else {
                $stmt = DB::pdo()->prepare(
                    'INSERT INTO login_tentativas (login, ip, attempts, first_attempt_at, last_attempt_at, locked_until) VALUES (:login, :ip, 1, NOW(), NOW(), NULL)'
                );
                $stmt->execute(['login' => $login, 'ip' => $ip]);
            }
            return;
        }

        $firstAttemptAt = (string) ($row['first_attempt_at'] ?? '');
        $attempts = (int) ($row['attempts'] ?? 0);
        $firstTs = $firstAttemptAt !== '' ? strtotime($firstAttemptAt) : 0;

        if ($firstTs <= 0 || (time() - $firstTs) > $windowSeconds) {
            $attempts = 0;
            $firstAttemptAt = $now;
        }

        $attempts++;

        $lockedUntil = null;
        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $windowSeconds);
        }

        $stmt = DB::pdo()->prepare(
            'UPDATE login_tentativas SET attempts = :attempts, first_attempt_at = :first_attempt_at, last_attempt_at = :last_attempt_at, locked_until = :locked_until WHERE id = :id'
        );
        $stmt->execute([
            'attempts' => $attempts,
            'first_attempt_at' => $firstAttemptAt,
            'last_attempt_at' => $now,
            'locked_until' => $lockedUntil,
            'id' => (int) $row['id'],
        ]);
    }

    public static function clear(string $login, ?string $ip): void
    {
        if ($ip === null) {
            $stmt = DB::pdo()->prepare('DELETE FROM login_tentativas WHERE login = :login AND ip IS NULL');
            $stmt->execute(['login' => $login]);
        } else {
            $stmt = DB::pdo()->prepare('DELETE FROM login_tentativas WHERE login = :login AND ip = :ip');
            $stmt->execute(['login' => $login, 'ip' => $ip]);
        }
    }

    private static function getRow(string $login, ?string $ip): array|null
    {
        if ($ip === null) {
            $stmt = DB::pdo()->prepare('SELECT * FROM login_tentativas WHERE login = :login AND ip IS NULL LIMIT 1');
            $stmt->execute(['login' => $login]);
        } else {
            $stmt = DB::pdo()->prepare('SELECT * FROM login_tentativas WHERE login = :login AND ip = :ip LIMIT 1');
            $stmt->execute(['login' => $login, 'ip' => $ip]);
        }
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}
