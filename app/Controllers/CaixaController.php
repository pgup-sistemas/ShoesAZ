<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditoriaService;

final class CaixaController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Caixa');

        $data = (string) Request::input('data', date('Y-m-d'));

        // Preferir o caixa ABERTO mais recente do dia; se não existir, mostrar o último caixa do dia
        $stmt = DB::pdo()->prepare(
            "SELECT * FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['data' => $data]);
        $caixa = $stmt->fetch();

        if (!$caixa) {
            $stmt = DB::pdo()->prepare('SELECT * FROM caixa WHERE data = :data ORDER BY id DESC LIMIT 1');
            $stmt->execute(['data' => $data]);
            $caixa = $stmt->fetch();
        }

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE data_pagamento = :data AND status = :status');
        $stmt->execute(['data' => $data, 'status' => 'Pago']);
        $totalReceitasDia = (float) $stmt->fetchColumn();

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE data_pagamento = :data');
        $stmt->execute(['data' => $data]);
        $totalDespesasDia = (float) $stmt->fetchColumn();

        $movimentacoes = [];
        if (is_array($caixa)) {
            $stmt = DB::pdo()->prepare(
                'SELECT m.*, u.nome as created_by_nome
                 FROM caixa_movimentacoes m
                 LEFT JOIN usuarios u ON m.created_by = u.id
                 WHERE m.caixa_id = :caixa_id
                 ORDER BY m.id ASC'
            );
            $stmt->execute(['caixa_id' => (int) $caixa['id']]);
            $movimentacoes = $stmt->fetchAll();
        }

        // Buscar histórico de caixas
        $stmt = DB::pdo()->query('SELECT * FROM caixa ORDER BY data DESC LIMIT 30');
        $historico = $stmt->fetchAll();

        // Buscar receitas do dia (pagamentos)
        $stmt = DB::pdo()->prepare(
            'SELECT p.*, os.numero as os_numero, c.nome as cliente_nome 
             FROM pagamentos p 
             JOIN ordens_servico os ON p.os_id = os.id 
             JOIN clientes c ON os.cliente_id = c.id 
             WHERE p.data_pagamento = :data AND p.status = :status'
        );
        $stmt->execute(['data' => $data, 'status' => 'Pago']);
        $receitasDoDia = $stmt->fetchAll();

        // Buscar despesas do dia
        $stmt = DB::pdo()->prepare(
            'SELECT d.*, u.nome as created_by_nome 
             FROM despesas d 
             LEFT JOIN usuarios u ON d.created_by = u.id 
             WHERE d.data_pagamento = :data'
        );
        $stmt->execute(['data' => $data]);
        $despesasDoDia = $stmt->fetchAll();

        View::render('caixa/index', [
            'pageTitle' => 'Caixa - ' . date('d/m/Y', strtotime($data)),
            'caixa' => $caixa,
            'data' => $data,
            'historico' => $historico,
            'receitasDoDia' => $receitasDoDia,
            'despesasDoDia' => $despesasDoDia,
            'movimentacoes' => $movimentacoes,
        ]);
    }

    public function abrir(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $hoje = date('Y-m-d');

        // Só bloqueia se já existir um caixa ABERTO hoje (permite abrir novamente após fechar)
        $stmt = DB::pdo()->prepare("SELECT id FROM caixa WHERE data = :data AND status = 'Aberto' LIMIT 1");
        $stmt->execute(['data' => $hoje]);
        if ($stmt->fetch()) {
            Flash::add('warning', 'Já existe um caixa aberto hoje.');
            Response::redirect('/caixa');
        }

        $saldoInicial = (float) Request::input('saldo_inicial', 0);
        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO caixa (data, saldo_inicial, saldo_esperado, responsavel_abertura, data_abertura, status) 
             VALUES (:data, :saldo_inicial, :saldo_esperado, :responsavel, NOW(), :status)'
        );
        $stmt->execute([
            'data' => $hoje,
            'saldo_inicial' => $saldoInicial,
            'saldo_esperado' => $saldoInicial,
            'responsavel' => $user['id'] ?? null,
            'status' => 'Aberto',
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO caixa_movimentacoes (caixa_id, tipo, valor, motivo, meta_json, created_by) VALUES (:caixa_id, :tipo, :valor, :motivo, :meta_json, :created_by)'
        );
        $stmt->execute([
            'caixa_id' => $id,
            'tipo' => 'Abertura',
            'valor' => $saldoInicial,
            'motivo' => null,
            'meta_json' => null,
            'created_by' => $user['id'] ?? null,
        ]);

        AuditoriaService::log(Auth::user(), 'caixa_aberto', 'caixa', $id, null, [
            'saldo_inicial' => $saldoInicial,
            'data' => $hoje,
        ]);

        Flash::add('success', 'Caixa aberto com sucesso.');
        Response::redirect('/caixa');
    }

    public function fechar(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $saldoReal = (float) Request::input('saldo_real', 0);
        $observacoes = trim((string) Request::input('observacoes', ''));

        $stmt = DB::pdo()->prepare('SELECT * FROM caixa WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Caixa não encontrado.');
            Response::redirect('/caixa');
        }

        if ((string) $before['status'] === 'Fechado') {
            Flash::add('warning', 'Caixa já está fechado.');
            Response::redirect('/caixa');
        }

        $saldoEsperado = (float) $before['saldo_esperado'];
        $diferenca = $saldoReal - $saldoEsperado;
        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'UPDATE caixa SET saldo_real = :saldo_real, diferenca = :diferenca, observacoes = :observacoes, responsavel_fechamento = :responsavel, data_fechamento = NOW(), status = :status WHERE id = :id'
        );
        $stmt->execute([
            'saldo_real' => $saldoReal,
            'diferenca' => $diferenca,
            'observacoes' => $observacoes,
            'responsavel' => $user['id'] ?? null,
            'status' => 'Fechado',
            'id' => $id,
        ]);

        $stmt = DB::pdo()->prepare(
            'INSERT INTO caixa_movimentacoes (caixa_id, tipo, valor, motivo, meta_json, created_by) VALUES (:caixa_id, :tipo, :valor, :motivo, :meta_json, :created_by)'
        );
        $stmt->execute([
            'caixa_id' => $id,
            'tipo' => 'Fechamento',
            'valor' => $saldoReal,
            'motivo' => $observacoes !== '' ? $observacoes : null,
            'meta_json' => json_encode([
                'saldo_esperado' => $saldoEsperado,
                'diferenca' => $diferenca,
            ], JSON_UNESCAPED_UNICODE),
            'created_by' => $user['id'] ?? null,
        ]);

        AuditoriaService::log(Auth::user(), 'caixa_fechado', 'caixa', $id, $before, [
            'saldo_real' => $saldoReal,
            'diferenca' => $diferenca,
        ]);

        Flash::add('success', 'Caixa fechado. Diferença: R$ ' . number_format($diferenca, 2, ',', '.'));
        Response::redirect('/caixa');
    }

    public function retirada(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $valor = (float) Request::input('valor', 0);
        $motivo = trim((string) Request::input('motivo', ''));

        if ($valor <= 0) {
            Flash::add('error', 'Valor deve ser maior que zero.');
            Response::redirect('/caixa');
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM caixa WHERE id = :id AND status = :status');
        $stmt->execute(['id' => $id, 'status' => 'Aberto']);
        $caixa = $stmt->fetch();

        if (!$caixa) {
            Flash::add('error', 'Caixa não encontrado ou já fechado.');
            Response::redirect('/caixa');
        }

        $saldoDisponivel = (float) ($caixa['saldo_esperado'] ?? 0);
        if ($valor > $saldoDisponivel) {
            Flash::add('error', 'Retirada maior que o saldo disponível no caixa. Saldo disponível: R$ ' . number_format($saldoDisponivel, 2, ',', '.'));
            Response::redirect('/caixa');
        }

        $stmt = DB::pdo()->prepare(
            'UPDATE caixa SET retiradas = retiradas + :valor_retirada, saldo_esperado = saldo_esperado - :valor_saldo, observacoes = CONCAT(observacoes, :motivo) WHERE id = :id'
        );
        $stmt->execute([
            'valor_retirada' => $valor,
            'valor_saldo' => $valor,
            'motivo' => "\nRetirada: R$ " . number_format($valor, 2, ',', '.') . " - " . $motivo,
            'id' => $id,
        ]);

        $user = Auth::user();
        $stmt = DB::pdo()->prepare(
            'INSERT INTO caixa_movimentacoes (caixa_id, tipo, valor, motivo, meta_json, created_by) VALUES (:caixa_id, :tipo, :valor, :motivo, :meta_json, :created_by)'
        );
        $stmt->execute([
            'caixa_id' => $id,
            'tipo' => 'Retirada',
            'valor' => $valor,
            'motivo' => $motivo,
            'meta_json' => null,
            'created_by' => $user['id'] ?? null,
        ]);

        AuditoriaService::log(Auth::user(), 'caixa_retirada', 'caixa', $id, $caixa, [
            'valor' => $valor,
            'motivo' => $motivo,
        ]);

        Flash::add('success', 'Retirada registrada.');
        Response::redirect('/caixa');
    }

    public function importarPagamentos(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $data = date('Y-m-d');

        $stmt = DB::pdo()->prepare("SELECT * FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['data' => $data]);
        $caixa = $stmt->fetch();

        if (!$caixa) {
            Flash::add('error', 'Abra o caixa antes de importar pagamentos.');
            Response::redirect('/caixa');
        }

        $caixaId = (int) $caixa['id'];

        $stmt = DB::pdo()->prepare(
            "SELECT id, valor
             FROM pagamentos
             WHERE status = 'Pago'
               AND data_pagamento = :data
               AND (caixa_id IS NULL OR caixa_id = 0)"
        );
        $stmt->execute(['data' => $data]);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            Flash::add('info', 'Nenhum pagamento para importar.');
            Response::redirect('/caixa');
        }

        $user = Auth::user();
        $qtde = 0;
        $total = 0.0;

        try {
            DB::pdo()->beginTransaction();

            foreach ($rows as $r) {
                $pagamentoId = (int) ($r['id'] ?? 0);
                $valor = (float) ($r['valor'] ?? 0);
                if ($pagamentoId <= 0 || $valor <= 0) {
                    continue;
                }

                $stmt = DB::pdo()->prepare('UPDATE caixa SET receitas = receitas + :valor, saldo_esperado = saldo_esperado + :valor2 WHERE id = :id');
                $stmt->execute([
                    'valor' => $valor,
                    'valor2' => $valor,
                    'id' => $caixaId,
                ]);

                $stmt = DB::pdo()->prepare('UPDATE pagamentos SET caixa_id = :caixa_id WHERE id = :id');
                $stmt->execute([
                    'caixa_id' => $caixaId,
                    'id' => $pagamentoId,
                ]);

                $qtde++;
                $total += $valor;
            }

            if ($qtde > 0) {
                $stmt = DB::pdo()->prepare(
                    'INSERT INTO caixa_movimentacoes (caixa_id, tipo, valor, motivo, meta_json, created_by) VALUES (:caixa_id, :tipo, :valor, :motivo, :meta_json, :created_by)'
                );
                $stmt->execute([
                    'caixa_id' => $caixaId,
                    'tipo' => 'Ajuste',
                    'valor' => $total,
                    'motivo' => 'Importação de pagamentos pagos do dia',
                    'meta_json' => json_encode([
                        'qtde' => $qtde,
                        'data' => $data,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_by' => $user['id'] ?? null,
                ]);
            }

            DB::pdo()->commit();
        } catch (\Throwable $e) {
            if (DB::pdo()->inTransaction()) {
                DB::pdo()->rollBack();
            }
            Flash::add('error', 'Falha ao importar pagamentos. ' . $e->getMessage());
            Response::redirect('/caixa');
        }

        Flash::add('success', 'Pagamentos importados para o caixa: ' . $qtde . ' | Total: R$ ' . number_format($total, 2, ',', '.'));
        Response::redirect('/caixa');
    }
}
