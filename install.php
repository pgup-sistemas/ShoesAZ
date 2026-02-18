<?php

declare(strict_types=1);

// Detectar se já está instalado
$lockFile = __DIR__ . '/database/install.lock';
$installedFlag = __DIR__ . '/.installed';

if (is_file($lockFile) || is_file($installedFlag)) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ShoesAZ - Sistema Já Instalado</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                padding: 40px;
                text-align: center;
            }
            .icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                color: #2d3748;
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            p {
                color: #718096;
                line-height: 1.6;
                margin: 0;
                font-size: 16px;
            }
            .info {
                background: #edf2f7;
                border-left: 4px solid #667eea;
                padding: 15px;
                margin-top: 20px;
                text-align: left;
                border-radius: 4px;
            }
            .info strong {
                color: #2d3748;
            }
            code {
                background: #f7fafc;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Monaco', 'Courier New', monospace;
                color: #e53e3e;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✅</div>
            <h1>Sistema Já Instalado</h1>
            <p>O ShoesAZ já foi configurado e está pronto para uso.</p>
            <p style="margin-top: 20px;">
                <strong>Acesse o sistema:</strong><br>
                <a href="/" style="color: #667eea; text-decoration: none; font-weight: 600;">Ir para o Dashboard</a>
            </p>
            <div class="info">
                <strong>Se precisar reinstalar:</strong><br>
                Remova os arquivos <code>database/install.lock</code> e <code>.installed</code> do servidor
            </div>
        </div>
    </body>
    </html>
    <?php
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

$charset = 'utf8mb4';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do Banco de Dados
    $dbHost = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = trim((string) ($_POST['db_port'] ?? '3306'));
    $dbName = trim((string) ($_POST['db_name'] ?? 'shoesaz'));
    $dbUser = trim((string) ($_POST['db_user'] ?? 'root'));
    $dbPass = (string) ($_POST['db_pass'] ?? '');

    // Dados do Admin
    $adminNome = trim((string) ($_POST['admin_nome'] ?? 'Administrador'));
    $adminLogin = trim((string) ($_POST['admin_login'] ?? 'admin'));
    $adminSenha = (string) ($_POST['admin_senha'] ?? '');

    // Validações
    if ($dbHost === '') {
        $errors[] = 'Host do banco de dados é obrigatório.';
    }
    if ($dbPort === '') {
        $errors[] = 'Porta do banco de dados é obrigatória.';
    } elseif (!is_numeric($dbPort)) {
        $errors[] = 'Porta do banco de dados deve ser um número.';
    }
    if ($dbName === '') {
        $errors[] = 'Nome do banco de dados é obrigatório.';
    }
    if ($dbUser === '') {
        $errors[] = 'Usuário do banco de dados é obrigatório.';
    }
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
            // Conectar ao servidor (sem banco)
            $pdoServer = new PDO(
                buildMysqlDsn($dbHost, $dbPort, null, $charset),
                $dbUser,
                $dbPass,
                $options
            );
            
            // Criar banco se não existir
            $pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            // Conectar ao banco específico
            $pdo = new PDO(
                buildMysqlDsn($dbHost, $dbPort, $dbName, $charset),
                $dbUser,
                $dbPass,
                $options
            );

            // Verificar se já existem usuários
            try {
                $check = $pdo->query('SELECT COUNT(*) FROM usuarios');
                if ($check && (int) $check->fetchColumn() > 0) {
                    $errors[] = 'Já existem usuários cadastrados neste banco. Instalação bloqueada.';
                }
            } catch (Throwable $e) {
                // Tabela não existe ainda, é normal
            }

            if (!$errors) {
                $schemaPath = __DIR__ . '/database/schema.sql';
                if (!is_file($schemaPath)) {
                    throw new RuntimeException('Arquivo schema.sql não encontrado em database/schema.sql');
                }

                // Executar schema
                $pdo->beginTransaction();
                foreach (sqlStatementsFromFile($schemaPath) as $stmt) {
                    $pdo->exec($stmt);
                }

                // Criar usuário admin
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

                // Marcar como instalado
                file_put_contents($lockFile, 'installed_at=' . date('c'));
                file_put_contents($installedFlag, 'installed_at=' . date('c'));
                
                // Atualizar config/database.php com as credenciais usadas
                $newDsn = buildMysqlDsn($dbHost, $dbPort, $dbName, $charset);
                $configContent = '<?php' . "\n\n" .
                    'return [' . "\n" .
                    "    'dsn' => '" . str_replace("'", "\\'", $newDsn) . "'," . "\n" .
                    "    'username' => '" . str_replace("'", "\\'", $dbUser) . "'," . "\n" .
                    "    'password' => '" . str_replace("'", "\\'", $dbPass) . "'," . "\n" .
                    "    'options' => [\n" .
                    "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n" .
                    "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n" .
                    "        PDO::ATTR_TIMEOUT => 10,\n" .
                    "        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',\n" .
                    "    ],\n" .
                    "];\n";
                
                file_put_contents(__DIR__ . '/config/database.php', $configContent);
                
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

?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🔧 ShoesAZ - Instalação do Sistema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 30px;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #718096;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .db-info {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #4a5568;
        }

        .db-info strong {
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 6px;
        }

        .alert {
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-error {
            background: #fff5f5;
            border-left-color: #e53e3e;
            color: #742a2a;
        }

        .alert-error strong {
            color: #c53030;
        }

        .alert-success {
            background: #f0fff4;
            border-left-color: #38a169;
            color: #22543d;
        }

        .alert-success strong {
            color: #2f855a;
        }

        .alert ul {
            margin: 8px 0 0 20px;
        }

        .alert li {
            margin-top: 4px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
            text-align: center;
            font-size: 13px;
            color: #718096;
        }

        .success-message {
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-message h2 {
            color: #2f855a;
            margin-bottom: 10px;
        }

        .next-steps {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }

        .next-steps h3 {
            color: #22543d;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .next-steps ol {
            margin-left: 20px;
            color: #22543d;
            font-size: 13px;
        }

        .next-steps li {
            margin-bottom: 8px;
        }

        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Monaco', 'Courier New', monospace;
            color: #c53030;
            font-size: 13px;
        }

        @media (max-width: 640px) {
            .container {
                padding: 24px;
            }

            h1 {
                font-size: 24px;
            }

            .logo {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="header">
                <div class="logo">✅</div>
                <h1>Instalação Concluída!</h1>
                <p class="subtitle">Sistema configurado e pronto para uso</p>
            </div>

            <div class="alert alert-success">
                <strong>Sucesso!</strong> Usuário administrador criado com sucesso.
            </div>

            <div class="next-steps">
                <h3>Próximos Passos:</h3>
                <ol>
                    <li><strong>Acesse o sistema:</strong> <a href="/">Ir para o Dashboard</a></li>
                    <li><strong>Faça login</strong> com as credenciais do administrador</li>
                    <li><strong>Configure a empresa</strong> em Configurações → Empresa</li>
                    <li><strong>Crie usuários adicionais</strong> conforme necessário</li>
                </ol>
            </div>

            <div class="footer" style="margin-top: 40px;">
                <p>🎉 ShoesAZ v1.0 | Seu sistema de gestão de sapataria está pronto!</p>
            </div>

        <?php else: ?>
            <div class="header">
                <div class="logo">🔧</div>
                <h1>Instalação do ShoesAZ</h1>
                <p class="subtitle">Configure o sistema na primeira execução</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error" style="margin-top: 20px;">
                    <strong>⚠️ Erro na Instalação:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" style="margin-top: 30px;">
                <div style="background: #edf2f7; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3 style="color: #2d3748; margin-bottom: 15px; font-size: 16px;">📊 Banco de Dados</h3>
                    
                    <div class="form-group">
                        <label for="db_host">🖥️ Host do Banco</label>
                        <input
                            type="text"
                            id="db_host"
                            name="db_host"
                            value="<?php echo htmlspecialchars((string) ($_POST['db_host'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: localhost ou 192.168.1.100"
                            required>
                        <div class="form-hint">Endereço do servidor MySQL</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="db_port">🔌 Porta</label>
                            <input
                                type="number"
                                id="db_port"
                                name="db_port"
                                value="<?php echo htmlspecialchars((string) ($_POST['db_port'] ?? '3306'), ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="3306"
                                min="1"
                                max="65535"
                                required>
                            <div class="form-hint">Porta padrão: 3306</div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="db_name">📦 Nome do Banco</label>
                            <input
                                type="text"
                                id="db_name"
                                name="db_name"
                                value="<?php echo htmlspecialchars((string) ($_POST['db_name'] ?? 'shoesaz'), ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Ex: shoesaz"
                                required>
                            <div class="form-hint">Será criado automaticamente</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="db_user">👤 Usuário MySQL</label>
                        <input
                            type="text"
                            id="db_user"
                            name="db_user"
                            value="<?php echo htmlspecialchars((string) ($_POST['db_user'] ?? 'root'), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: root"
                            required>
                        <div class="form-hint">Usuário com permissão para criar bancos</div>
                    </div>

                    <div class="form-group">
                        <label for="db_pass">🔑 Senha MySQL</label>
                        <input
                            type="password"
                            id="db_pass"
                            name="db_pass"
                            placeholder="Deixe vazio se não houver senha"
                        >
                        <div class="form-hint">Senha do usuário MySQL (pode estar vazia)</div>
                    </div>
                </div>

                <div style="background: #f0fff4; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3 style="color: #22543d; margin-bottom: 15px; font-size: 16px;">👤 Administrador</h3>
                    
                    <div class="form-group">
                        <label for="admin_nome">Nome do Administrador</label>
                        <input
                            type="text"
                            id="admin_nome"
                            name="admin_nome"
                            value="<?php echo htmlspecialchars((string) ($_POST['admin_nome'] ?? 'Administrador'), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: João Silva"
                            required>
                        <div class="form-hint">Nome que será exibido no sistema</div>
                    </div>

                    <div class="form-group">
                        <label for="admin_login">Login do Administrador</label>
                        <input
                            type="text"
                            id="admin_login"
                            name="admin_login"
                            value="<?php echo htmlspecialchars((string) ($_POST['admin_login'] ?? 'admin'), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: admin"
                            required>
                        <div class="form-hint">Apenas letras e números</div>
                    </div>

                    <div class="form-group">
                        <label for="admin_senha">Senha do Administrador</label>
                        <input
                            type="password"
                            id="admin_senha"
                            name="admin_senha"
                            placeholder="Digite uma senha forte"
                            required>
                        <div class="form-hint">Letras, números e caracteres especiais</div>
                    </div>
                </div>

                <button type="submit">🚀 Instalar Agora</button>
            </form>

            <div class="footer">
                <p>💡 Dica: Você pode remover os arquivos <code>install.lock</code> e <code>.installed</code> para reinstalar o sistema</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
