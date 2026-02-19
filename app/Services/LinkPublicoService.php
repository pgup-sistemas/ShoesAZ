<?php

namespace App\Services;

use App\Core\DB;

final class LinkPublicoService
{
    public static function buscarOuCriar(string $tipo, int $referenciaId, ?int $validadeDias = null): array
    {
        $validadeDias = $validadeDias ?? 30;

        $stmt = DB::pdo()->prepare(
            'SELECT * FROM links_publicos WHERE tipo = :tipo AND referencia_id = :ref_id AND data_expiracao > NOW() ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['tipo' => $tipo, 'ref_id' => $referenciaId]);
        $link = $stmt->fetch();

        if ($link) {
            return $link;
        }

        return self::criar($tipo, $referenciaId, $validadeDias);
    }

    public static function criar(string $tipo, int $referenciaId, int $validadeDias = 30): array
    {
        $token = bin2hex(random_bytes(32));
        $dataCriacao = date('Y-m-d H:i:s');
        $expiracao = date('Y-m-d H:i:s', strtotime("+{$validadeDias} days"));

        $stmt = DB::pdo()->prepare(
            'INSERT INTO links_publicos (token, tipo, referencia_id, data_criacao, data_expiracao) VALUES (:token, :tipo, :ref_id, :data_criacao, :expiracao)'
        );
        $stmt->execute([
            'token' => $token,
            'tipo' => $tipo,
            'ref_id' => $referenciaId,
            'data_criacao' => $dataCriacao,
            'expiracao' => $expiracao,
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        return [
            'id' => $id,
            'token' => $token,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'data_criacao' => $dataCriacao,
            'data_expiracao' => $expiracao,
            'acessos' => 0,
            'ultimo_acesso' => null,
        ];
    }

    public static function buscarPorToken(string $token): ?array
    {
        $stmt = DB::pdo()->prepare(
            'SELECT * FROM links_publicos WHERE token = :token AND data_expiracao > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function registrarAcesso(int $linkId): void
    {
        $stmt = DB::pdo()->prepare(
            'UPDATE links_publicos SET acessos = acessos + 1, ultimo_acesso = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $linkId]);
    }
}
