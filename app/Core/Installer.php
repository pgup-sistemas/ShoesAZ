<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Helper para detectar e gerenciar estado de instalação
 */
final class Installer
{
    private const LOCK_FILE = __DIR__ . '/../../database/install.lock';
    private const INSTALLED_FLAG = __DIR__ . '/../../.installed';

    /**
     * Verifica se o sistema está instalado
     */
    public static function isInstalled(): bool
    {
        return is_file(self::LOCK_FILE) || is_file(self::INSTALLED_FLAG);
    }

    /**
     * Marca como instalado
     */
    public static function markAsInstalled(): void
    {
        file_put_contents(self::LOCK_FILE, 'installed_at=' . date('c'));
        file_put_contents(self::INSTALLED_FLAG, 'installed_at=' . date('c'));
    }

    /**
     * Remove marcação de instalação (para reinstalar)
     */
    public static function uninstall(): void
    {
        if (is_file(self::LOCK_FILE)) {
            unlink(self::LOCK_FILE);
        }
        if (is_file(self::INSTALLED_FLAG)) {
            unlink(self::INSTALLED_FLAG);
        }
    }

    /**
     * Obtém informações de instalação
     */
    public static function getInfo(): ?array
    {
        $file = self::LOCK_FILE;
        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if (!$content) {
            return null;
        }

        $data = [];
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $data[$key] = $value;
        }

        return $data;
    }
}
