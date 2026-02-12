<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

final class AuditoriaService
{
    public static function log(?array $user, string $acao, ?string $tabela = null, ?int $registroId = null, mixed $antes = null, mixed $depois = null): void
    {
        $usuarioId = $user['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = DB::pdo()->prepare(
            'INSERT INTO auditoria (usuario_id, acao, tabela, registro_id, dados_antes, dados_depois, ip) VALUES (:usuario_id, :acao, :tabela, :registro_id, :dados_antes, :dados_depois, :ip)'
        );

        $stmt->execute([
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'tabela' => $tabela,
            'registro_id' => $registroId,
            'dados_antes' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'dados_depois' => $depois === null ? null : json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip' => $ip,
        ]);
    }
}
