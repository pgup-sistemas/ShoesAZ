<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditoriaService;

final class PagamentoController
{
    public function contasReceber(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $q = trim((string) Request::input('q', ''));
        $apenasAtrasados = (string) Request::input('atrasados', '') === '1';
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        $where = 'WHERE p.status = :status';
        $params = ['status' => 'Pendente'];

        if ($q !== '') {
            $where .= ' AND (os.numero LIKE :q_os OR c.nome LIKE :q_nome OR c.telefone LIKE :q_tel)';
            $like = '%' . $q . '%';
            $params['q_os'] = $like;
            $params['q_nome'] = $like;
            $params['q_tel'] = $like;
        }

        if ($apenasAtrasados) {
            $where .= ' AND p.vencimento IS NOT NULL AND p.vencimento < CURDATE()';
        }

        $countSql = "SELECT COUNT(*)
                     FROM pagamentos p
                     JOIN ordens_servico os ON p.os_id = os.id
                     JOIN clientes c ON os.cliente_id = c.id
                     {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        $sql = "SELECT p.*, os.numero as os_numero, os.status as os_status, c.nome as cliente_nome, c.telefone as cliente_telefone
                FROM pagamentos p
                JOIN ordens_servico os ON p.os_id = os.id
                JOIN clientes c ON os.cliente_id = c.id
                {$where}
                ORDER BY (p.vencimento IS NULL) ASC, p.vencimento ASC, p.created_at ASC
                LIMIT :limit OFFSET :offset";

        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pagination->perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination->offset, \PDO::PARAM_INT);
        $stmt->execute();
        $contas = $stmt->fetchAll();

        $totalValorSql = "SELECT COALESCE(SUM(p.valor), 0)
                          FROM pagamentos p
                          JOIN ordens_servico os ON p.os_id = os.id
                          JOIN clientes c ON os.cliente_id = c.id
                          {$where}";
        $totalValorStmt = DB::pdo()->prepare($totalValorSql);
        $totalValorStmt->execute($params);
        $totalValor = (float) $totalValorStmt->fetchColumn();

        View::render('pagamentos/contas_receber', [
            'pageTitle' => 'Contas a Receber',
            'q' => $q,
            'apenasAtrasados' => $apenasAtrasados,
            'contas' => $contas,
            'totalValor' => $totalValor,
            'pagination' => $pagination,
        ]);
    }

    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $osId = (int) Request::input('os_id', 0);
        $status = trim((string) Request::input('status', ''));
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Build WHERE conditions
        $where = 'WHERE 1=1';
        $params = [];

        if ($osId > 0) {
            $where .= ' AND p.os_id = :os_id';
            $params['os_id'] = $osId;
        }

        if ($status !== '') {
            $where .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM pagamentos p JOIN ordens_servico os ON p.os_id = os.id JOIN clientes c ON os.cliente_id = c.id {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT p.*, os.numero as os_numero, c.nome as cliente_nome 
                FROM pagamentos p 
                JOIN ordens_servico os ON p.os_id = os.id 
                JOIN clientes c ON os.cliente_id = c.id 
                {$where}
                ORDER BY p.vencimento ASC, p.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pagination->perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination->offset, \PDO::PARAM_INT);
        $stmt->execute();
        $pagamentos = $stmt->fetchAll();

        // Buscar OS para filtro
        $stmt = DB::pdo()->query('SELECT id, numero FROM ordens_servico ORDER BY numero DESC LIMIT 100');
        $ordens = $stmt->fetchAll();

        View::render('pagamentos/index', [
            'pageTitle' => 'Pagamentos',
            'pagamentos' => $pagamentos,
            'ordens' => $ordens,
            'osId' => $osId,
            'status' => $status,
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $osId = (int) Request::input('os_id', 0);

        // Buscar OS
        $stmt = DB::pdo()->prepare(
            'SELECT os.*, c.nome as cliente_nome, c.id as cliente_id 
             FROM ordens_servico os 
             JOIN clientes c ON os.cliente_id = c.id 
             WHERE os.id = :id'
        );
        $stmt->execute(['id' => $osId]);
        $os = $stmt->fetch();

        if (!$os) {
            Flash::add('error', 'OS não encontrada.');
            Response::redirect('/os');
        }

        // Calcular próxima parcela
        $stmt = DB::pdo()->prepare('SELECT COUNT(*) as total FROM pagamentos WHERE os_id = :os_id');
        $stmt->execute(['os_id' => $osId]);
        $proximaParcela = (int) $stmt->fetch()['total'] + 1;

        // Calcular valor já pago
        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as pago FROM pagamentos WHERE os_id = :os_id AND status = :status');
        $stmt->execute(['os_id' => $osId, 'status' => 'Pago']);
        $valorPago = (float) $stmt->fetch()['pago'];

        $valorRestante = max(0, (float) $os['valor_total'] - $valorPago);

        View::render('pagamentos/form', [
            'pageTitle' => 'Novo Pagamento - OS ' . $os['numero'],
            'os' => $os,
            'pagamento' => [
                'id' => null,
                'parcela_numero' => $proximaParcela,
                'valor' => $valorRestante,
                'vencimento' => date('Y-m-d', strtotime('+7 days')),
                'forma_pagamento' => '',
                'status' => 'Pendente',
            ],
            'valorPago' => $valorPago,
            'valorRestante' => $valorRestante,
        ]);
    }

    public function store(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $osId = (int) Request::input('os_id', 0);
        $parcelaNumero = (int) Request::input('parcela_numero', 0);
        $valor = (float) Request::input('valor', 0);
        $vencimento = (string) Request::input('vencimento', '');
        $formaPagamento = trim((string) Request::input('forma_pagamento', ''));
        $status = trim((string) Request::input('status', 'Pendente'));
        $dataPagamento = $status === 'Pago' ? date('Y-m-d') : null;

        if ($osId <= 0 || $valor <= 0) {
            Flash::add('error', 'OS e valor são obrigatórios.');
            Response::redirect('/pagamentos/create?os_id=' . $osId);
        }

        if ($status === 'Pago' && $formaPagamento === '') {
            Flash::add('error', 'Selecione a forma de pagamento para registrar como Pago.');
            Response::redirect('/os/edit?id=' . $osId);
        }

        if ($status === 'Pago') {
            $stmt = DB::pdo()->prepare("SELECT id FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
            $stmt->execute(['data' => date('Y-m-d')]);
            if (!$stmt->fetch()) {
                Flash::add('error', 'Para registrar pagamento como Pago, abra o caixa antes.');
                Response::redirect('/os/edit?id=' . $osId);
            }
        }

        $stmt = DB::pdo()->prepare('SELECT id, valor_total FROM ordens_servico WHERE id = :id');
        $stmt->execute(['id' => $osId]);
        $os = $stmt->fetch();
        if (!$os) {
            Flash::add('error', 'OS não encontrada.');
            Response::redirect('/os');
        }

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as pago FROM pagamentos WHERE os_id = :os_id AND status = :status');
        $stmt->execute(['os_id' => $osId, 'status' => 'Pago']);
        $valorPago = (float) ($stmt->fetch()['pago'] ?? 0);
        $valorRestante = max(0, (float) ($os['valor_total'] ?? 0) - $valorPago);

        if ($valor > $valorRestante) {
            Flash::add('error', 'O valor do pagamento não pode ser maior que o restante da OS.');
            Response::redirect('/pagamentos/create?os_id=' . $osId);
        }

        $user = Auth::user();

        $stmt = DB::pdo()->prepare('SELECT COALESCE(MAX(parcela_numero), 0) as max_parcela FROM pagamentos WHERE os_id = :os_id');
        $stmt->execute(['os_id' => $osId]);
        $maxParcela = (int) ($stmt->fetch()['max_parcela'] ?? 0);

        $parcelaNumeroFinal = $parcelaNumero > 0 ? $parcelaNumero : ($maxParcela + 1);
        if ($parcelaNumeroFinal <= $maxParcela) {
            $parcelaNumeroFinal = $maxParcela + 1;
        }

        try {
            DB::pdo()->beginTransaction();

            $stmt = DB::pdo()->prepare(
                'INSERT INTO pagamentos (os_id, parcela_numero, valor, vencimento, data_pagamento, forma_pagamento, status, created_by, created_at) 
                 VALUES (:os_id, :parcela_numero, :valor, :vencimento, :data_pagamento, :forma_pagamento, :status, :created_by, NOW())'
            );
            $stmt->execute([
                'os_id' => $osId,
                'parcela_numero' => $parcelaNumeroFinal,
                'valor' => $valor,
                'vencimento' => $vencimento ?: null,
                'data_pagamento' => $dataPagamento,
                'forma_pagamento' => $formaPagamento ?: null,
                'status' => $status,
                'created_by' => $user['id'] ?? null,
            ]);

            $id = (int) DB::pdo()->lastInsertId();

            // Se pago, lançar no caixa (na mesma transação)
            if ($status === 'Pago') {
                if ($this->adicionarReceitaAoCaixa($valor, $id) <= 0) {
                    throw new \RuntimeException('Não foi possível lançar o pagamento no caixa.');
                }
            }

            DB::pdo()->commit();
        } catch (\Throwable $e) {
            if (DB::pdo()->inTransaction()) {
                DB::pdo()->rollBack();
            }
            Flash::add('error', 'Não foi possível registrar o pagamento. ' . $e->getMessage());
            Response::redirect('/os/edit?id=' . $osId);
        }

        AuditoriaService::log(Auth::user(), 'pagamento_criado', 'pagamentos', $id, null, [
            'os_id' => $osId,
            'valor' => $valor,
            'status' => $status,
        ]);

        Flash::add('success', 'Pagamento registrado.');
        Response::redirect('/os/edit?id=' . $osId);
    }

    public function quitar(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $formaPagamento = trim((string) Request::input('forma_pagamento', 'Dinheiro'));

        $stmt = DB::pdo()->prepare('SELECT * FROM pagamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Pagamento não encontrado.');
            Response::redirect('/pagamentos');
        }

        if ((string) $before['status'] === 'Pago') {
            Flash::add('warning', 'Pagamento já está quitado.');
            Response::redirect('/os/edit?id=' . $before['os_id']);
        }

        $stmt = DB::pdo()->prepare("SELECT id FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['data' => date('Y-m-d')]);
        if (!$stmt->fetch()) {
            Flash::add('error', 'Para quitar pagamento, abra o caixa antes.');
            Response::redirect('/os/edit?id=' . $before['os_id']);
        }

        $dataPagamento = date('Y-m-d');

        try {
            DB::pdo()->beginTransaction();

            $stmt = DB::pdo()->prepare(
                'UPDATE pagamentos SET status = :status, data_pagamento = :data_pagamento, forma_pagamento = :forma_pagamento WHERE id = :id'
            );
            $stmt->execute([
                'status' => 'Pago',
                'data_pagamento' => $dataPagamento,
                'forma_pagamento' => $formaPagamento,
                'id' => $id,
            ]);

            if ($this->adicionarReceitaAoCaixa((float) $before['valor'], $id) <= 0) {
                throw new \RuntimeException('Não foi possível lançar o pagamento no caixa.');
            }

            DB::pdo()->commit();
        } catch (\Throwable $e) {
            if (DB::pdo()->inTransaction()) {
                DB::pdo()->rollBack();
            }
            Flash::add('error', 'Não foi possível quitar o pagamento. ' . $e->getMessage());
            Response::redirect('/os/edit?id=' . $before['os_id']);
        }

        AuditoriaService::log(Auth::user(), 'pagamento_quitado', 'pagamentos', $id, $before, [
            'forma_pagamento' => $formaPagamento,
        ]);

        Flash::add('success', 'Pagamento quitado.');
        Response::redirect('/os/edit?id=' . $before['os_id']);
    }

    private function adicionarReceitaAoCaixa(float $valor, int $pagamentoId): int
    {
        $hoje = date('Y-m-d');

        $stmt = DB::pdo()->prepare("SELECT id, status FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['data' => $hoje]);
        $caixa = $stmt->fetch();

        if ($caixa && (string) $caixa['status'] === 'Aberto') {
            $stmt = DB::pdo()->prepare(
                'UPDATE caixa SET receitas = receitas + :valor_receitas, saldo_esperado = saldo_esperado + :valor_saldo WHERE id = :id'
            );
            $stmt->execute([
                'valor_receitas' => $valor,
                'valor_saldo' => $valor,
                'id' => $caixa['id'],
            ]);

            $stmt = DB::pdo()->prepare('UPDATE pagamentos SET caixa_id = :caixa_id WHERE id = :id');
            $stmt->execute([
                'caixa_id' => (int) $caixa['id'],
                'id' => $pagamentoId,
            ]);

            return (int) $caixa['id'];
        }

        return 0;
    }
}
