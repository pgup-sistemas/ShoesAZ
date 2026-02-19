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
use App\Services\LinkPublicoService;
use App\Services\NumeracaoService;

final class OrcamentoController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Orçamentos');

        $q = trim((string) Request::input('q', ''));
        $status = trim((string) Request::input('status', ''));
        $sort = trim((string) Request::input('sort', 'created_at'));
        $dir = strtoupper(trim((string) Request::input('dir', 'DESC')));
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Validar parâmetros de sort
        $validSortFields = ['numero', 'cliente_nome', 'valor_final', 'status', 'validade', 'created_at'];
        if (!in_array($sort, $validSortFields, true)) {
            $sort = 'created_at';
        }
        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            $dir = 'DESC';
        }

        // Build WHERE conditions
        $where = 'WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $where .= ' AND (o.numero LIKE ? OR c.nome LIKE ? OR c.telefone LIKE ?)';
            $param = '%' . $q . '%';
            $params[] = $param;
            $params[] = $param;
            $params[] = $param;
        }

        if ($status !== '' && in_array($status, ['Aguardando', 'Aprovado', 'Reprovado', 'Expirado', 'Convertido'], true)) {
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM orcamentos o JOIN clientes c ON o.cliente_id = c.id {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Build ORDER BY clause with proper field names
        $sortField = match($sort) {
            'numero' => 'o.numero',
            'cliente_nome' => 'c.nome',
            'valor_final' => 'o.valor_final',
            'status' => 'o.status',
            'validade' => 'o.validade',
            'created_at' => 'o.created_at',
            default => 'o.created_at',
        };
        $orderBy = "{$sortField} {$dir}";

        // Fetch paginated results
        $sql = "SELECT o.*, c.nome as cliente_nome 
                FROM orcamentos o 
                JOIN clientes c ON o.cliente_id = c.id 
                {$where}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        $stmt = DB::pdo()->prepare($sql);
        $execParams = array_merge($params, [$pagination->perPage, $pagination->offset]);
        $stmt->execute($execParams);
        $orcamentos = $stmt->fetchAll();

        View::render('orcamentos/index', [
            'pageTitle' => 'Orçamentos',
            'orcamentos' => $orcamentos,
            'q' => $q,
            'status' => $status,
            'pagination' => $pagination,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Orçamentos', View::url('/orcamentos'));
        \App\Core\Breadcrumb::add('Novo Orçamento');

        View::render('orcamentos/form', [
            'pageTitle' => 'Novo Orçamento',
            'orcamento' => [
                'id' => null,
                'cliente_id' => '',
                'valor_total' => 0,
                'desconto' => 0,
                'valor_final' => 0,
                'status' => 'Aguardando',
                'validade' => date('Y-m-d', strtotime('+30 days')),
                'observacoes' => '',
            ],
            'clienteSelecionado' => null,
            'action' => View::url('/orcamentos/store'),
            'isEdit' => false,
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

        $clienteId = (int) Request::input('cliente_id', 0);
        $desconto = (float) Request::input('desconto', 0);
        $validade = (string) Request::input('validade', '');
        $observacoes = trim((string) Request::input('observacoes', ''));

        if ($clienteId <= 0) {
            Flash::add('error', 'Selecione um cliente.');
            Response::redirect('/orcamentos/create');
        }

        $numero = NumeracaoService::gerar('orcamento');
        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO orcamentos (numero, cliente_id, valor_total, desconto, valor_final, status, validade, observacoes, created_by, created_at) 
             VALUES (:numero, :cliente_id, 0, :desconto, 0, :status, :validade, :observacoes, :created_by, NOW())'
        );
        $stmt->execute([
            'numero' => $numero,
            'cliente_id' => $clienteId,
            'desconto' => $desconto,
            'status' => 'Aguardando',
            'validade' => $validade ?: null,
            'observacoes' => $observacoes,
            'created_by' => $user['id'] ?? null,
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        AuditoriaService::log(Auth::user(), 'orcamento_criado', 'orcamentos', $id, null, [
            'numero' => $numero,
            'cliente_id' => $clienteId,
        ]);

        Flash::add('success', 'Orçamento criado: ' . $numero);
        Response::redirect('/orcamentos/edit?id=' . $id);
    }

    public function edit(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Orçamentos', View::url('/orcamentos'));
        \App\Core\Breadcrumb::add('Editar');

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $orcamento = $stmt->fetch();

        if (!$orcamento) {
            Flash::add('error', 'Orçamento não encontrado.');
            Response::redirect('/orcamentos');
        }

        $clienteSelecionado = $this->getClienteById((int) ($orcamento['cliente_id'] ?? 0));
        $sapatos = $this->getSapatosDoOrcamento($id);

        $linkPublico = null;
        if ((string) ($orcamento['status'] ?? '') !== 'Convertido') {
            $linkPublico = LinkPublicoService::buscarOuCriar('orcamento', $id, 30);
        }

        View::render('orcamentos/form', [
            'pageTitle' => 'Orçamento ' . $orcamento['numero'],
            'orcamento' => $orcamento,
            'clienteSelecionado' => $clienteSelecionado,
            'sapatos' => $sapatos,
            'linkPublico' => $linkPublico,
            'action' => View::url('/orcamentos/update') . '?id=' . $id,
            'isEdit' => true,
        ]);
    }

    public function update(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Orçamento não encontrado.');
            Response::redirect('/orcamentos');
        }

        if ((string) $before['status'] === 'Convertido') {
            Flash::add('error', 'Orçamento já convertido não pode ser editado.');
            Response::redirect('/orcamentos');
        }

        $clienteId = (int) Request::input('cliente_id', 0);
        $desconto = (float) Request::input('desconto', 0);
        $validade = (string) Request::input('validade', '');
        $observacoes = trim((string) Request::input('observacoes', ''));

        if ($clienteId <= 0) {
            Flash::add('error', 'Selecione um cliente.');
            Response::redirect('/orcamentos/edit?id=' . $id);
        }

        $stmt = DB::pdo()->prepare(
            'UPDATE orcamentos SET cliente_id = :cliente_id, desconto = :desconto, validade = :validade, observacoes = :observacoes, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'cliente_id' => $clienteId,
            'desconto' => $desconto,
            'validade' => $validade ?: null,
            'observacoes' => $observacoes,
            'id' => $id,
        ]);

        $this->recalcularTotais($id);

        AuditoriaService::log(Auth::user(), 'orcamento_atualizado', 'orcamentos', $id, $before, [
            'cliente_id' => $clienteId,
            'desconto' => $desconto,
        ]);

        Flash::add('success', 'Orçamento atualizado.');
        Response::redirect('/orcamentos/edit?id=' . $id);
    }

    public function aprovar(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Orçamento não encontrado.');
            Response::redirect('/orcamentos');
        }

        if ((string) $before['status'] !== 'Aguardando') {
            Flash::add('error', 'Apenas orçamentos em aguardo podem ser aprovados.');
            Response::redirect('/orcamentos/edit?id=' . $id);
        }

        $stmt = DB::pdo()->prepare('UPDATE orcamentos SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => 'Aprovado', 'id' => $id]);

        AuditoriaService::log(Auth::user(), 'orcamento_aprovado', 'orcamentos', $id, $before, ['status' => 'Aprovado']);

        Flash::add('success', 'Orçamento aprovado.');
        Response::redirect('/orcamentos/edit?id=' . $id);
    }

    public function converter(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $orcamento = $stmt->fetch();

        if (!$orcamento) {
            Flash::add('error', 'Orçamento não encontrado.');
            Response::redirect('/orcamentos');
        }

        if ((string) $orcamento['status'] !== 'Aprovado') {
            Flash::add('error', 'Apenas orçamentos aprovados podem ser convertidos.');
            Response::redirect('/orcamentos/edit?id=' . $id);
        }

        $numeroOS = NumeracaoService::gerar('ordem_servico');
        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO ordens_servico (numero, orcamento_id, cliente_id, data_entrada, prazo_entrega, valor_total, status, created_by, created_at) 
             VALUES (:numero, :orcamento_id, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), :valor_total, :status, :created_by, NOW())'
        );
        $stmt->execute([
            'numero' => $numeroOS,
            'orcamento_id' => $id,
            'cliente_id' => $orcamento['cliente_id'],
            'valor_total' => $orcamento['valor_final'],
            'status' => 'Recebido',
            'created_by' => $user['id'] ?? null,
        ]);

        $osId = (int) DB::pdo()->lastInsertId();

        $stmt = DB::pdo()->prepare('UPDATE sapatos SET os_id = :os_id WHERE orcamento_id = :orcamento_id');
        $stmt->execute(['os_id' => $osId, 'orcamento_id' => $id]);

        $stmt = DB::pdo()->prepare('UPDATE orcamentos SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => 'Convertido', 'id' => $id]);

        AuditoriaService::log(Auth::user(), 'orcamento_convertido', 'orcamentos', $id, $orcamento, [
            'os_id' => $osId,
            'os_numero' => $numeroOS,
        ]);

        Flash::add('success', 'Orçamento convertido em OS: ' . $numeroOS);
        Response::redirect('/os/edit?id=' . $osId);
    }

    public function imprimir(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Orçamentos', View::url('/orcamentos'));
        \App\Core\Breadcrumb::add('Imprimir');

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare(
            'SELECT o.*, c.nome as cliente_nome, c.telefone as cliente_telefone, c.cpf as cliente_cpf, c.endereco as cliente_endereco 
             FROM orcamentos o 
             JOIN clientes c ON o.cliente_id = c.id 
             WHERE o.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $orcamento = $stmt->fetch();

        if (!$orcamento) {
            Flash::add('error', 'Orçamento não encontrado.');
            Response::redirect('/orcamentos');
        }

        $sapatos = $this->getSapatosDoOrcamento($id);

        // Buscar dados da empresa
        $stmt = DB::pdo()->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch() ?: [
            'nome' => 'Sapataria Modelo',
            'cnpj' => '00.000.000/0001-00',
            'endereco' => 'Rua Exemplo, 123 - Centro',
            'telefone' => '(00) 0000-0000',
        ];

        View::render('orcamentos/imprimir', [
            'pageTitle' => 'Imprimir Orçamento ' . $orcamento['numero'],
            'orcamento' => $orcamento,
            'sapatos' => $sapatos,
            'empresa' => $empresa,
        ], false);
    }

    private function getClientesParaSelect(): array
    {
        $stmt = DB::pdo()->query('SELECT id, nome, telefone FROM clientes ORDER BY nome LIMIT 500');
        return $stmt->fetchAll();
    }

    private function getClienteById(int $id): array|null
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = DB::pdo()->prepare('SELECT id, nome, telefone FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function getSapatosDoOrcamento(int $orcamentoId): array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE orcamento_id = :id ORDER BY id');
        $stmt->execute(['id' => $orcamentoId]);
        return $stmt->fetchAll();
    }

    private function recalcularTotais(int $orcamentoId): void
    {
        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as total FROM sapatos WHERE orcamento_id = :id');
        $stmt->execute(['id' => $orcamentoId]);
        $total = (float) $stmt->fetch()['total'];

        $stmt = DB::pdo()->prepare('SELECT desconto FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $orcamentoId]);
        $desconto = (float) $stmt->fetch()['desconto'];

        $final = max(0, $total - $desconto);

        $stmt = DB::pdo()->prepare('UPDATE orcamentos SET valor_total = :total, valor_final = :final WHERE id = :id');
        $stmt->execute(['total' => $total, 'final' => $final, 'id' => $orcamentoId]);
    }
}
