<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\AjudaController;
use App\Controllers\AuthController;
use App\Controllers\CaixaController;
use App\Controllers\ClienteController;
use App\Controllers\ConfiguracaoController;
use App\Controllers\DespesaController;
use App\Controllers\OrcamentoController;
use App\Controllers\OSController;
use App\Controllers\PagamentoController;
use App\Controllers\PublicoController;
use App\Controllers\ReciboController;
use App\Controllers\SapatoController;
use App\Controllers\UsuarioController;

$router = new Router();

// Evita 404s no console do navegador para /favicon.ico
$router->get('/favicon.ico', function () {
    http_response_code(204);
    exit;
});

$router->get('/', function () {
    if (!\App\Core\Auth::check()) {
        \App\Core\Response::redirect('/login');
    }
    \App\Core\View::render('dashboard/index', [
        'pageTitle' => 'Dashboard',
    ]);
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/recuperar-senha', [AuthController::class, 'showRecuperarSenha']);
$router->post('/recuperar-senha', [AuthController::class, 'recuperarSenha']);
$router->get('/nova-senha', [AuthController::class, 'showNovaSenha']);
$router->post('/nova-senha', [AuthController::class, 'novaSenha']);

$router->get('/clientes', [ClienteController::class, 'index']);
$router->get('/clientes/create', [ClienteController::class, 'create']);
$router->post('/clientes/store', [ClienteController::class, 'store']);
$router->get('/clientes/edit', [ClienteController::class, 'edit']);
$router->post('/clientes/update', [ClienteController::class, 'update']);

$router->get('/orcamentos', [OrcamentoController::class, 'index']);
$router->get('/orcamentos/create', [OrcamentoController::class, 'create']);
$router->post('/orcamentos/store', [OrcamentoController::class, 'store']);
$router->get('/orcamentos/edit', [OrcamentoController::class, 'edit']);
$router->post('/orcamentos/update', [OrcamentoController::class, 'update']);
$router->post('/orcamentos/aprovar', [OrcamentoController::class, 'aprovar']);
$router->post('/orcamentos/converter', [OrcamentoController::class, 'converter']);

$router->post('/sapatos/store', [SapatoController::class, 'store']);
$router->post('/sapatos/destroy', [SapatoController::class, 'destroy']);
$router->post('/sapatos/upload-foto', [SapatoController::class, 'uploadFoto']);
$router->post('/sapatos/remover-foto', [SapatoController::class, 'removerFoto']);

$router->get('/pagamentos', [PagamentoController::class, 'index']);
$router->get('/pagamentos/create', [PagamentoController::class, 'create']);
$router->post('/pagamentos/store', [PagamentoController::class, 'store']);
$router->post('/pagamentos/quitar', [PagamentoController::class, 'quitar']);

$router->get('/despesas', [DespesaController::class, 'index']);
$router->get('/despesas/create', [DespesaController::class, 'create']);
$router->post('/despesas/store', [DespesaController::class, 'store']);
$router->get('/despesas/edit', [DespesaController::class, 'edit']);
$router->post('/despesas/update', [DespesaController::class, 'update']);

$router->get('/caixa', [CaixaController::class, 'index']);
$router->post('/caixa/abrir', [CaixaController::class, 'abrir']);
$router->post('/caixa/fechar', [CaixaController::class, 'fechar']);
$router->post('/caixa/retirada', [CaixaController::class, 'retirada']);
$router->post('/caixa/importar-pagamentos', [CaixaController::class, 'importarPagamentos']);

$router->get('/recibos', [ReciboController::class, 'index']);
$router->get('/recibos/create', [ReciboController::class, 'create']);
$router->post('/recibos/store', [ReciboController::class, 'store']);
$router->get('/recibos/visualizar', [ReciboController::class, 'visualizar']);
$router->get('/recibos/imprimir', [ReciboController::class, 'imprimir']);

$router->get('/os', [OSController::class, 'index']);
$router->get('/os/edit', [OSController::class, 'edit']);
$router->post('/os/update', [OSController::class, 'update']);
$router->get('/os/etiqueta', [OSController::class, 'etiqueta']);

$router->get('/public', [PublicoController::class, 'visualizar']);

$router->get('/configuracoes/empresa', [ConfiguracaoController::class, 'empresa']);
$router->post('/configuracoes/empresa', [ConfiguracaoController::class, 'updateEmpresa']);

$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/create', [UsuarioController::class, 'create']);
$router->post('/usuarios/store', [UsuarioController::class, 'store']);
$router->get('/usuarios/edit', [UsuarioController::class, 'edit']);
$router->post('/usuarios/update', [UsuarioController::class, 'update']);

$router->get('/ajuda', [AjudaController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
