<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;
use App\Services\LinkPublicoService;

final class PublicoController
{
    public function visualizar(): void
    {
        $token = $_GET['token'] ?? '';

        if (!$token) {
            http_response_code(404);
            echo 'Link não encontrado';
            return;
        }

        $link = LinkPublicoService::buscarPorToken($token);

        if (!$link) {
            http_response_code(404);
            echo 'Link expirado ou inválido';
            return;
        }

        LinkPublicoService::registrarAcesso((int) $link['id']);

        $tipo = (string) $link['tipo'];
        $referenciaId = (int) $link['referencia_id'];

        switch ($tipo) {
            case 'orcamento':
                $this->visualizarOrcamento($referenciaId);
                break;
            case 'ordem_servico':
                $this->visualizarOS($referenciaId);
                break;
            case 'recibo':
                $this->visualizarRecibo($referenciaId);
                break;
            default:
                http_response_code(404);
                echo 'Conteúdo não disponível';
        }
    }

    private function visualizarOrcamento(int $id): void
    {
        $stmt = DB::pdo()->prepare(
            'SELECT o.*, c.nome as cliente_nome, c.telefone as cliente_telefone 
             FROM orcamentos o 
             JOIN clientes c ON o.cliente_id = c.id 
             WHERE o.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $orcamento = $stmt->fetch();

        if (!$orcamento) {
            http_response_code(404);
            echo 'Orçamento não encontrado';
            return;
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE orcamento_id = :id ORDER BY id');
        $stmt->execute(['id' => $id]);
        $sapatos = $stmt->fetchAll();

        View::render('publico/orcamento', [
            'pageTitle' => 'Orçamento ' . $orcamento['numero'],
            'orcamento' => $orcamento,
            'sapatos' => $sapatos,
            'layout' => 'public',
        ], false);
    }

    private function visualizarOS(int $id): void
    {
        $stmt = DB::pdo()->prepare(
            'SELECT os.*, c.nome as cliente_nome, c.telefone as cliente_telefone, u.nome as sapateiro_nome
             FROM ordens_servico os 
             JOIN clientes c ON os.cliente_id = c.id 
             LEFT JOIN usuarios u ON os.sapateiro_id = u.id
             WHERE os.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $os = $stmt->fetch();

        if (!$os) {
            http_response_code(404);
            echo 'Ordem de Serviço não encontrada';
            return;
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE os_id = :id ORDER BY id');
        $stmt->execute(['id' => $id]);
        $sapatos = $stmt->fetchAll();

        View::render('publico/os', [
            'pageTitle' => 'OS ' . $os['numero'],
            'os' => $os,
            'sapatos' => $sapatos,
            'layout' => 'public',
        ], false);
    }

    private function visualizarRecibo(int $id): void
    {
        $stmt = DB::pdo()->prepare(
            'SELECT r.*, os.numero as os_numero, os.data_entrada, c.nome as cliente_nome, c.cpf as cliente_cpf, c.telefone as cliente_telefone
             FROM recibos r 
             JOIN ordens_servico os ON r.os_id = os.id 
             JOIN clientes c ON os.cliente_id = c.id 
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $recibo = $stmt->fetch();

        if (!$recibo) {
            http_response_code(404);
            echo 'Recibo não encontrado';
            return;
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM pagamentos WHERE os_id = :os_id AND status = :status ORDER BY parcela_numero');
        $stmt->execute(['os_id' => $recibo['os_id'], 'status' => 'Pago']);
        $pagamentos = $stmt->fetchAll();

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE os_id = :os_id ORDER BY id');
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

        View::render('publico/recibo', [
            'pageTitle' => 'Recibo ' . $recibo['numero'],
            'recibo' => $recibo,
            'pagamentos' => $pagamentos,
            'sapatos' => $sapatos,
            'empresa' => $empresa,
            'layout' => 'public',
        ], false);
    }
}
