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

final class DespesaController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $categoria = trim((string) Request::input('categoria', ''));
        $dataInicio = (string) Request::input('data_inicio', '');
        $dataFim = (string) Request::input('data_fim', '');
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Build WHERE conditions
        $where = 'WHERE 1=1';
        $params = [];

        if ($categoria !== '') {
            $where .= ' AND d.categoria = :categoria';
            $params['categoria'] = $categoria;
        }

        if ($dataInicio !== '') {
            $where .= ' AND d.created_at >= :data_inicio';
            $params['data_inicio'] = $dataInicio . ' 00:00:00';
        }

        if ($dataFim !== '') {
            $where .= ' AND d.created_at <= :data_fim';
            $params['data_fim'] = $dataFim . ' 23:59:59';
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM despesas d {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT d.*, u.nome as created_by_nome 
                FROM despesas d 
                LEFT JOIN usuarios u ON d.created_by = u.id 
                {$where}
                ORDER BY d.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pagination->perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination->offset, \PDO::PARAM_INT);
        $stmt->execute();
        $despesas = $stmt->fetchAll();

        // Calcular total (use separate query for accurate total regardless of pagination)
        $totalValor = 0;
        if (!empty($despesas)) {
            $totalSql = "SELECT COALESCE(SUM(valor), 0) as total FROM despesas d {$where}";
            $totalStmt = DB::pdo()->prepare($totalSql);
            foreach ($params as $key => $value) {
                $totalStmt->bindValue($key, $value);
            }
            $totalStmt->execute();
            $totalValor = (float) $totalStmt->fetchColumn();
        }

        View::render('despesas/index', [
            'pageTitle' => 'Despesas',
            'despesas' => $despesas,
            'total' => $totalValor,
            'categoria' => $categoria,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        View::render('despesas/form', [
            'pageTitle' => 'Nova Despesa',
            'despesa' => [
                'id' => null,
                'descricao' => '',
                'categoria' => '',
                'valor' => '',
                'vencimento' => '',
                'data_pagamento' => '',
                'forma_pagamento' => '',
                'recorrente' => 0,
                'periodicidade' => '',
            ],
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

        $descricao = trim((string) Request::input('descricao', ''));
        $categoria = trim((string) Request::input('categoria', ''));
        $valor = (float) Request::input('valor', 0);
        $vencimento = (string) Request::input('vencimento', '');
        $dataPagamento = (string) Request::input('data_pagamento', '');
        $formaPagamento = trim((string) Request::input('forma_pagamento', ''));
        $recorrente = (int) Request::input('recorrente', 0);
        $periodicidade = $recorrente ? trim((string) Request::input('periodicidade', '')) : null;

        if ($descricao === '' || $categoria === '' || $valor <= 0) {
            Flash::add('error', 'Descrição, categoria e valor são obrigatórios.');
            Response::redirect('/despesas/create');
        }

        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO despesas (descricao, categoria, valor, vencimento, data_pagamento, forma_pagamento, recorrente, periodicidade, created_by, created_at) 
             VALUES (:descricao, :categoria, :valor, :vencimento, :data_pagamento, :forma_pagamento, :recorrente, :periodicidade, :created_by, NOW())'
        );
        $stmt->execute([
            'descricao' => $descricao,
            'categoria' => $categoria,
            'valor' => $valor,
            'vencimento' => $vencimento ?: null,
            'data_pagamento' => $dataPagamento ?: null,
            'forma_pagamento' => $formaPagamento ?: null,
            'recorrente' => $recorrente,
            'periodicidade' => $periodicidade,
            'created_by' => $user['id'] ?? null,
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        AuditoriaService::log(Auth::user(), 'despesa_criada', 'despesas', $id, null, [
            'descricao' => $descricao,
            'valor' => $valor,
        ]);

        // Se já paga, atualizar caixa
        if ($dataPagamento) {
            $this->adicionarDespesaAoCaixa($valor);
        }

        Flash::add('success', 'Despesa registrada.');
        Response::redirect('/despesas');
    }

    public function edit(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM despesas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $despesa = $stmt->fetch();

        if (!$despesa) {
            Flash::add('error', 'Despesa não encontrada.');
            Response::redirect('/despesas');
        }

        View::render('despesas/form', [
            'pageTitle' => 'Editar Despesa',
            'despesa' => $despesa,
        ]);
    }

    public function update(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM despesas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Despesa não encontrada.');
            Response::redirect('/despesas');
        }

        $descricao = trim((string) Request::input('descricao', ''));
        $categoria = trim((string) Request::input('categoria', ''));
        $valor = (float) Request::input('valor', 0);
        $vencimento = (string) Request::input('vencimento', '');
        $dataPagamento = (string) Request::input('data_pagamento', '');
        $formaPagamento = trim((string) Request::input('forma_pagamento', ''));

        if ($descricao === '' || $categoria === '' || $valor <= 0) {
            Flash::add('error', 'Descrição, categoria e valor são obrigatórios.');
            Response::redirect('/despesas/edit?id=' . $id);
        }

        $stmt = DB::pdo()->prepare(
            'UPDATE despesas SET descricao = :descricao, categoria = :categoria, valor = :valor, vencimento = :vencimento, data_pagamento = :data_pagamento, forma_pagamento = :forma_pagamento WHERE id = :id'
        );
        $stmt->execute([
            'descricao' => $descricao,
            'categoria' => $categoria,
            'valor' => $valor,
            'vencimento' => $vencimento ?: null,
            'data_pagamento' => $dataPagamento ?: null,
            'forma_pagamento' => $formaPagamento ?: null,
            'id' => $id,
        ]);

        AuditoriaService::log(Auth::user(), 'despesa_atualizada', 'despesas', $id, $before, [
            'descricao' => $descricao,
            'valor' => $valor,
        ]);

        Flash::add('success', 'Despesa atualizada.');
        Response::redirect('/despesas');
    }

    public function delete(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        if ($id <= 0) {
            Flash::add('error', 'Despesa inválida.');
            Response::redirect('/despesas');
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM despesas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $despesa = $stmt->fetch();

        if (!$despesa) {
            Flash::add('error', 'Despesa não encontrada.');
            Response::redirect('/despesas');
        }

        $dataPagamento = (string) ($despesa['data_pagamento'] ?? '');
        $valor = (float) ($despesa['valor'] ?? 0);

        $stmt = DB::pdo()->prepare('DELETE FROM despesas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($dataPagamento !== '' && $valor > 0) {
            $this->estornarDespesaAoCaixa($valor, $dataPagamento);
        }

        AuditoriaService::log(Auth::user(), 'despesa_excluida', 'despesas', $id, $despesa, [
            'valor' => $valor,
            'data_pagamento' => $dataPagamento ?: null,
        ]);

        Flash::add('success', 'Despesa excluída.');
        Response::redirect('/despesas');
    }

    private function adicionarDespesaAoCaixa(float $valor): void
    {
        $hoje = date('Y-m-d');

        $stmt = DB::pdo()->prepare("SELECT id, status FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['data' => $hoje]);
        $caixa = $stmt->fetch();

        if ($caixa && (string) $caixa['status'] === 'Aberto') {
            $stmt = DB::pdo()->prepare(
                'UPDATE caixa SET despesas = despesas + :valor_despesas, saldo_esperado = saldo_esperado - :valor_saldo WHERE id = :id'
            );
            $stmt->execute([
                'valor_despesas' => $valor,
                'valor_saldo' => $valor,
                'id' => $caixa['id'],
            ]);
        }
    }

    private function estornarDespesaAoCaixa(float $valor, string $dataPagamento): void
    {
        $data = $dataPagamento;

        $stmt = DB::pdo()->prepare("SELECT id, status FROM caixa WHERE data = :data AND status = 'Aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['data' => $data]);
        $caixa = $stmt->fetch();

        if ($caixa && (string) $caixa['status'] === 'Aberto') {
            $stmt = DB::pdo()->prepare(
                'UPDATE caixa SET despesas = despesas - :valor_despesas, saldo_esperado = saldo_esperado + :valor_saldo WHERE id = :id'
            );
            $stmt->execute([
                'valor_despesas' => $valor,
                'valor_saldo' => $valor,
                'id' => $caixa['id'],
            ]);
        }
    }
}
