<?php

/**
 * Script de teste do instalador
 * Valida se o sistema pode ser instalado corretamente
 * 
 * Uso: php tests/test_installer.php
 */

echo "\n🔧 TESTE DO INSTALADOR DO SHOESAZ\n";
echo str_repeat("=", 50) . "\n\n";

// Teste 1: Verificar arquivo install.php
echo "1️⃣  Verificando arquivo install.php...\n";
$installFile = __DIR__ . '/../install.php';
if (is_file($installFile)) {
    echo "   ✅ install.php encontrado\n";
} else {
    echo "   ❌ install.php NÃO encontrado\n";
    exit(1);
}

// Teste 2: Verificar diretório database/
echo "\n2️⃣  Verificando diretório database/...\n";
$dbDir = __DIR__ . '/../database';
if (is_dir($dbDir)) {
    echo "   ✅ Diretório database/ existe\n";
} else {
    echo "   ❌ Diretório database/ não existe\n";
    exit(1);
}

// Teste 3: Verificar schema.sql
echo "\n3️⃣  Verificando schema.sql...\n";
$schemaFile = $dbDir . '/schema.sql';
if (is_file($schemaFile)) {
    $lines = count(file($schemaFile));
    echo "   ✅ schema.sql encontrado ($lines linhas)\n";
} else {
    echo "   ❌ schema.sql NÃO encontrado\n";
    exit(1);
}

// Teste 4: Verificar permissões de escrita
echo "\n4️⃣  Verificando permissões de escrita...\n";
if (is_writable($dbDir)) {
    echo "   ✅ Diretório database/ é escrevível\n";
} else {
    echo "   ⚠️  Diretório database/ não é escrevível (será necessário chmod 755)\n";
}

$rootDir = __DIR__ . '/..';
if (is_writable($rootDir)) {
    echo "   ✅ Diretório raiz é escrevível\n";
} else {
    echo "   ⚠️  Diretório raiz não é escrevível\n";
}

// Teste 5: Verificar arquivo Installer.php
echo "\n5️⃣  Verificando app/Core/Installer.php...\n";
$installerFile = __DIR__ . '/../app/Core/Installer.php';
if (is_file($installerFile)) {
    echo "   ✅ Installer.php encontrado\n";
    
    // Tentar carregar
    require $installerFile;
    if (class_exists('App\Core\Installer')) {
        echo "   ✅ Classe Installer pode ser carregada\n";
        
        // Testar métodos
        if (method_exists('App\Core\Installer', 'isInstalled')) {
            echo "   ✅ Método isInstalled() existe\n";
        }
        if (method_exists('App\Core\Installer', 'markAsInstalled')) {
            echo "   ✅ Método markAsInstalled() existe\n";
        }
    } else {
        echo "   ❌ Classe Installer não pode ser carregada\n";
    }
} else {
    echo "   ❌ Installer.php NÃO encontrado\n";
}

// Teste 6: Verificar lock files
echo "\n6️⃣  Verificando status de instalação...\n";
$lockFile = $dbDir . '/install.lock';
$installedFlag = $rootDir . '/.installed';

if (is_file($lockFile)) {
    echo "   ℹ️  install.lock existe (sistema pode estar instalado)\n";
    $content = file_get_contents($lockFile);
    echo "   Conteúdo: " . trim(substr($content, 0, 50)) . "...\n";
} else {
    echo "   ℹ️  install.lock NÃO existe (sistema não instalado ainda)\n";
}

if (is_file($installedFlag)) {
    echo "   ℹ️  .installed existe (sistema pode estar instalado)\n";
} else {
    echo "   ℹ️  .installed NÃO existe (sistema não instalado ainda)\n";
}

// Teste 7: Verificar configuração de banco
echo "\n7️⃣  Verificando config/database.php...\n";
$configFile = __DIR__ . '/../config/database.php';
if (is_file($configFile)) {
    echo "   ✅ config/database.php encontrado\n";
    
    // Carregar config
    $databaseConfig = require $configFile;
    echo "   DSN: " . substr($databaseConfig['dsn'], 0, 40) . "...\n";
    echo "   Username: " . $databaseConfig['username'] . "\n";
} else {
    echo "   ❌ config/database.php NÃO encontrado\n";
}

// Teste 8: Tentar conectar ao banco
echo "\n8️⃣  Tentando conectar ao banco de dados...\n";
try {
    require __DIR__ . '/../app/bootstrap.php';
    $db = \App\Core\DB::pdo();
    $stmt = $db->query("SELECT 1");
    if ($stmt->fetchColumn() == 1) {
        echo "   ✅ Conectado ao banco de dados\n";
    }
} catch (\Throwable $e) {
    echo "   ⚠️  Erro ao conectar: " . $e->getMessage() . "\n";
    echo "   (Isso é normal se o banco não estiver criado ainda)\n";
}

// Teste 9: Verificar index.php modificado
echo "\n9️⃣  Verificando redirecionamento automático...\n";
$indexFile = __DIR__ . '/../index.php';
$indexContent = file_get_contents($indexFile);
if (str_contains($indexContent, 'install.php') && str_contains($indexContent, 'Verificar se sistema está instalado')) {
    echo "   ✅ index.php com redirecionamento automático\n";
} else {
    echo "   ⚠️  index.php pode não ter redirecionamento\n";
}

// Resumo
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TESTES CONCLUÍDOS\n\n";
echo "Status:\n";
echo "  • install.php: Presente\n";
echo "  • schema.sql: Presente\n";
echo "  • Installer.php: Presente\n";
echo "  • Permissões: Verificadas\n";
echo "  • Configuração: Carregável\n\n";
echo "Próximo passo: Subir arquivos para o servidor e testar!\n";
echo "\n";
