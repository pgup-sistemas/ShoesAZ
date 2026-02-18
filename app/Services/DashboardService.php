<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

final class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutos
    private const CACHE_KEY = 'dashboard_stats_';

    /**
     * Obtém estatísticas do dashboard com cache
     */
    public static function getStats(string $userRole = 'default'): array
    {
        $cacheKey = self::CACHE_KEY . $userRole;
        
        // Tentar usar APCu se disponível
        if (extension_loaded('apcu')) {
            $cached = apcu_fetch($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }

        $hoje = date('Y-m-d');
        $amanha = date('Y-m-d', strtotime('+1 day'));
        $db = DB::pdo();

        try {
            // Usar uma única query mais otimizada
            $stats = [
                'os_abertas' => 0,
                'os_atrasadas' => 0,
                'os_em_reparo' => 0,
                'os_aguardando_retirada' => 0,
                'orcamentos_pendentes' => 0,
                'clientes' => 0,
                'receitas_hoje' => 0.0,
                'contas_receber_clientes' => 0,
                'contas_receber_total' => 0.0,
                'inadimplentes' => 0,
                'valor_inadimplencia' => 0.0,
                'lucro_mes' => 0.0,
                'despesas_mes' => 0.0,
                'os_finalizadas_mes' => 0,
            ];

            // Query agregada para os stats principais
            $stmt = $db->query("
                SELECT 
                    (SELECT COUNT(*) FROM ordens_servico WHERE status NOT IN ('Entregue', 'Cancelado')) as os_abertas,
                    (SELECT COUNT(*) FROM ordens_servico WHERE status = 'Em reparo') as os_em_reparo,
                    (SELECT COUNT(*) FROM ordens_servico WHERE status = 'Aguardando retirada') as os_aguardando_retirada,
                    (SELECT COUNT(*) FROM orcamentos WHERE status = 'Aguardando') as orcamentos_pendentes,
                    (SELECT COUNT(*) FROM clientes) as clientes,
                    (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE data_pagamento = CURDATE() AND status = 'Pago') as receitas_hoje
            ");
            
            $row = $stmt->fetch();
            if ($row) {
                $stats['os_abertas'] = (int) ($row['os_abertas'] ?? 0);
                $stats['os_em_reparo'] = (int) ($row['os_em_reparo'] ?? 0);
                $stats['os_aguardando_retirada'] = (int) ($row['os_aguardando_retirada'] ?? 0);
                $stats['orcamentos_pendentes'] = (int) ($row['orcamentos_pendentes'] ?? 0);
                $stats['clientes'] = (int) ($row['clientes'] ?? 0);
                $stats['receitas_hoje'] = (float) ($row['receitas_hoje'] ?? 0);
            }

            // OS atrasadas
            $stmt = $db->query("
                SELECT COUNT(*) FROM ordens_servico 
                WHERE prazo_entrega < CURDATE() 
                AND status NOT IN ('Entregue', 'Cancelado')
            ");
            $stats['os_atrasadas'] = (int) ($stmt->fetchColumn() ?? 0);

            // Contas a receber
            $stmt = $db->query("
                SELECT COUNT(DISTINCT os.cliente_id) as clientes, COALESCE(SUM(p.valor), 0) as total
                FROM pagamentos p
                JOIN ordens_servico os ON p.os_id = os.id
                WHERE p.status = 'Pendente'
            ");
            $row = $stmt->fetch();
            $stats['contas_receber_clientes'] = (int) ($row['clientes'] ?? 0);
            $stats['contas_receber_total'] = (float) ($row['total'] ?? 0);

            // Inadimplentes
            $stmt = $db->query("
                SELECT COUNT(DISTINCT os.cliente_id) as clientes, COALESCE(SUM(p.valor), 0) as total
                FROM pagamentos p
                JOIN ordens_servico os ON p.os_id = os.id
                WHERE p.status = 'Pendente'
                AND p.vencimento IS NOT NULL
                AND p.vencimento < CURDATE()
            ");
            $row = $stmt->fetch();
            $stats['inadimplentes'] = (int) ($row['clientes'] ?? 0);
            $stats['valor_inadimplencia'] = (float) ($row['total'] ?? 0);

            // Lucro do mês
            $stmt = $db->query("
                SELECT COALESCE(SUM(p.valor), 0) - COALESCE((SELECT SUM(valor) FROM despesas WHERE MONTH(data) = MONTH(CURDATE()) AND YEAR(data) = YEAR(CURDATE())), 0) as lucro
                FROM pagamentos p
                WHERE MONTH(p.data_pagamento) = MONTH(CURDATE())
                AND YEAR(p.data_pagamento) = YEAR(CURDATE())
                AND p.status = 'Pago'
            ");
            $stats['lucro_mes'] = (float) ($stmt->fetchColumn() ?? 0);

            // Despesas do mês
            $stmt = $db->query("
                SELECT COALESCE(SUM(valor), 0) FROM despesas 
                WHERE MONTH(data) = MONTH(CURDATE()) 
                AND YEAR(data) = YEAR(CURDATE())
            ");
            $stats['despesas_mes'] = (float) ($stmt->fetchColumn() ?? 0);

            // OS finalizadas no mês
            $stmt = $db->query("
                SELECT COUNT(*) FROM ordens_servico 
                WHERE status = 'Entregue'
                AND MONTH(data_entrega) = MONTH(CURDATE())
                AND YEAR(data_entrega) = YEAR(CURDATE())
            ");
            $stats['os_finalizadas_mes'] = (int) ($stmt->fetchColumn() ?? 0);

            // Cache por 5 minutos
            if (extension_loaded('apcu')) {
                apcu_store($cacheKey, $stats, self::CACHE_TTL);
            }

            return $stats;
        } catch (\Throwable $e) {
            error_log('DashboardService Error: ' . $e->getMessage());
            return $stats;
        }
    }

    /**
     * Obtém OS atrasadas
     */
    public static function getOsAtrasadas(int $limit = 3): array
    {
        try {
            $db = DB::pdo();
            $stmt = $db->prepare("
                SELECT os.*, c.nome as cliente_nome 
                FROM ordens_servico os 
                JOIN clientes c ON os.cliente_id = c.id 
                WHERE os.prazo_entrega < CURDATE() 
                AND os.status NOT IN ('Entregue', 'Cancelado') 
                ORDER BY os.prazo_entrega ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('DashboardService getOsAtrasadas Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém OS para hoje
     */
    public static function getOsHoje(int $limit = 3): array
    {
        try {
            $db = DB::pdo();
            $stmt = $db->prepare("
                SELECT os.*, c.nome as cliente_nome 
                FROM ordens_servico os 
                JOIN clientes c ON os.cliente_id = c.id 
                WHERE os.prazo_entrega = CURDATE() 
                ORDER BY os.status ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('DashboardService getOsHoje Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém OS para amanhã
     */
    public static function getOsAmanha(int $limit = 3): array
    {
        try {
            $db = DB::pdo();
            $amanha = date('Y-m-d', strtotime('+1 day'));
            $stmt = $db->prepare("
                SELECT os.*, c.nome as cliente_nome 
                FROM ordens_servico os 
                JOIN clientes c ON os.cliente_id = c.id 
                WHERE os.prazo_entrega = ? 
                AND os.status NOT IN ('Entregue', 'Cancelado') 
                ORDER BY os.status ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $amanha);
            $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('DashboardService getOsAmanha Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém caixa de hoje
     */
    public static function getCaixaHoje(): ?array
    {
        try {
            $db = DB::pdo();
            $stmt = $db->prepare("SELECT * FROM caixa WHERE data = CURDATE() AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $caixa = $stmt->fetch();
            
            if (!$caixa) {
                $stmt = $db->prepare("SELECT * FROM caixa WHERE data = CURDATE() ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $caixa = $stmt->fetch();
            }
            
            return $caixa ?: null;
        } catch (\Throwable $e) {
            error_log('DashboardService getCaixaHoje Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpa cache do dashboard
     */
    public static function clearCache(): void
    {
        if (extension_loaded('apcu')) {
            apcu_delete('dashboard_stats_default');
            apcu_delete('dashboard_stats_Administrador');
            apcu_delete('dashboard_stats_Gerente');
            apcu_delete('dashboard_stats_Atendente');
            apcu_delete('dashboard_stats_Sapateiro');
        }
    }
}
