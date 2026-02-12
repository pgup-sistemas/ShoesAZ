<?php

declare(strict_types=1);

$lockFile = __DIR__ . '/database/install.lock';

if (is_file($lockFile)) {
    http_response_code(403);
    echo 'Instalação já foi executada. Remova o arquivo database/install.lock para rodar novamente.';
    exit;
}

$databaseConfig = require __DIR__ . '/config/database.php';
$dsn = (string) ($databaseConfig['dsn'] ?? '');
$username = (string) ($databaseConfig['username'] ?? '');
$password = (string) ($databaseConfig['password'] ?? '');
$options = (array) ($databaseConfig['options'] ?? []);

function parseDsn(string $dsn): array
{
    // DSN example: mysql:host=127.0.0.1;dbname=shoesaz;charset=utf8mb4
    $out = [
        'driver' => '',
        'host' => '127.0.0.1',
        'port' => null,
        'dbname' => null,
        'charset' => 'utf8mb4',
    ];

    $parts = explode(':', $dsn, 2);
    $out['driver'] = $parts[0] ?? '';

    if (isset($parts[1])) {
        foreach (explode(';', $parts[1]) as $kv) {
            $kv = trim($kv);
            if ($kv === '' || !str_contains($kv, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $kv, 2));
            if ($k === 'host') {
                $out['host'] = $v;
            } elseif ($k === 'port') {
                $out['port'] = $v;
            } elseif ($k === 'dbname') {
                $out['dbname'] = $v;
            } elseif ($k === 'charset') {
                $out['charset'] = $v;
            }
        }
    }

    return $out;
}

function buildMysqlDsn(string $host, ?string $port, ?string $dbname, string $charset): string
{
    $s = 'mysql:host=' . $host;
    if ($port !== null && $port !== '') {
        $s .= ';port=' . $port;
    }
    if ($dbname !== null && $dbname !== '') {
        $s .= ';dbname=' . $dbname;
    }
    $s .= ';charset=' . $charset;
    return $s;
}

function sqlStatementsFromFile(string $path): array
{
    $sql = (string) file_get_contents($path);

    // Remove BOM
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    $lines = preg_split('/\R/', $sql) ?: [];
    $filtered = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim === '') {
            $filtered[] = $line;
            continue;
        }
        if (str_starts_with($trim, '--')) {
            continue;
        }
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);

    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';

    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($inString) {
            $buffer .= $ch;
            if ($ch === $stringChar) {
                $prev = $i > 0 ? $sql[$i - 1] : '';
                if ($prev !== '\\') {
                    $inString = false;
                    $stringChar = '';
                }
            }
            continue;
        }

        if ($ch === '\'' || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            $buffer .= $ch;
            continue;
        }

        if ($ch === ';') {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $stmt = trim($buffer);
    if ($stmt !== '') {
        $statements[] = $stmt;
    }

    return $statements;
}

$dsnParts = parseDsn($dsn);
$host = (string) ($dsnParts['host'] ?? '127.0.0.1');
$port = $dsnParts['port'] !== null ? (string) $dsnParts['port'] : null;
$dbname = (string) ($dsnParts['dbname'] ?? 'shoesaz');
$charset = (string) ($dsnParts['charset'] ?? 'utf8mb4');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminNome = trim((string) ($_POST['admin_nome'] ?? 'Administrador'));
    $adminLogin = trim((string) ($_POST['admin_login'] ?? 'admin'));
    $adminSenha = (string) ($_POST['admin_senha'] ?? '');

    if ($adminNome === '') {
        $errors[] = 'Nome do admin é obrigatório.';
    }
    if ($adminLogin === '') {
        $errors[] = 'Login do admin é obrigatório.';
    }
    if ($adminSenha === '') {
        $errors[] = 'Senha do admin é obrigatória.';
    }

    if (!$errors) {
        try {
            $pdoServer = new PDO(buildMysqlDsn($host, $port, null, $charset), $username, $password, $options);
            $pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbname) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $pdo = new PDO(buildMysqlDsn($host, $port, $dbname, $charset), $username, $password, $options);

            // If already has users, block install to avoid accidents
            try {
                $check = $pdo->query('SELECT COUNT(*) FROM usuarios');
                if ($check && (int) $check->fetchColumn() > 0) {
                    $errors[] = 'Já existem usuários cadastrados. Instalação bloqueada.';
                }
            } catch (Throwable $e) {
                // Table not exists yet
            }

            if (!$errors) {
                $schemaPath = __DIR__ . '/database/schema.sql';
                if (!is_file($schemaPath)) {
                    throw new RuntimeException('Arquivo schema.sql não encontrado em database/schema.sql');
                }

                $pdo->beginTransaction();
                foreach (sqlStatementsFromFile($schemaPath) as $stmt) {
                    $pdo->exec($stmt);
                }

                $hash = password_hash($adminSenha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, login, senha, perfil, ativo) VALUES (:nome, :login, :senha, :perfil, :ativo)');
                $stmt->execute([
                    'nome' => $adminNome,
                    'login' => $adminLogin,
                    'senha' => $hash,
                    'perfil' => 'Administrador',
                    'ativo' => 1,
                ]);

                $pdo->commit();

                file_put_contents($lockFile, 'installed_at=' . date('c'));
                $success = true;
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalação ShoesAZ</title>
  <style>
    body{font-family:Arial, sans-serif; background:#f5f5f5; padding:24px;}
    .wrap{max-width:560px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px;}
    .row{margin-bottom:12px;}
    label{display:block; font-size:14px; margin-bottom:6px;}
    input{width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;}
    button{padding:10px 14px; border:0; background:#0d6efd; color:#fff; border-radius:6px; cursor:pointer;}
    .err{background:#ffe9e9; border:1px solid #ffb3b3; padding:10px; border-radius:6px; margin-bottom:12px;}
    .ok{background:#e8fff0; border:1px solid #9be7b0; padding:10px; border-radius:6px; margin-bottom:12px;}
    .muted{color:#666; font-size:13px;}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin-top:0">Instalação (primeira execução)</h2>

    <div class="muted">
      Banco: <strong><?php echo htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      Host: <strong><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></strong>
      <?php if ($port): ?> | Porta: <strong><?php echo htmlspecialchars($port, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?>
    </div>

    <?php if ($success): ?>
      <div class="ok">
        Instalação concluída. Usuário administrador criado.<br>
        Agora acesse o sistema e faça login.
      </div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="err">
          <strong>Erro:</strong><br>
          <?php echo implode('<br>', array_map(fn($m) => htmlspecialchars((string) $m, ENT_QUOTES, 'UTF-8'), $errors)); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="row">
          <label>Nome do Admin</label>
          <input name="admin_nome" value="<?php echo htmlspecialchars((string) ($_POST['admin_nome'] ?? 'Administrador'), ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="row">
          <label>Login do Admin</label>
          <input name="admin_login" value="<?php echo htmlspecialchars((string) ($_POST['admin_login'] ?? 'admin'), ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="row">
          <label>Senha do Admin</label>
          <input type="password" name="admin_senha" value="" required>
        </div>
        <button type="submit">Instalar</button>
      </form>

      <p class="muted" style="margin-top:14px">
        Após instalar, o script cria <code>database/install.lock</code> para bloquear nova execução.
      </p>
    <?php endif; ?>
  </div>
</body>
</html>
