<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\DB;

final class Healthcheck
{
    private array $errors = [];
    private array $warnings = [];

    public function run(): int
    {
        $this->checkPhp();
        $pdo = $this->checkDbConnection();
        if ($pdo) {
            $this->checkDatabaseSchema($pdo);
        }
        $this->checkWritablePaths();

        $this->printReport();

        return $this->errors ? 1 : 0;
    }

    private function checkPhp(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->errors[] = 'PHP >= 8.0 é obrigatório. Versão atual: ' . PHP_VERSION;
        }

        if (!extension_loaded('pdo')) {
            $this->errors[] = 'Extensão PHP "pdo" não está habilitada.';
        }

        if (!extension_loaded('pdo_mysql')) {
            $this->errors[] = 'Extensão PHP "pdo_mysql" não está habilitada.';
        }

        if (!extension_loaded('gd')) {
            $this->warnings[] = 'Extensão PHP "gd" não está habilitada (upload/resize de imagens pode falhar).';
        }
    }

    private function checkDbConnection(): ?\PDO
    {
        try {
            $pdo = DB::pdo();
            $pdo->query('SELECT 1');
            return $pdo;
        } catch (\Throwable $e) {
            $this->errors[] = 'Falha ao conectar no banco: ' . $e->getMessage();
            return null;
        }
    }

    private function checkDatabaseSchema(\PDO $pdo): void
    {
        $tablesRequired = [
            'usuarios',
            'clientes',
            'orcamentos',
            'ordens_servico',
            'sapatos',
            'pagamentos',
            'caixa',
            'despesas',
            'empresa',
            'configuracoes',
            'recibos',
            'links_publicos',
        ];

        foreach ($tablesRequired as $t) {
            if (!$this->tableExists($pdo, $t)) {
                $this->errors[] = 'Tabela obrigatória ausente: ' . $t;
            }
        }

        $this->assertColumns($pdo, 'usuarios', ['id', 'nome', 'login', 'senha', 'perfil']);
        $this->assertColumns($pdo, 'usuarios', ['token_recuperacao', 'token_expira_em'], true);

        $this->assertColumns($pdo, 'empresa', ['id', 'nome', 'cnpj', 'endereco', 'telefone', 'email'], true);

        $this->assertColumns($pdo, 'pagamentos', ['id', 'os_id', 'valor', 'status', 'forma_pagamento', 'parcela_numero']);
        $this->assertColumns($pdo, 'pagamentos', ['vencimento', 'data_pagamento'], true);

        $this->assertColumns($pdo, 'recibos', ['id', 'numero', 'os_id', 'cliente_id', 'valor_total', 'forma_pagamento', 'garantia_dias', 'termos', 'created_at']);

        $this->assertColumns($pdo, 'configuracoes', ['id', 'chave', 'valor'], true);

        if ($this->tableExists($pdo, 'configuracoes')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM configuracoes WHERE chave = :c');
            $stmt->execute(['c' => 'termos_recibo']);
            $count = (int) $stmt->fetchColumn();
            if ($count === 0) {
                $this->warnings[] = 'Configuração "termos_recibo" não encontrada (ok se ainda não configurou).';
            }
        }
    }

    private function checkWritablePaths(): void
    {
        $paths = [
            realpath(__DIR__ . '/../public/uploads') ?: (__DIR__ . '/../public/uploads'),
            realpath(__DIR__ . '/../backups') ?: (__DIR__ . '/../backups'),
        ];

        foreach ($paths as $p) {
            if (!is_dir($p)) {
                $this->warnings[] = 'Diretório não existe: ' . $p;
                continue;
            }
            if (!is_writable($p)) {
                $this->errors[] = 'Diretório sem permissão de escrita: ' . $p;
            }
        }
    }

    private function tableExists(\PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function assertColumns(\PDO $pdo, string $table, array $columns, bool $warnOnly = false): void
    {
        if (!$this->tableExists($pdo, $table)) {
            return;
        }

        foreach ($columns as $col) {
            if (!$this->columnExists($pdo, $table, $col)) {
                if ($warnOnly) {
                    $this->warnings[] = "Coluna ausente (recomendado): {$table}.{$col}";
                } else {
                    $this->errors[] = "Coluna obrigatória ausente: {$table}.{$col}";
                }
            }
        }
    }

    private function printReport(): void
    {
        echo "\n=== ShoesAZ Healthcheck ===\n";
        echo 'Data: ' . date('Y-m-d H:i:s') . "\n\n";

        if (!$this->errors && !$this->warnings) {
            echo "OK: Nenhum problema encontrado.\n";
            return;
        }

        if ($this->errors) {
            echo "ERROS:\n";
            foreach ($this->errors as $e) {
                echo '- ' . $e . "\n";
            }
            echo "\n";
        }

        if ($this->warnings) {
            echo "AVISOS:\n";
            foreach ($this->warnings as $w) {
                echo '- ' . $w . "\n";
            }
            echo "\n";
        }

        echo $this->errors ? "RESULTADO: FAIL\n" : "RESULTADO: OK (com avisos)\n";
    }
}

$hc = new Healthcheck();
exit($hc->run());
