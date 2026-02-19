<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

// Verificar se sistema está instalado - se não, redirecionar para instalador
$lockFile = __DIR__ . '/database/install.lock';
$installedFlag = __DIR__ . '/.installed';

if (!is_file($lockFile) && !is_file($installedFlag)) {
    // Sistema não instalado - redirecionar para instalador
    header('Location: /install.php', true, 302);
    exit;
}

use App\Core\Router;
use App\Controllers\AjudaController;
use App\Controllers\AuthController;
use App\Controllers\BackupController;
use App\Controllers\BuscaController;
use App\Controllers\CaixaController;
use App\Controllers\ClienteController;
use App\Controllers\ConfiguracaoController;
use App\Controllers\DespesaController;
use App\Controllers\OrcamentoController;
use App\Controllers\OSController;
use App\Controllers\PagamentoController;
use App\Controllers\PublicoController;
use App\Controllers\ReciboController;
use App\Controllers\RelatorioController;
use App\Controllers\SapatoController;
use App\Controllers\UsuarioController;
use App\Services\DashboardService;

$router = new Router();

$router->get('/', function () {
    if (!\App\Core\Auth::check()) {
        \App\Core\Response::redirect('/login');
    }

    \App\Core\Breadcrumb::reset();
    \App\Core\Breadcrumb::add('Dashboard');

    // Usar DashboardService para obter dados otimizados
    $user = \App\Core\Auth::user();
    $userRole = (string) ($user['perfil'] ?? 'default');

    $stats = DashboardService::getStats($userRole);
    $osAtrasadas = DashboardService::getOsAtrasadas(3);
    $osHoje = DashboardService::getOsHoje(3);
    $osAmanha = DashboardService::getOsAmanha(3);
    $caixaHoje = DashboardService::getCaixaHoje();

    \App\Core\View::render('dashboard/index', [
        'pageTitle' => 'Dashboard',
        'stats' => $stats,
        'osAtrasadas' => $osAtrasadas,
        'osHoje' => $osHoje,
        'osAmanha' => $osAmanha,
        'caixaHoje' => $caixaHoje,
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
$router->post('/clientes/destroy', [ClienteController::class, 'destroy']);
$router->get('/clientes/buscar', [ClienteController::class, 'buscar']);

$router->get('/orcamentos', [OrcamentoController::class, 'index']);
$router->get('/orcamentos/create', [OrcamentoController::class, 'create']);
$router->post('/orcamentos/store', [OrcamentoController::class, 'store']);
$router->get('/orcamentos/edit', [OrcamentoController::class, 'edit']);
$router->post('/orcamentos/update', [OrcamentoController::class, 'update']);
$router->post('/orcamentos/aprovar', [OrcamentoController::class, 'aprovar']);
$router->post('/orcamentos/converter', [OrcamentoController::class, 'converter']);
$router->get('/orcamentos/imprimir', [OrcamentoController::class, 'imprimir']);

$router->post('/sapatos/store', [SapatoController::class, 'store']);
$router->post('/sapatos/destroy', [SapatoController::class, 'destroy']);
$router->post('/sapatos/upload-foto', [SapatoController::class, 'uploadFoto']);
$router->post('/sapatos/remover-foto', [SapatoController::class, 'removerFoto']);

$router->get('/pagamentos', [PagamentoController::class, 'index']);
$router->get('/pagamentos/create', [PagamentoController::class, 'create']);
$router->post('/pagamentos/store', [PagamentoController::class, 'store']);
$router->post('/pagamentos/quitar', [PagamentoController::class, 'quitar']);

$router->get('/contas-receber', [PagamentoController::class, 'contasReceber']);

$router->get('/despesas', [DespesaController::class, 'index']);
$router->get('/despesas/create', [DespesaController::class, 'create']);
$router->post('/despesas/store', [DespesaController::class, 'store']);
$router->get('/despesas/edit', [DespesaController::class, 'edit']);
$router->post('/despesas/update', [DespesaController::class, 'update']);
$router->post('/despesas/delete', [DespesaController::class, 'delete']);

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

$router->get('/relatorios', [RelatorioController::class, 'index']);
$router->get('/relatorios/lucro', [RelatorioController::class, 'lucro']);
$router->get('/relatorios/lucro/csv', [RelatorioController::class, 'exportarLucroCsv']);
$router->get('/relatorios/os', [RelatorioController::class, 'os']);
$router->get('/relatorios/os/csv', [RelatorioController::class, 'exportarOsCsv']);
$router->get('/relatorios/clientes', [RelatorioController::class, 'clientes']);

$router->get('/backup', [BackupController::class, 'index']);
$router->post('/backup/create', [BackupController::class, 'create']);
$router->get('/backup/download', [BackupController::class, 'download']);
$router->post('/backup/delete', [BackupController::class, 'delete']);

$router->get('/configuracoes/empresa', [ConfiguracaoController::class, 'empresa']);
$router->post('/configuracoes/empresa', [ConfiguracaoController::class, 'updateEmpresa']);

$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/create', [UsuarioController::class, 'create']);
$router->post('/usuarios/store', [UsuarioController::class, 'store']);
$router->get('/usuarios/edit', [UsuarioController::class, 'edit']);
$router->post('/usuarios/update', [UsuarioController::class, 'update']);

$router->get('/busca', [BuscaController::class, 'index']);

$router->get('/ajuda', [AjudaController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
