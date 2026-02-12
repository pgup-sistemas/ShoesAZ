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

final class OSController
{
    public function index(): void
    {
        $user = Auth::user();
        $perfil = (string) ($user['perfil'] ?? '');

        if ($perfil === 'Sapateiro') {
            $this->indexSapateiro();
            return;
        }

        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $q = trim((string) Request::input('q', ''));
        $status = trim((string) Request::input('status', ''));
        $atrasados = (int) Request::input('atrasados', 0);
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Build base WHERE conditions
        $where = 'WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $where .= ' AND (os.numero LIKE ? OR c.nome LIKE ? OR c.telefone LIKE ?)';
            $param = '%' . $q . '%';
            $params[] = $param;
            $params[] = $param;
            $params[] = $param;
        }

        if ($status !== '' && in_array($status, ['Recebido', 'Em reparo', 'Aguardando retirada', 'Entregue', 'Cancelado', 'Atrasado'], true)) {
            $where .= ' AND os.status = ?';
            $params[] = $status;
        }

        if ($atrasados === 1) {
            $where .= ' AND os.prazo_entrega < CURDATE() AND os.status NOT IN (?, ?)';
            $params[] = 'Entregue';
            $params[] = 'Cancelado';
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM ordens_servico os JOIN clientes c ON os.cliente_id = c.id {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT os.*, c.nome as cliente_nome, u.nome as sapateiro_nome 
                FROM ordens_servico os 
                JOIN clientes c ON os.cliente_id = c.id 
                LEFT JOIN usuarios u ON os.sapateiro_id = u.id 
                {$where}
                ORDER BY os.prazo_entrega ASC, os.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = DB::pdo()->prepare($sql);
        $execParams = array_merge($params, [$pagination->perPage, $pagination->offset]);
        $stmt->execute($execParams);
        $ordens = $stmt->fetchAll();

        View::render('os/index', [
            'pageTitle' => 'Ordens de Serviço',
            'ordens' => $ordens,
            'q' => $q,
            'status' => $status,
            'atrasados' => $atrasados,
            'isSapateiro' => false,
            'pagination' => $pagination,
        ]);
    }

    private function indexSapateiro(): void
    {
        $user = Auth::user();
        $sapateiroId = (int) ($user['id'] ?? 0);

        $q = trim((string) Request::input('q', ''));
        $status = trim((string) Request::input('status', ''));
        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        $where = 'WHERE os.sapateiro_id = ?';
        $params = [$sapateiroId];

        if ($q !== '') {
            $where .= ' AND (os.numero LIKE ? OR c.nome LIKE ?)';
            $param = '%' . $q . '%';
            $params[] = $param;
            $params[] = $param;
        }

        if ($status !== '') {
            $where .= ' AND os.status = ?';
            $params[] = $status;
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM ordens_servico os JOIN clientes c ON os.cliente_id = c.id {$where}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $sql = "SELECT os.*, c.nome as cliente_nome 
                FROM ordens_servico os 
                JOIN clientes c ON os.cliente_id = c.id 
                {$where}
                ORDER BY os.prazo_entrega ASC 
                LIMIT ? OFFSET ?";
        $stmt = DB::pdo()->prepare($sql);
        $execParams = array_merge($params, [$pagination->perPage, $pagination->offset]);
        $stmt->execute($execParams);
        $ordens = $stmt->fetchAll();

        View::render('os/index', [
            'pageTitle' => 'Minhas OS',
            'ordens' => $ordens,
            'q' => $q,
            'status' => $status,
            'atrasados' => 0,
            'isSapateiro' => true,
            'pagination' => $pagination,
        ]);
    }

    public function edit(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente', 'Sapateiro']);

        $id = (int) Request::input('id', 0);
        $user = Auth::user();
        $perfil = (string) ($user['perfil'] ?? '');

        $stmt = DB::pdo()->prepare(
            'SELECT os.*, c.nome as cliente_nome, c.telefone as cliente_telefone 
             FROM ordens_servico os 
             JOIN clientes c ON os.cliente_id = c.id 
             WHERE os.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $os = $stmt->fetch();

        if (!$os) {
            Flash::add('error', 'Ordem de Serviço não encontrada.');
            Response::redirect('/os');
        }

        if ($perfil === 'Sapateiro' && (int) $os['sapateiro_id'] !== (int) $user['id']) {
            Flash::add('error', 'Você não tem acesso a esta OS.');
            Response::redirect('/os');
        }

        $sapatos = $this->getSapatosDaOS($id);
        $sapateiros = $this->getSapateirosParaSelect();
        $linkPublico = LinkPublicoService::buscarOuCriar('ordem_servico', $id);

        $stmt = DB::pdo()->prepare('SELECT * FROM pagamentos WHERE os_id = :os_id ORDER BY parcela_numero ASC, created_at ASC');
        $stmt->execute(['os_id' => $id]);
        $pagamentos = $stmt->fetchAll();

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as pago FROM pagamentos WHERE os_id = :os_id AND status = :status');
        $stmt->execute(['os_id' => $id, 'status' => 'Pago']);
        $valorPago = (float) (($stmt->fetch()['pago'] ?? 0));
        $valorTotal = (float) ($os['valor_total'] ?? 0);
        $valorRestante = max(0, $valorTotal - $valorPago);

        View::render('os/form', [
            'pageTitle' => 'OS ' . $os['numero'],
            'os' => $os,
            'sapatos' => $sapatos,
            'sapateiros' => $sapateiros,
            'linkPublico' => $linkPublico,
            'isSapateiro' => $perfil === 'Sapateiro',
            'pagamentos' => $pagamentos,
            'valorPago' => $valorPago,
            'valorRestante' => $valorRestante,
        ]);
    }

    public function update(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente', 'Sapateiro']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $user = Auth::user();
        $perfil = (string) ($user['perfil'] ?? '');

        $stmt = DB::pdo()->prepare('SELECT * FROM ordens_servico WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'OS não encontrada.');
            Response::redirect('/os');
        }

        if ($perfil === 'Sapateiro' && (int) $before['sapateiro_id'] !== (int) $user['id']) {
            Flash::add('error', 'Sem permissão.');
            Response::redirect('/os');
        }

        $status = trim((string) Request::input('status', ''));
        $sapateiroId = (int) Request::input('sapateiro_id', 0);
        $prazoEntrega = (string) Request::input('prazo_entrega', '');
        $localizacao = trim((string) Request::input('localizacao', ''));
        $observacoes = trim((string) Request::input('observacoes', ''));

        $dataConclusao = $before['data_conclusao'];
        if ($status === 'Aguardando retirada' && !$dataConclusao) {
            $dataConclusao = date('Y-m-d');
        }

        if ($perfil === 'Sapateiro') {
            $stmt = DB::pdo()->prepare(
                'UPDATE ordens_servico SET status = :status, localizacao = :localizacao, observacoes = :observacoes, data_conclusao = :data_conclusao, updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute([
                'status' => $status,
                'localizacao' => $localizacao,
                'observacoes' => $observacoes,
                'data_conclusao' => $dataConclusao,
                'id' => $id,
            ]);
        } else {
            $stmt = DB::pdo()->prepare(
                'UPDATE ordens_servico SET status = :status, sapateiro_id = :sapateiro_id, prazo_entrega = :prazo_entrega, localizacao = :localizacao, observacoes = :observacoes, data_conclusao = :data_conclusao, updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute([
                'status' => $status,
                'sapateiro_id' => $sapateiroId > 0 ? $sapateiroId : null,
                'prazo_entrega' => $prazo_entrega ?: $before['prazo_entrega'],
                'localizacao' => $localizacao,
                'observacoes' => $observacoes,
                'data_conclusao' => $dataConclusao,
                'id' => $id,
            ]);
        }

        AuditoriaService::log(Auth::user(), 'os_atualizada', 'ordens_servico', $id, $before, [
            'status' => $status,
            'sapateiro_id' => $sapateiroId,
        ]);

        Flash::add('success', 'OS atualizada.');
        Response::redirect('/os/edit?id=' . $id);
    }

    public function etiqueta(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        $id = (int) Request::input('id', 0);
        $stmt = DB::pdo()->prepare(
            'SELECT os.*, c.nome as cliente_nome, c.telefone as cliente_telefone 
             FROM ordens_servico os 
             JOIN clientes c ON os.cliente_id = c.id 
             WHERE os.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $os = $stmt->fetch();

        if (!$os) {
            Flash::add('error', 'OS não encontrada.');
            Response::redirect('/os');
        }

        $sapatos = $this->getSapatosDaOS($id);

        // Buscar dados da empresa
        $stmt = DB::pdo()->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch() ?: [
            'nome' => 'Sapataria Modelo',
            'cnpj' => '00.000.000/0001-00',
            'endereco' => 'Rua Exemplo, 123 - Centro',
            'telefone' => '(00) 0000-0000',
        ];

        View::render('os/etiqueta', [
            'pageTitle' => 'Etiqueta ' . $os['numero'],
            'os' => $os,
            'sapatos' => $sapatos,
            'empresa' => $empresa,
        ], false);
    }

    private function getSapatosDaOS(int $osId): array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE os_id = :id ORDER BY id');
        $stmt->execute(['id' => $osId]);
        return $stmt->fetchAll();
    }

    private function getSapateirosParaSelect(): array
    {
        $stmt = DB::pdo()->query("SELECT id, nome FROM usuarios WHERE perfil = 'Sapateiro' AND ativo = 1 ORDER BY nome");
        return $stmt->fetchAll();
    }
}
