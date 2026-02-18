<?php

$stats = $stats ?? [];
$osAtrasadas = $osAtrasadas ?? [];
$osHoje = $osHoje ?? [];
$osAmanha = $osAmanha ?? [];
$caixaHoje = $caixaHoje ?? null;

// Calcular totais
$totalOS = (int) ($stats['os_abertas'] ?? 0);
$osAtrasadasCount = (int) ($stats['os_atrasadas'] ?? 0);
$osHojeCount = count($osHoje);
$osAmanhaCount = count($osAmanha);
$receitasHoje = (float) ($stats['receitas_hoje'] ?? 0);

?>
<style>
/* === VARIÁVEIS DE DESIGN === */
:root {
  --primary-color: #008bcd;
  --success-color: #28a745;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #17a2b8;
  --light-bg: #f8f9fa;
  --border-radius: 12px;
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
  --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
}

/* === DASHBOARD CARD === */
.dashboard-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: var(--shadow-sm);
  border-radius: var(--border-radius);
  background: #fff;
}

.dashboard-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.dashboard-card .card-header {
  border-bottom: 2px solid var(--light-bg);
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

/* === STAT NUMBERS === */
.stat-number {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1.1;
  color: #1a1a1a;
}

@media (min-width: 576px) {
  .stat-number {
    font-size: 2rem;
  }
}

@media (min-width: 768px) {
  .stat-number {
    font-size: 2.4rem;
  }
}

.stat-label {
  font-size: 0.75rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-top: 6px;
}

/* === METRIC CARD === */
.metric-card {
  border-radius: 10px;
  background: #fff;
  border: 2px solid;
  transition: all 0.3s ease;
  padding: 20px !important;
  text-align: center;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.metric-card.primary { border-color: var(--primary-color); background: rgba(0, 139, 205, 0.04); }
.metric-card.success { border-color: var(--success-color); background: rgba(40, 167, 69, 0.04); }
.metric-card.warning { border-color: var(--warning-color); background: rgba(255, 193, 7, 0.04); }
.metric-card.danger { border-color: var(--danger-color); background: rgba(220, 53, 69, 0.04); }
.metric-card.info { border-color: var(--info-color); background: rgba(23, 162, 184, 0.04); }

/* === ALERT CARDS === */
.alert-card {
  border-radius: var(--border-radius);
  border: none;
  overflow: hidden;
  box-shadow: var(--shadow-md);
  transition: transform 0.3s ease;
}

.alert-card:hover {
  transform: translateY(-3px);
}

.alert-card.atrasada {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}

.alert-card.hoje {
  background: linear-gradient(135deg, #008bcd 0%, #0069d9 100%);
  color: white;
}

.alert-card.amanha {
  background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
  color: #212529;
}

.alert-card .card-body {
  padding: 1.5rem !important;
}

.alert-card-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  background: rgba(255,255,255,0.2);
  flex-shrink: 0;
}

.alert-card-number {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
}

.alert-card-label {
  font-size: 0.9rem;
  opacity: 0.95;
  font-weight: 500;
}

/* === OS LIST ITEM === */
.os-list-item {
  padding: 14px 16px;
  border-bottom: 1px solid #e9ecef;
  transition: background 0.2s, padding-left 0.2s;
  position: relative;
}

.os-list-item:hover {
  background: var(--light-bg);
  padding-left: 20px;
}

.os-list-item:last-child {
  border-bottom: none;
}

.os-numero {
  font-weight: 700;
  font-size: 1.05rem;
  color: #008bcd;
  text-decoration: none;
  transition: color 0.2s;
}

.os-numero:hover {
  color: #0069d9;
  text-decoration: underline;
}

.os-cliente {
  font-size: 0.9rem;
  color: #6c757d;
  margin-top: 4px;
}

.os-status {
  font-weight: 600;
  font-size: 0.85rem;
  padding: 6px 12px;
}

.os-data {
  font-size: 0.85rem;
  font-weight: 500;
}

/* === QUICK ACTION BUTTONS === */
.quick-action-btn {
  padding: 10px 18px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s;
  font-size: 0.9rem;
  border: 2px solid;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.quick-action-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

@media (max-width: 576px) {
  .quick-action-btn {
    padding: 8px 14px;
    font-size: 0.8rem;
  }
}

/* === HEADER SECTION === */
.dashboard-header {
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 800;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  font-size: 0.95rem;
  color: #6c757d;
  margin: 0;
}

@media (max-width: 576px) {
  .dashboard-title {
    font-size: 1.6rem;
  }
  
  .dashboard-subtitle {
    font-size: 0.85rem;
  }
}

/* === ACTION BUTTONS === */
.dashboard-actions {
  gap: 12px;
}

.dashboard-actions .btn {
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 8px;
  transition: all 0.3s;
  white-space: nowrap;
}

.dashboard-actions .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

@media (max-width: 576px) {
  .dashboard-actions {
    flex-wrap: wrap;
  }
  
  .dashboard-actions .btn {
    flex: 1;
    min-width: 140px;
  }
}

/* === SECTION TITLES === */
.section-title {
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title.danger { color: var(--danger-color); }
.section-title.primary { color: var(--primary-color); }
.section-title.success { color: var(--success-color); }

/* === RESPONSIVE GRID === */
@media (max-width: 992px) {
  .col-lg-6,
  .col-lg-3 {
    flex: 0 0 100%;
  }
}

/* === FOOTER QUICK ACCESS === */
.quick-access-section {
  background: var(--light-bg);
  border-radius: var(--border-radius);
  padding: 1.5rem;
}

.quick-access-section .btn {
  flex: 1;
  min-height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  gap: 8px;
}

@media (max-width: 576px) {
  .quick-access-section {
    padding: 1rem;
  }
  
  .quick-access-section .btn {
    min-height: 44px;
    font-size: 0.85rem;
    padding: 8px 12px;
  }
}

/* === COLOR UTILITIES === */
.text-success-strong { color: var(--success-color); font-weight: 700; }
.text-danger-strong { color: var(--danger-color); font-weight: 700; }
.text-primary-strong { color: var(--primary-color); font-weight: 700; }
.text-warning-strong { color: var(--warning-color); font-weight: 700; }

/* === NO DATA STATE === */
.no-data {
  text-align: center;
  padding: 2rem 1rem;
  color: #6c757d;
}

.no-data-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}
</style>

<div class="container-fluid px-3 px-md-4 py-4">
  <!-- HEADER -->
  <div class="dashboard-header mb-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
      <div>
        <h1 class="dashboard-title">📊 Dashboard</h1>
        <p class="dashboard-subtitle">Visão geral do seu negócio • <?= date('d \d\e F \d\e Y', strtotime(date('Y-m-d'))) ?></p>
      </div>
      <div class="d-flex gap-2 dashboard-actions">
        <a href="<?= \App\Core\View::url('/orcamentos/create') ?>" class="btn btn-primary">
          <span>📄</span> Novo Orçamento
        </a>
        <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-success">
          <span>✓</span> Nova OS
        </a>
      </div>
    </div>
  </div>

  <!-- ALERTAS PRIORITÁRIOS -->
  <?php if ($osAtrasadasCount > 0 || $osHojeCount > 0 || $osAmanhaCount > 0): ?>
  <div class="row g-3 mb-4">
    <?php if ($osAtrasadasCount > 0): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card alert-card atrasada h-100">
        <div class="card-body d-flex align-items-center">
          <div class="alert-card-icon me-3">
            <span>⚠️</span>
          </div>
          <div class="flex-grow-1">
            <div class="alert-card-number"><?= $osAtrasadasCount ?></div>
            <div class="alert-card-label">OS Atrasada<?= $osAtrasadasCount > 1 ? 's' : '' ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($osHojeCount > 0): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card alert-card hoje h-100">
        <div class="card-body d-flex align-items-center">
          <div class="alert-card-icon me-3">
            <span>📅</span>
          </div>
          <div class="flex-grow-1">
            <div class="alert-card-number"><?= $osHojeCount ?></div>
            <div class="alert-card-label">Entrega<?= $osHojeCount > 1 ? 's' : '' ?> Hoje</div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($osAmanhaCount > 0): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card alert-card amanha h-100">
        <div class="card-body d-flex align-items-center">
          <div class="alert-card-icon me-3">
            <span>⏰</span>
          </div>
          <div class="flex-grow-1">
            <div class="alert-card-number"><?= $osAmanhaCount ?></div>
            <div class="alert-card-label">Entrega<?= $osAmanhaCount > 1 ? 's' : '' ?> Amanhã</div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- MÉTRICAS PRINCIPAIS -->
  <div class="row g-3 mb-4">
    <!-- OS E FINANCEIRO -->
    <div class="col-12 col-lg-8 d-flex">
      <div class="card dashboard-card flex-fill d-flex flex-column w-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold">📦 Ordens de Serviço</h5>
          <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-sm btn-outline-primary">Ver Todas →</a>
        </div>
        <div class="card-body">
          <!-- Métricas de OS -->
          <div class="row g-2 mb-4">
            <div class="col-6 col-sm-3">
              <div class="metric-card primary">
                <div class="stat-number text-primary"><?= $totalOS ?></div>
                <div class="stat-label">Em Aberto</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="metric-card warning">
                <div class="stat-number text-warning"><?= (int) ($stats['os_em_reparo'] ?? 0) ?></div>
                <div class="stat-label">Em Reparo</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="metric-card success">
                <div class="stat-number text-success"><?= (int) ($stats['os_aguardando_retirada'] ?? 0) ?></div>
                <div class="stat-label">Prontas</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="metric-card info">
                <div class="stat-number text-info"><?= (int) ($stats['orcamentos_pendentes'] ?? 0) ?></div>
                <div class="stat-label">Orçamentos</div>
              </div>
            </div>
          </div>

          <!-- OS Atrasadas -->
          <?php if (!empty($osAtrasadas)): ?>
          <div class="mb-3">
            <h6 class="section-title danger">⚠️ Atrasadas</h6>
            <div class="border rounded-2 overflow-hidden">
              <?php foreach (array_slice($osAtrasadas, 0, 3) as $os): ?>
              <div class="os-list-item d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $os['id'] ?>" class="os-numero">
                    #<?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <div class="os-cliente"><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="text-end">
                  <span class="badge bg-danger os-status">Atrasada</span>
                  <div class="os-data text-danger">Prazo: <?= date('d/m', strtotime((string) $os['prazo_entrega'])) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- OS Hoje -->
          <?php if (!empty($osHoje)): ?>
          <div>
            <h6 class="section-title primary">📅 Entregas Hoje</h6>
            <div class="border rounded-2 overflow-hidden">
              <?php foreach (array_slice($osHoje, 0, 3) as $os): ?>
              <div class="os-list-item d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $os['id'] ?>" class="os-numero">
                    #<?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <div class="os-cliente"><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <span class="badge bg-primary os-status"><?= htmlspecialchars((string) $os['status'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (empty($osAtrasadas) && empty($osHoje)): ?>
          <div class="no-data">
            <div class="no-data-icon">✓</div>
            <p>Nenhuma OS pendente no momento!</p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- FINANCEIRO + CLIENTES -->
    <div class="col-12 col-lg-4 d-flex flex-column gap-3">
      <!-- Card Financeiro -->
      <div class="card dashboard-card flex-fill">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">💵 Financeiro</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="text-muted small mb-1">Receitas Hoje</div>
            <div class="fs-3 fw-bold text-success-strong">R$ <?= number_format($receitasHoje, 2, ',', '.') ?></div>
          </div>
          <hr class="my-3">
          
          <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small">Status do Caixa</span>
              <span class="badge bg-<?= ($caixaHoje && (string) $caixaHoje['status'] === 'Aberto') ? 'success' : 'secondary' ?>">
                <?= $caixaHoje ? (($caixaHoje['status'] ?? '') === 'Aberto' ? 'Aberto' : 'Fechado') : 'Não Aberto' ?>
              </span>
            </div>
          </div>

          <div class="mb-3 p-2 bg-light rounded">
            <div class="text-muted small">Saldo Esperado</div>
            <div class="fs-5 fw-bold text-primary-strong">R$ <?= number_format((float) ($caixaHoje['saldo_esperado'] ?? 0), 2, ',', '.') ?></div>
          </div>

          <a href="<?= \App\Core\View::url('/caixa') ?>" class="btn btn-success w-100">
            🔓 Abrir Caixa
          </a>
        </div>
      </div>

      <!-- Card Clientes -->
      <div class="card dashboard-card flex-fill">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">👥 Clientes</h5>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-3 text-center">
            <div class="col-6">
              <div class="p-3 bg-light rounded">
                <div class="fs-4 fw-bold text-primary"><?= (int) ($stats['clientes'] ?? 0) ?></div>
                <small class="text-muted">Total</small>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 bg-light rounded">
                <div class="fs-4 fw-bold text-warning"><?= (int) ($stats['contas_receber_clientes'] ?? 0) ?></div>
                <small class="text-muted">A Receber</small>
              </div>
            </div>
          </div>

          <?php if (((float) ($stats['contas_receber_total'] ?? 0)) > 0 || ((float) ($stats['valor_inadimplencia'] ?? 0)) > 0): ?>
            <a href="<?= \App\Core\View::url('/contas-receber') ?>" class="d-block p-3 bg-light rounded text-decoration-none mb-2 hover:bg-lighter transition-all">
              <div class="small fw-bold text-warning mb-1">📊 A receber</div>
              <div class="fs-5 fw-bold text-warning">R$ <?= number_format((float) ($stats['contas_receber_total'] ?? 0), 2, ',', '.') ?></div>
            </a>
          <?php endif; ?>

          <?php if (((float) ($stats['valor_inadimplencia'] ?? 0)) > 0): ?>
            <a href="<?= \App\Core\View::url('/contas-receber') ?>?atrasados=1" class="d-block p-3 bg-light rounded text-decoration-none hover:bg-lighter transition-all">
              <div class="small fw-bold text-danger mb-1">⚠️ Em atraso</div>
              <div class="fs-5 fw-bold text-danger">R$ <?= number_format((float) ($stats['valor_inadimplencia'] ?? 0), 2, ',', '.') ?></div>
            </a>
          <?php endif; ?>

          <a href="<?= \App\Core\View::url('/contas-receber') ?>" class="btn btn-outline-primary w-100 mt-2 btn-sm">
            Ver Contas a Receber
          </a>
        </div>
      </div>

      <!-- Card Resumo do Mês -->
      <div class="card dashboard-card flex-fill">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">📈 Este Mês</h5>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-12">
              <div class="p-3 bg-light rounded">
                <div class="text-muted small mb-1">Lucro</div>
                <div class="fs-5 fw-bold text-success-strong">R$ <?= number_format((float)($stats['lucro_mes'] ?? 0), 2, ',', '.') ?></div>
              </div>
            </div>
            <div class="col-12">
              <div class="p-3 bg-light rounded">
                <div class="text-muted small mb-1">Despesas</div>
                <div class="fs-5 fw-bold text-danger-strong">R$ <?= number_format((float)($stats['despesas_mes'] ?? 0), 2, ',', '.') ?></div>
              </div>
            </div>
            <div class="col-12">
              <div class="p-3 bg-light rounded">
                <div class="text-muted small mb-1">OS Finalizadas</div>
                <div class="fs-5 fw-bold text-primary-strong"><?= (int)($stats['os_finalizadas_mes'] ?? 0) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ACESSO RÁPIDO / FOOTER -->
  <div class="quick-access-section mt-4">
    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <a href="<?= \App\Core\View::url('/clientes') ?>" class="btn btn-outline-primary quick-action-btn">
        🔍 Clientes
      </a>
      <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-outline-info quick-action-btn">
        📋 Ordens
      </a>
      <a href="<?= \App\Core\View::url('/orcamentos') ?>" class="btn btn-outline-warning quick-action-btn">
        📄 Orçamentos
      </a>
      <a href="<?= \App\Core\View::url('/relatorios') ?>" class="btn btn-outline-secondary quick-action-btn">
        📊 Relatórios
      </a>
      <a href="<?= \App\Core\View::url('/despesas') ?>" class="btn btn-outline-danger quick-action-btn">
        💸 Despesas
      </a>
      <a href="<?= \App\Core\View::url('/pagamentos') ?>" class="btn btn-outline-success quick-action-btn">
        💰 Pagamentos
      </a>
    </div>
  </div>
</div>

