<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

final class NumeracaoService
{
    public static function gerar(string $tipo, ?int $ano = null): string
    {
        $ano = $ano ?? (int) date('Y');

        $stmt = DB::pdo()->prepare(
            'SELECT ultimo_numero FROM sequenciais WHERE tipo = :tipo AND ano = :ano FOR UPDATE'
        );
        $stmt->execute(['tipo' => $tipo, 'ano' => $ano]);
        $row = $stmt->fetch();

        if (!$row) {
            $stmt = DB::pdo()->prepare(
                'INSERT INTO sequenciais (tipo, ano, ultimo_numero) VALUES (:tipo, :ano, 1)'
            );
            $stmt->execute(['tipo' => $tipo, 'ano' => $ano]);
            $numero = 1;
        } else {
            $numero = (int) $row['ultimo_numero'] + 1;
            $stmt = DB::pdo()->prepare(
                'UPDATE sequenciais SET ultimo_numero = :numero WHERE tipo = :tipo AND ano = :ano'
            );
            $stmt->execute(['numero' => $numero, 'tipo' => $tipo, 'ano' => $ano]);
        }

        return sprintf('%d-%06d', $ano, $numero);
    }

    public static function proximoNumero(string $tipo, ?int $ano = null): int
    {
        $ano = $ano ?? (int) date('Y');

        $stmt = DB::pdo()->prepare(
            'SELECT ultimo_numero FROM sequenciais WHERE tipo = :tipo AND ano = :ano'
        );
        $stmt->execute(['tipo' => $tipo, 'ano' => $ano]);
        $row = $stmt->fetch();

        return $row ? (int) $row['ultimo_numero'] + 1 : 1;
    }
}
