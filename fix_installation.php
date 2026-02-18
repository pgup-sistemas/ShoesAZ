<?php
/**
 * Script para criar lock files de instalação
 * Execute uma única vez e depois delete o arquivo
 * 
 * Acesso: https://seu_site.com/fix_installation.php
 */

// ============================================================================
// SEGURANÇA - Altere esta senha antes de usar!
// ============================================================================
$SENHA_SECRETA = 'ShoesAZ2026';  // ⚠️ MUDE ISSO!

// ============================================================================
// VERIFICAR SENHA
// ============================================================================
$senha_fornecida = trim($_GET['pass'] ?? $_POST['pass'] ?? '');
$acao = $_GET['action'] ?? $_POST['action'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Criar Lock Files - ShoesAZ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        h1 {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-info {
            background: #e6f3ff;
            border-left-color: #0066cc;
            color: #003d99;
        }
        .alert-success {
            background: #f0fff4;
            border-left-color: #38a169;
            color: #22543d;
        }
        .alert-error {
            background: #fff5f5;
            border-left-color: #e53e3e;
            color: #742a2a;
        }
        .alert-warning {
            background: #fffaf0;
            border-left-color: #f6ad55;
            color: #7c2d12;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: monospace;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 0;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        button:active {
            transform: translateY(0);
        }
        .info-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4a5568;
            line-height: 1.6;
        }
        .info-box strong {
            color: #2d3748;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            font-size: 14px;
        }
        .status.ok .icon {
            color: #38a169;
        }
        .status.error .icon {
            color: #e53e3e;
        }
        .status-icon {
            font-size: 20px;
            display: inline-block;
            width: 24px;
        }
        .files-list {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            margin: 15px 0;
        }
        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🔧</div>
            <h1>Criar Lock Files</h1>
            <p class="subtitle">Marcar instalação como concluída</p>
        </div>

        <?php
        // Se senha não foi fornecida, mostrar formulário
        if (empty($senha_fornecida)):
        ?>
            <div class="alert alert-info">
                <strong>⚠️ Segurança:</strong> Digite a senha para prosseguir
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="pass">Senha de Acesso:</label>
                    <input 
                        type="password" 
                        id="pass" 
                        name="pass" 
                        placeholder="Digite a senha" 
                        autofocus
                        required
                    >
                </div>
                <input type="hidden" name="action" value="verify">
                <button type="submit">🔓 Verificar Acesso</button>
            </form>

            <div class="info-box" style="margin-top: 30px;">
                <strong>ℹ️ Sobre este script:</strong><br><br>
                Este script cria os arquivos de bloqueio de instalação:<br>
                • <code>database/install.lock</code><br>
                • <code>.installed</code><br><br>
                Estes arquivos indicam ao sistema que a instalação já foi concluída e não deve tentar instalar novamente.
            </div>

        <?php
        // Verificar senha
        elseif ($acao === 'verify'):
            if ($senha_fornecida !== $SENHA_SECRETA):
        ?>
                <div class="alert alert-error">
                    <strong>❌ Erro:</strong> Senha incorreta!
                </div>
                <form method="post" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="pass">Senha de Acesso:</label>
                        <input 
                            type="password" 
                            id="pass" 
                            name="pass" 
                            placeholder="Digite a senha" 
                            autofocus
                            required
                        >
                    </div>
                    <input type="hidden" name="action" value="verify">
                    <button type="submit">🔓 Tentar Novamente</button>
                </form>

            <?php
            else:
                // Senha correta, mostrar confirmação
            ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Confirme a ação:</strong><br>
                    Você está prestes a criar os arquivos de lock de instalação.
                </div>

                <div class="info-box">
                    <strong>O que será criado:</strong><br><br>
                    📁 <code>database/install.lock</code><br>
                    📁 <code>.installed</code><br><br>
                    <strong>Resultado:</strong><br>
                    O sistema não tentará mais redirecionar para /install.php
                </div>

                <form method="post">
                    <input type="hidden" name="pass" value="<?php echo htmlspecialchars($senha_fornecida); ?>">
                    <input type="hidden" name="action" value="create">
                    <button type="submit">✅ Criar Lock Files</button>
                </form>

                <form method="post" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" style="background: #a0aec0; background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);">❌ Cancelar</button>
                </form>

            <?php
            endif;
        // Executar criação dos lock files
        elseif ($acao === 'create' && $senha_fornecida === $SENHA_SECRETA):
            $dir = __DIR__;
            $lockFile = $dir . '/database/install.lock';
            $installedFlag = $dir . '/.installed';
            $timestamp = date('c');
            $content = 'installed_at=' . $timestamp;

            $erro = false;
            $mensagens = [];

            // Criar database/install.lock
            if (@file_put_contents($lockFile, $content) === false) {
                $erro = true;
                $mensagens[] = "❌ Erro ao criar <code>database/install.lock</code>";
            } else {
                $mensagens[] = "✅ Arquivo <code>database/install.lock</code> criado";
            }

            // Criar .installed
            if (@file_put_contents($installedFlag, $content) === false) {
                $erro = true;
                $mensagens[] = "❌ Erro ao criar <code>.installed</code>";
            } else {
                $mensagens[] = "✅ Arquivo <code>.installed</code> criado";
            }

            // Mostrar resultado
            if (!$erro):
            ?>
                <div class="alert alert-success">
                    <strong>🎉 Sucesso!</strong> Lock files criados com sucesso!
                </div>

                <div class="status">
                    <span class="status-icon">📅</span>
                    <span><strong>Timestamp:</strong> <?php echo htmlspecialchars($timestamp); ?></span>
                </div>

                <div class="files-list">
                    Arquivos criados:<br><br>
                    ✓ database/install.lock<br>
                    ✓ .installed
                </div>

                <div class="alert alert-info">
                    <strong>⚠️ Próximos Passos:</strong><br><br>
                    1. Acesse seu site normalmente<br>
                    2. O instalador não deve mais aparecer<br>
                    3. <strong>Delete este arquivo (fix_installation.php)</strong> do servidor por segurança
                </div>

                <button onclick="location.href='/'">🏠 Ir para Home</button>

            <?php
            else:
            ?>
                <div class="alert alert-error">
                    <strong>❌ Erro ao criar arquivos!</strong>
                </div>

                <div style="background: #f7fafc; padding: 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                    <?php foreach ($mensagens as $msg): ?>
                        <div style="margin-bottom: 8px;">
                            <?php echo $msg; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-warning">
                    <strong>⚠️ Possíveis causas:</strong><br><br>
                    • Permissões insuficientes nos diretórios<br>
                    • Disco cheio<br>
                    • Diretório <code>database/</code> não existe<br><br>
                    <strong>Solução:</strong> Contate seu host ou tente via SSH
                </div>

                <form method="post">
                    <input type="hidden" name="pass" value="<?php echo htmlspecialchars($senha_fornecida); ?>">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit">🔄 Tentar Novamente</button>
                </form>

            <?php
            endif;
        // Cancelado
        elseif ($acao === 'cancel'):
        ?>
            <div class="alert alert-info">
                <strong>⚠️ Operação cancelada</strong>
            </div>
            <button onclick="location.reload()">🔄 Voltar</button>

        <?php
        endif;
        ?>

        <div class="info-box" style="margin-top: 30px; border-left-color: #e53e3e;">
            <strong style="color: #e53e3e;">🔒 SEGURANÇA:</strong><br><br>
            ⚠️ <strong>DEPOIS DE USAR ESTE SCRIPT:</strong><br>
            1. Delete este arquivo via FTP<br>
            2. Não deixe este arquivo online
        </div>
    </div>
</body>
</html>
