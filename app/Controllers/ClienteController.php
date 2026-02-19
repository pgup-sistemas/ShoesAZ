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

final class ClienteController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Clientes');

        $q = trim((string) Request::input('q', ''));
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Build query conditions
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = 'WHERE nome LIKE ? OR telefone LIKE ? OR cpf LIKE ?';
            $param = '%' . $q . '%';
            $params = [$param, $param, $param];
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM clientes {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT id, nome, cpf, telefone, email, created_at 
                FROM clientes 
                {$where} 
                ORDER BY id DESC 
                LIMIT ? OFFSET ?";
        $stmt = DB::pdo()->prepare($sql);
        $execParams = array_merge($params, [$pagination->perPage, $pagination->offset]);
        $stmt->execute($execParams);
        $clientes = $stmt->fetchAll();

        View::render('clientes/index', [
            'pageTitle' => 'Clientes',
            'clientes' => $clientes,
            'q' => $q,
            'pagination' => $pagination,
        ]);
    }

    public function buscar(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $q = trim((string) Request::input('q', ''));

        header('Content-Type: application/json; charset=utf-8');

        if ($q === '' || mb_strlen($q) < 2) {
            echo json_encode([]);
            return;
        }

        $param = '%' . $q . '%';
        $stmt = DB::pdo()->prepare(
            'SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? OR telefone LIKE ? OR cpf LIKE ? ORDER BY nome LIMIT 20'
        );
        $stmt->execute([$param, $param, $param]);
        $rows = $stmt->fetchAll();

        echo json_encode(is_array($rows) ? $rows : []);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Clientes', View::url('/clientes'));
        \App\Core\Breadcrumb::add('Novo Cliente');

        View::render('clientes/form', [
            'pageTitle' => 'Novo Cliente',
            'cliente' => [
                'id' => null,
                'nome' => '',
                'cpf' => '',
                'telefone' => '',
                'email' => '',
                'endereco' => '',
                'observacoes' => '',
            ],
            'action' => View::url('/clientes/store'),
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

        $payload = $this->payloadFromRequest();

        if ($payload['nome'] === '' || $payload['telefone'] === '') {
            Flash::add('error', 'Nome e telefone são obrigatórios.');
            Response::redirect('/clientes/create');
        }

        $stmt = DB::pdo()->prepare(
            'INSERT INTO clientes (nome, cpf, telefone, email, endereco, observacoes) VALUES (:nome, :cpf, :telefone, :email, :endereco, :observacoes)'
        );
        $stmt->execute($payload);
        $id = (int) DB::pdo()->lastInsertId();

        AuditoriaService::log(Auth::user(), 'cliente_criado', 'clientes', $id, null, $payload);

        Flash::add('success', 'Cliente criado com sucesso.');
        Response::redirect('/clientes');
    }

    public function edit(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Clientes', View::url('/clientes'));
        \App\Core\Breadcrumb::add('Editar');

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            Flash::add('error', 'Cliente não encontrado.');
            Response::redirect('/clientes');
        }

        // Buscar histórico de OS
        $stmt = DB::pdo()->prepare(
            'SELECT os.*, GROUP_CONCAT(s.tipo_servico SEPARATOR ", ") as servicos
             FROM ordens_servico os
             LEFT JOIN sapatos s ON s.os_id = os.id
             WHERE os.cliente_id = :id
             GROUP BY os.id
             ORDER BY os.created_at DESC'
        );
        $stmt->execute(['id' => $id]);
        $ordensServico = $stmt->fetchAll();

        // Buscar histórico de orçamentos
        $stmt = DB::pdo()->prepare(
            'SELECT o.*, GROUP_CONCAT(s.tipo_servico SEPARATOR ", ") as servicos
             FROM orcamentos o
             LEFT JOIN sapatos s ON s.orcamento_id = o.id
             WHERE o.cliente_id = :id
             GROUP BY o.id
             ORDER BY o.created_at DESC'
        );
        $stmt->execute(['id' => $id]);
        $orcamentos = $stmt->fetchAll();

        // Métricas do cliente
        $metricas = $this->calcularMetricas($id, $ordensServico, $orcamentos);

        View::render('clientes/form', [
            'pageTitle' => 'Editar Cliente',
            'cliente' => $cliente,
            'action' => View::url('/clientes/update') . '?id=' . $id,
            'ordensServico' => $ordensServico,
            'orcamentos' => $orcamentos,
            'metricas' => $metricas,
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

        $stmt = DB::pdo()->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Cliente não encontrado.');
            Response::redirect('/clientes');
        }

        $payload = $this->payloadFromRequest();
        if ($payload['nome'] === '' || $payload['telefone'] === '') {
            Flash::add('error', 'Nome e telefone são obrigatórios.');
            Response::redirect('/clientes/edit?id=' . $id);
        }

        $payload['id'] = $id;

        $stmt = DB::pdo()->prepare(
            'UPDATE clientes SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email, endereco = :endereco, observacoes = :observacoes WHERE id = :id'
        );
        $stmt->execute($payload);

        AuditoriaService::log(Auth::user(), 'cliente_atualizado', 'clientes', $id, $before, $payload);

        Flash::add('success', 'Cliente atualizado.');
        Response::redirect('/clientes');
    }

    private function payloadFromRequest(): array
    {
        return [
            'nome' => trim((string) Request::input('nome', '')),
            'cpf' => trim((string) Request::input('cpf', '')),
            'telefone' => trim((string) Request::input('telefone', '')),
            'email' => trim((string) Request::input('email', '')),
            'endereco' => trim((string) Request::input('endereco', '')),
            'observacoes' => trim((string) Request::input('observacoes', '')),
        ];
    }

    private function calcularMetricas(int $clienteId, array $ordensServico, array $orcamentos): array
    {
        $metricas = [
            'total_os' => count($ordensServico),
            'total_orcamentos' => count($orcamentos),
            'valor_total_gasto' => 0,
            'valor_medio_os' => 0,
            'primeira_visita' => null,
            'ultima_visita' => null,
            'dias_ultima_visita' => null,
            'os_concluidas' => 0,
            'taxa_conversao' => 0,
        ];

        // Calcular valores
        $valores = [];
        $datas = [];
        $osConcluidas = 0;

        foreach ($ordensServico as $os) {
            $valores[] = (float) ($os['valor_total'] ?? 0);
            $datas[] = $os['created_at'];
            if (in_array($os['status'], ['Entregue', 'Concluído'])) {
                $osConcluidas++;
            }
        }

        foreach ($orcamentos as $orc) {
            $datas[] = $orc['created_at'];
        }

        if (!empty($valores)) {
            $metricas['valor_total_gasto'] = array_sum($valores);
            $metricas['valor_medio_os'] = $metricas['valor_total_gasto'] / count($valores);
        }

        // Ordenar datas
        sort($datas);
        if (!empty($datas)) {
            $metricas['primeira_visita'] = $datas[0];
            $metricas['ultima_visita'] = end($datas);
            $metricas['dias_ultima_visita'] = (new \DateTime($metricas['ultima_visita']))->diff(new \DateTime())->days;
        }

        $metricas['os_concluidas'] = $osConcluidas;

        // Taxa de conversão (orçamentos → OS)
        $totalOrcamentosNaoConvertidos = count(array_filter($orcamentos, fn($o) => $o['status'] !== 'Convertido'));
        $orcamentosConvertidos = count(array_filter($orcamentos, fn($o) => $o['status'] === 'Convertido'));
        $totalOrcamentosValidos = $orcamentosConvertidos + $totalOrcamentosNaoConvertidos;
        
        if ($totalOrcamentosValidos > 0) {
            $metricas['taxa_conversao'] = ($orcamentosConvertidos / $totalOrcamentosValidos) * 100;
        }

        return $metricas;
    }
}
