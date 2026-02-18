#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script de teste das rotas do sistema
 * Uso: php tests/route_test.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\DB;

echo "\n🔍 TESTE DE ROTAS E SISTEMA\n";
echo str_repeat("=", 50) . "\n\n";

// Teste 1: Conexão com BD
echo "1️⃣  Testando Conexão com Banco de Dados...\n";
try {
    $db = DB::pdo();
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetchColumn();
    echo "   ✅ Conectado ao banco de dados\n";
} catch (\Throwable $e) {
    echo "   ❌ Erro ao conectar: " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 2: Tabelas
echo "\n2️⃣  Verificando Tabelas Essenciais...\n";
$tables = ['usuarios', 'clientes', 'ordens_servico', 'pagamentos', 'caixa', 'orcamentos'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "   ✅ Tabela '$table': $count registros\n";
    } catch (\Throwable $e) {
        echo "   ❌ Tabela '$table' não encontrada\n";
    }
}

// Teste 3: DashboardService
echo "\n3️⃣  Testando DashboardService...\n";
try {
    $stats = \App\Services\DashboardService::getStats();
    echo "   ✅ Stats carregadas: " . count($stats) . " métricas\n";
    echo "   - OS Abertas: " . $stats['os_abertas'] . "\n";
    echo "   - Clientes: " . $stats['clientes'] . "\n";
} catch (\Throwable $e) {
    echo "   ❌ Erro ao carregar stats: " . $e->getMessage() . "\n";
}

// Teste 4: APCu (opcional)
echo "\n4️⃣  Verificando Cache APCu...\n";
if (extension_loaded('apcu')) {
    echo "   ✅ APCu instalado e habilitado\n";
    echo "   - Versão: " . phpversion('apcu') . "\n";
    $cache_enabled = ini_get('apc.enabled');
    echo "   - Habilitado: " . ($cache_enabled ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "   ⚠️  APCu não instalado (cache desabilitado)\n";
}

// Teste 5: Limites PHP
echo "\n5️⃣  Verificando Limites PHP...\n";
echo "   - memory_limit: " . ini_get('memory_limit') . "\n";
echo "   - max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "   - upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";

// Teste 6: Permissões de Arquivo
echo "\n6️⃣  Verificando Permissões de Arquivo...\n";
$dirs = [
    __DIR__ . '/../backups',
    __DIR__ . '/../public/uploads',
    __DIR__ . '/../app',
];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "   ✅ Escrita em $dir: OK\n";
    } else {
        echo "   ⚠️  Sem escrita em $dir\n";
    }
}

// Teste 7: Logs
echo "\n7️⃣  Verificando Arquivo de Logs...\n";
$log_file = ini_get('error_log');
if ($log_file && is_writable($log_file)) {
    echo "   ✅ Error log: $log_file (OK)\n";
} else {
    echo "   ⚠️  Error log não configurado ou não escrevível\n";
}

// Teste 8: Performance Dashboard
echo "\n8️⃣  Teste de Performance (Dashboard)...\n";
$start = microtime(true);
try {
    $osAtrasadas = \App\Services\DashboardService::getOsAtrasadas(3);
    $osHoje = \App\Services\DashboardService::getOsHoje(3);
    $osAmanha = \App\Services\DashboardService::getOsAmanha(3);
    $caixa = \App\Services\DashboardService::getCaixaHoje();
} catch (\Throwable $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}
$time = (microtime(true) - $start) * 1000;
echo "   ✅ Dashboard carregado em " . number_format($time, 2) . "ms\n";

if ($time < 500) {
    echo "   ⚡ Excelente! (<500ms)\n";
} elseif ($time < 1000) {
    echo "   👍 Bom (500-1000ms)\n";
} else {
    echo "   🐢 Lento (>1000ms) - Verificar índices do BD\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Testes Concluídos!\n\n";
