<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Authorization;
use App\Core\DB;
use App\Core\Request;
use App\Core\View;

final class RelatorioController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        View::render('relatorios/index', [
            'pageTitle' => 'Relatórios',
        ]);
    }

    public function lucro(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $dataInicio = (string) Request::input('data_inicio', date('Y-m-01'));
        $dataFim = (string) Request::input('data_fim', date('Y-m-t'));

        $db = DB::pdo();

        // Receitas (pagamentos confirmados)
        $stmt = $db->prepare("SELECT COALESCE(SUM(valor), 0) FROM pagamentos 
                             WHERE data_pagamento BETWEEN :inicio AND :fim AND status = 'Pago'");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $receitas = (float) $stmt->fetchColumn();

        // Despesas pagas
        $stmt = $db->prepare("SELECT COALESCE(SUM(valor), 0) FROM despesas 
                             WHERE data_pagamento BETWEEN :inicio AND :fim");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $despesas = (float) $stmt->fetchColumn();

        // Detalhamento por forma de pagamento
        $stmt = $db->prepare("SELECT forma_pagamento, COALESCE(SUM(valor), 0) as total 
                             FROM pagamentos 
                             WHERE data_pagamento BETWEEN :inicio AND :fim AND status = 'Pago'
                             GROUP BY forma_pagamento");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $receitasPorForma = $stmt->fetchAll();

        // Detalhamento de despesas por categoria
        $stmt = $db->prepare("SELECT categoria, COALESCE(SUM(valor), 0) as total 
                             FROM despesas 
                             WHERE data_pagamento BETWEEN :inicio AND :fim
                             GROUP BY categoria");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $despesasPorCategoria = $stmt->fetchAll();

        View::render('relatorios/lucro', [
            'pageTitle' => 'Relatório de Lucro/Prejuízo',
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'lucro' => $receitas - $despesas,
            'receitasPorForma' => $receitasPorForma,
            'despesasPorCategoria' => $despesasPorCategoria,
        ]);
    }

    public function os(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $dataInicio = (string) Request::input('data_inicio', date('Y-m-01'));
        $dataFim = (string) Request::input('data_fim', date('Y-m-t'));

        $db = DB::pdo();

        // OS criadas no período
        $stmt = $db->prepare("SELECT os.*, c.nome as cliente_nome, u.nome as sapateiro_nome
                             FROM ordens_servico os
                             JOIN clientes c ON os.cliente_id = c.id
                             LEFT JOIN usuarios u ON os.sapateiro_id = u.id
                             WHERE os.data_entrada BETWEEN :inicio AND :fim
                             ORDER BY os.data_entrada DESC");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $ordens = $stmt->fetchAll();

        // Por status
        $stmt = $db->prepare("SELECT status, COUNT(*) as total 
                             FROM ordens_servico 
                             WHERE data_entrada BETWEEN :inicio AND :fim
                             GROUP BY status");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $porStatus = $stmt->fetchAll();

        // Valor total
        $valorTotal = array_sum(array_column($ordens, 'valor_total'));

        View::render('relatorios/os', [
            'pageTitle' => 'Relatório de OS',
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'ordens' => $ordens,
            'porStatus' => $porStatus,
            'valorTotal' => $valorTotal,
        ]);
    }

    public function clientes(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $db = DB::pdo();

        // Clientes com mais OS
        $stmt = $db->query("SELECT c.id, c.nome, c.telefone, COUNT(os.id) as total_os, SUM(os.valor_total) as valor_total
                           FROM clientes c
                           JOIN ordens_servico os ON c.id = os.cliente_id
                           GROUP BY c.id, c.nome, c.telefone
                           ORDER BY total_os DESC
                           LIMIT 20");
        $clientesTop = $stmt->fetchAll();

        // Clientes sem OS recente (mais de 90 dias)
        $stmt = $db->query("SELECT c.id, c.nome, c.telefone, MAX(os.data_entrada) as ultima_os
                           FROM clientes c
                           LEFT JOIN ordens_servico os ON c.id = os.cliente_id
                           GROUP BY c.id, c.nome, c.telefone
                           HAVING ultima_os IS NULL OR ultima_os < DATE_SUB(NOW(), INTERVAL 90 DAY)
                           ORDER BY ultima_os ASC
                           LIMIT 20");
        $clientesInativos = $stmt->fetchAll();

        View::render('relatorios/clientes', [
            'pageTitle' => 'Relatório de Clientes',
            'clientesTop' => $clientesTop,
            'clientesInativos' => $clientesInativos,
        ]);
    }

    public function exportarLucroCsv(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $dataInicio = (string) Request::input('data_inicio', date('Y-m-01'));
        $dataFim = (string) Request::input('data_fim', date('Y-m-t'));

        $db = DB::pdo();

        // Receitas detalhadas
        $stmt = $db->prepare("SELECT p.data_pagamento, p.valor, p.forma_pagamento, os.numero as os_numero, c.nome as cliente_nome
                             FROM pagamentos p
                             JOIN ordens_servico os ON p.os_id = os.id
                             JOIN clientes c ON os.cliente_id = c.id
                             WHERE p.data_pagamento BETWEEN :inicio AND :fim AND p.status = 'Pago'
                             ORDER BY p.data_pagamento");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $receitas = $stmt->fetchAll();

        // Despesas detalhadas
        $stmt = $db->prepare("SELECT data_pagamento, descricao, categoria, valor
                             FROM despesas
                             WHERE data_pagamento BETWEEN :inicio AND :fim
                             ORDER BY data_pagamento");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $despesas = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_lucro_' . $dataInicio . '_a_' . $dataFim . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, ['RELATÓRIO DE LUCRO/PREJUÍZO']);
        fputcsv($output, ['Período:', $dataInicio, 'a', $dataFim]);
        fputcsv($output, []);

        fputcsv($output, ['RECEITAS']);
        fputcsv($output, ['Data', 'OS', 'Cliente', 'Forma Pagamento', 'Valor']);
        $totalReceitas = 0;
        foreach ($receitas as $r) {
            fputcsv($output, [
                $r['data_pagamento'],
                $r['os_numero'],
                $r['cliente_nome'],
                $r['forma_pagamento'],
                number_format((float) $r['valor'], 2, ',', '.')
            ]);
            $totalReceitas += (float) $r['valor'];
        }
        fputcsv($output, ['', '', '', 'TOTAL RECEITAS:', number_format($totalReceitas, 2, ',', '.')]);
        fputcsv($output, []);

        fputcsv($output, ['DESPESAS']);
        fputcsv($output, ['Data', 'Descrição', 'Categoria', 'Valor']);
        $totalDespesas = 0;
        foreach ($despesas as $d) {
            fputcsv($output, [
                $d['data_pagamento'],
                $d['descricao'],
                $d['categoria'],
                number_format((float) $d['valor'], 2, ',', '.')
            ]);
            $totalDespesas += (float) $d['valor'];
        }
        fputcsv($output, ['', '', 'TOTAL DESPESAS:', number_format($totalDespesas, 2, ',', '.')]);
        fputcsv($output, []);

        $lucro = $totalReceitas - $totalDespesas;
        fputcsv($output, ['', '', 'LUCRO LÍQUIDO:', number_format($lucro, 2, ',', '.')]);

        fclose($output);
        exit;
    }

    public function exportarOsCsv(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $dataInicio = (string) Request::input('data_inicio', date('Y-m-01'));
        $dataFim = (string) Request::input('data_fim', date('Y-m-t'));

        $db = DB::pdo();

        $stmt = $db->prepare("SELECT os.*, c.nome as cliente_nome, u.nome as sapateiro_nome
                             FROM ordens_servico os
                             JOIN clientes c ON os.cliente_id = c.id
                             LEFT JOIN usuarios u ON os.sapateiro_id = u.id
                             WHERE os.data_entrada BETWEEN :inicio AND :fim
                             ORDER BY os.data_entrada DESC");
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        $ordens = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_os_' . $dataInicio . '_a_' . $dataFim . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['RELATÓRIO DE ORDENS DE SERVIÇO']);
        fputcsv($output, ['Período:', $dataInicio, 'a', $dataFim]);
        fputcsv($output, []);

        fputcsv($output, ['Número', 'Data Entrada', 'Prazo Entrega', 'Cliente', 'Sapateiro', 'Status', 'Valor Total']);
        
        foreach ($ordens as $os) {
            fputcsv($output, [
                $os['numero'],
                $os['data_entrada'],
                $os['prazo_entrega'],
                $os['cliente_nome'],
                $os['sapateiro_nome'] ?? 'Não atribuído',
                $os['status'],
                number_format((float) $os['valor_total'], 2, ',', '.')
            ]);
        }

        $total = array_sum(array_column($ordens, 'valor_total'));
        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', '', 'TOTAL:', number_format($total, 2, ',', '.')]);

        fclose($output);
        exit;
    }
}
