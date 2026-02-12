<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\DB;
use App\Core\View;
use App\Core\Request;

final class BuscaController
{
    public function index(): void
    {
        Authorization::requireLogin();

        $termo = trim((string) Request::input('q', ''));
        
        if ($termo === '') {
            View::render('busca/index', [
                'pageTitle' => 'Busca',
                'termo' => '',
                'clientes' => [],
                'os' => [],
                'orcamentos' => [],
            ]);
            return;
        }

        $db = DB::pdo();
        $param = '%' . $termo . '%';

        // Buscar clientes
        $stmt = $db->prepare("SELECT * FROM clientes 
                             WHERE nome LIKE ? 
                             OR telefone LIKE ? 
                             OR cpf LIKE ? 
                             ORDER BY nome LIMIT 10");
        $stmt->execute([$param, $param, $param]);
        $clientes = $stmt->fetchAll();

        // Buscar OS
        $stmt = $db->prepare("SELECT os.*, c.nome as cliente_nome 
                             FROM ordens_servico os 
                             JOIN clientes c ON os.cliente_id = c.id 
                             WHERE os.numero LIKE ? 
                             OR c.nome LIKE ? 
                             OR os.localizacao LIKE ? 
                             ORDER BY os.id DESC LIMIT 10");
        $stmt->execute([$param, $param, $param]);
        $os = $stmt->fetchAll();

        // Buscar Orçamentos
        $stmt = $db->prepare("SELECT o.*, c.nome as cliente_nome 
                             FROM orcamentos o 
                             JOIN clientes c ON o.cliente_id = c.id 
                             WHERE o.numero LIKE ? 
                             OR c.nome LIKE ? 
                             ORDER BY o.id DESC LIMIT 10");
        $stmt->execute([$param, $param]);
        $orcamentos = $stmt->fetchAll();

        View::render('busca/index', [
            'pageTitle' => 'Resultados da Busca',
            'termo' => $termo,
            'clientes' => $clientes,
            'os' => $os,
            'orcamentos' => $orcamentos,
        ]);
    }
}
