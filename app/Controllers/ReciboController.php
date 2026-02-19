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

final class ReciboController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Recibos');

        $osId = (int) Request::input('os_id', 0);
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Build WHERE conditions
        $where = 'WHERE 1=1';
        $params = [];

        if ($osId > 0) {
            $where .= ' AND r.os_id = :os_id';
            $params['os_id'] = $osId;
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM recibos r JOIN ordens_servico os ON r.os_id = os.id JOIN clientes c ON r.cliente_id = c.id {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT r.*, os.numero as os_numero, c.nome as cliente_nome 
                FROM recibos r 
                JOIN ordens_servico os ON r.os_id = os.id 
                JOIN clientes c ON r.cliente_id = c.id 
                {$where}
                ORDER BY r.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pagination->perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination->offset, \PDO::PARAM_INT);
        $stmt->execute();
        $recibos = $stmt->fetchAll();

        View::render('recibos/index', [
            'pageTitle' => 'Recibos',
            'recibos' => $recibos,
            'osId' => $osId,
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Recibos', View::url('/recibos'));
        \App\Core\Breadcrumb::add('Novo Recibo');

        $osId = (int) Request::input('os_id', 0);

        $stmt = DB::pdo()->prepare(
            'SELECT os.*, c.nome as cliente_nome, c.id as cliente_id, c.telefone as cliente_telefone
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

        // Verificar se já existe recibo
        $stmt = DB::pdo()->prepare('SELECT id FROM recibos WHERE os_id = :os_id');
        $stmt->execute(['os_id' => $osId]);
        if ($stmt->fetch()) {
            Flash::add('warning', 'Recibo já emitido para esta OS.');
            Response::redirect('/os/edit?id=' . $osId);
        }

        // Buscar pagamentos
        $stmt = DB::pdo()->prepare(
            'SELECT * FROM pagamentos WHERE os_id = :os_id AND status = :status'
        );
        $stmt->execute(['os_id' => $osId, 'status' => 'Pago']);
        $pagamentos = $stmt->fetchAll();

        // Calcular total pago
        $totalPago = array_sum(array_column($pagamentos, 'valor'));

        View::render('recibos/form', [
            'pageTitle' => 'Emitir Recibo - OS ' . $os['numero'],
            'os' => $os,
            'pagamentos' => $pagamentos,
            'totalPago' => $totalPago,
            'recibo' => [
                'garantia_dias' => 30,
                'termos' => "Garantia válida apenas para o serviço executado.\nObjetos não retirados em 90 dias serão descartados.",
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

        $osId = (int) Request::input('os_id', 0);
        $garantiaDias = (int) Request::input('garantia_dias', 30);
        $termos = trim((string) Request::input('termos', ''));

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

        // Buscar pagamentos
        $stmt = DB::pdo()->prepare(
            'SELECT * FROM pagamentos WHERE os_id = :os_id AND status = :status'
        );
        $stmt->execute(['os_id' => $osId, 'status' => 'Pago']);
        $pagamentos = $stmt->fetchAll();

        if (!$pagamentos) {
            Flash::add('error', 'Nenhum pagamento registrado para esta OS.');
            Response::redirect('/os/edit?id=' . $osId);
        }

        // Determinar forma de pagamento principal
        $formasPagamento = array_unique(array_column($pagamentos, 'forma_pagamento'));
        $formaPagamento = implode(', ', array_filter($formasPagamento));

        $numero = NumeracaoService::gerar('recibo');
        $user = Auth::user();

        $stmt = DB::pdo()->prepare(
            'INSERT INTO recibos (numero, os_id, cliente_id, valor_total, forma_pagamento, garantia_dias, termos, created_by, created_at) 
             VALUES (:numero, :os_id, :cliente_id, :valor_total, :forma_pagamento, :garantia_dias, :termos, :created_by, NOW())'
        );
        $stmt->execute([
            'numero' => $numero,
            'os_id' => $osId,
            'cliente_id' => $os['cliente_id'],
            'valor_total' => $os['valor_total'],
            'forma_pagamento' => $formaPagamento,
            'garantia_dias' => $garantiaDias,
            'termos' => $termos,
            'created_by' => $user['id'] ?? null,
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        AuditoriaService::log(Auth::user(), 'recibo_emitido', 'recibos', $id, null, [
            'numero' => $numero,
            'os_id' => $osId,
            'valor' => $os['valor_total'],
        ]);

        // Gerar link público
        $linkPublico = LinkPublicoService::criar('recibo', $id, 90);

        Flash::add('success', 'Recibo emitido: ' . $numero);
        Response::redirect('/recibos/visualizar?id=' . $id);
    }

    public function visualizar(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Recibos', View::url('/recibos'));

        $id = (int) Request::input('id', 0);

        $stmt = DB::pdo()->prepare(
            'SELECT r.*, os.numero as os_numero, os.data_entrada, c.nome as cliente_nome, c.telefone as cliente_telefone, u.nome as created_by_nome
             FROM recibos r 
             JOIN ordens_servico os ON r.os_id = os.id 
             JOIN clientes c ON r.cliente_id = c.id 
             LEFT JOIN usuarios u ON r.created_by = u.id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $recibo = $stmt->fetch();

        if (!$recibo) {
            Flash::add('error', 'Recibo não encontrado.');
            Response::redirect('/recibos');
        }

        // Buscar pagamentos
        $stmt = DB::pdo()->prepare(
            'SELECT * FROM pagamentos WHERE os_id = :os_id AND status = :status'
        );
        $stmt->execute(['os_id' => $recibo['os_id'], 'status' => 'Pago']);
        $pagamentos = $stmt->fetchAll();

        // Buscar sapatos
        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE os_id = :os_id');
        $stmt->execute(['os_id' => $recibo['os_id']]);
        $sapatos = $stmt->fetchAll();

        $linkPublico = LinkPublicoService::buscarOuCriar('recibo', $id, 90);

        View::render('recibos/visualizar', [
            'pageTitle' => 'Recibo ' . $recibo['numero'],
            'recibo' => $recibo,
            'pagamentos' => $pagamentos,
            'sapatos' => $sapatos,
            'linkPublico' => $linkPublico,
        ]);
    }

    public function imprimir(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Recibos', View::url('/recibos'));
        \App\Core\Breadcrumb::add('Imprimir');

        $id = (int) Request::input('id', 0);

        $stmt = DB::pdo()->prepare(
            'SELECT r.*, os.numero as os_numero, os.data_entrada, c.nome as cliente_nome, c.telefone as cliente_telefone, c.cpf as cliente_cpf
             FROM recibos r 
             JOIN ordens_servico os ON r.os_id = os.id 
             JOIN clientes c ON r.cliente_id = c.id 
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $recibo = $stmt->fetch();

        if (!$recibo) {
            Flash::add('error', 'Recibo não encontrado.');
            Response::redirect('/recibos');
        }

        $stmt = DB::pdo()->prepare(
            'SELECT * FROM pagamentos WHERE os_id = :os_id AND status = :status'
        );
        $stmt->execute(['os_id' => $recibo['os_id'], 'status' => 'Pago']);
        $pagamentos = $stmt->fetchAll();

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE os_id = :os_id');
        $stmt->execute(['os_id' => $recibo['os_id']]);
        $sapatos = $stmt->fetchAll();

        // Buscar dados da empresa
        $stmt = DB::pdo()->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch() ?: [
            'nome' => 'Sapataria Modelo',
            'cnpj' => '00.000.000/0001-00',
            'endereco' => 'Rua Exemplo, 123 - Centro',
            'telefone' => '(00) 0000-0000',
        ];

        View::render('recibos/imprimir', [
            'pageTitle' => 'Recibo ' . $recibo['numero'],
            'recibo' => $recibo,
            'pagamentos' => $pagamentos,
            'sapatos' => $sapatos,
            'empresa' => $empresa,
            'layout' => 'print',
        ], false);
    }
}
