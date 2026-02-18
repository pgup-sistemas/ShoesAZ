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
.dashboard-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}
.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.stat-number {
  font-size: 1.8rem;
  font-weight: 700;
  line-height: 1;
}
@media (min-width: 768px) {
  .stat-number {
    font-size: 2.2rem;
  }
}
.stat-label {
  font-size: 0.8rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}
.metric-card {
  border-left: 4px solid;
  background: #fff;
}
.metric-card.primary { border-left-color: #008bcd; }
.metric-card.success { border-left-color: #28a745; }
.metric-card.warning { border-left-color: #ffc107; }
.metric-card.danger { border-left-color: #dc3545; }
.metric-card.info { border-left-color: #17a2b8; }

.alert-card {
  border-radius: 10px;
  border: none;
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
.os-list-item {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}
.os-list-item:hover {
  background: #f8f9fa;
}
.os-list-item:last-child {
  border-bottom: none;
}
.quick-action-btn {
  padding: 8px 16px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  font-size: 0.875rem;
}
@media (max-width: 576px) {
  .quick-action-btn {
    padding: 6px 12px;
    font-size: 0.8rem;
  }
}
.quick-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}
</style>

<div class="container-fluid px-2 px-md-4 py-3 py-md-4">
  <!-- Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
      <h4 class="mb-1 fw-bold">Dashboard</h4>
      <p class="text-muted mb-0 small">Visão geral • <?= date('d/m/Y') ?></p>
    </div>
    <div class="d-flex gap-2 w-100 w-md-auto">
      <a href="<?= \App\Core\View::url('/orcamentos/create') ?>" class="btn btn-primary btn-sm flex-fill flex-md-grow-0">
        + Orçamento
      </a>
      <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-success btn-sm flex-fill flex-md-grow-0">
        + OS
      </a>
    </div>
  </div>

  <!-- ALERTAS PRIORITÁRIOS -->
  <?php if ($osAtrasadasCount > 0 || $osHojeCount > 0 || $osAmanhaCount > 0): ?>
  <div class="row g-3 mb-4">
    <?php if ($osAtrasadasCount > 0): ?>
    <div class="col-md-4">
      <div class="card alert-card atrasada">
        <div class="card-body d-flex align-items-center py-3">
          <div class="flex-shrink-0 me-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <span class="fs-4">⚠️</span>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="fs-3 fw-bold"><?= $osAtrasadasCount ?></div>
            <div class="small">OS Atrasada<?= $osAtrasadasCount > 1 ? 's' : '' ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($osHojeCount > 0): ?>
    <div class="col-md-4">
      <div class="card alert-card hoje">
        <div class="card-body d-flex align-items-center py-3">
          <div class="flex-shrink-0 me-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <span class="fs-4">📅</span>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="fs-3 fw-bold"><?= $osHojeCount ?></div>
            <div class="small">Entrega<?= $osHojeCount > 1 ? 's' : '' ?> Hoje</div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($osAmanhaCount > 0): ?>
    <div class="col-md-4">
      <div class="card alert-card amanha">
        <div class="card-body d-flex align-items-center py-3">
          <div class="flex-shrink-0 me-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <span class="fs-4">⏰</span>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="fs-3 fw-bold"><?= $osAmanhaCount ?></div>
            <div class="small">Entrega<?= $osAmanhaCount > 1 ? 's' : '' ?> Amanhã</div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- MÉTRICAS PRINCIPAIS -->
  <div class="row g-3 mb-4 align-items-stretch">
    <!-- Coluna OS -->
    <div class="col-12 col-lg-6 d-flex">
      <div class="card dashboard-card flex-fill d-flex flex-column">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold">📦 Ordens de Serviço</h5>
          <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-sm btn-outline-primary">Ver Todas</a>
        </div>
        <div class="card-body">
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="metric-card primary p-3 rounded bg-light">
                <div class="stat-number text-primary"><?= $totalOS ?></div>
                <div class="stat-label">Em Aberto</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="metric-card warning p-3 rounded bg-light">
                <div class="stat-number text-warning"><?= (int) ($stats['os_em_reparo'] ?? 0) ?></div>
                <div class="stat-label">Em Reparo</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="metric-card success p-3 rounded bg-light">
                <div class="stat-number text-success"><?= (int) ($stats['os_aguardando_retirada'] ?? 0) ?></div>
                <div class="stat-label">Prontas</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="metric-card info p-3 rounded bg-light">
                <div class="stat-number text-info"><?= (int) ($stats['orcamentos_pendentes'] ?? 0) ?></div>
                <div class="stat-label">Orçamentos</div>
              </div>
            </div>
          </div>

          <?php if (!empty($osAtrasadas)): ?>
          <div class="mb-3">
            <h6 class="text-danger fw-bold mb-2">⚠️ Atrasadas</h6>
            <div class="border rounded overflow-hidden">
              <?php foreach (array_slice($osAtrasadas, 0, 3) as $os): ?>
              <div class="os-list-item d-flex justify-content-between align-items-center">
                <div>
                  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $os['id'] ?>" class="fw-bold text-decoration-none">
                    <?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <div class="small text-muted"><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="text-end">
                  <span class="badge bg-danger">Atrasada</span>
                  <div class="small text-danger"><?= date('d/m', strtotime((string) $os['prazo_entrega'])) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($osHoje)): ?>
          <div>
            <h6 class="text-primary fw-bold mb-2">📅 Entregas Hoje</h6>
            <div class="border rounded overflow-hidden">
              <?php foreach (array_slice($osHoje, 0, 3) as $os): ?>
              <div class="os-list-item d-flex justify-content-between align-items-center">
                <div>
                  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $os['id'] ?>" class="fw-bold text-decoration-none">
                    <?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <div class="small text-muted"><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <span class="badge bg-primary"><?= htmlspecialchars((string) $os['status'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Coluna Financeiro -->
    <div class="col-12 col-lg-3 d-flex flex-column gap-3 mt-4 mt-lg-0">
      <div class="card dashboard-card flex-fill d-flex flex-column">
        <div class="card-header bg-white border-bottom py-3;">
          <h5 class="mb-0 fw-bold">💵 Financeiro</h5>
        </div>
        <div class="card-body">
          <div class="text-center mb-3">
            <div class="text-muted small mb-1">Receitas Hoje</div>
            <div class="fs-2 fw-bold text-success">R$ <?= number_format($receitasHoje, 2, ',', '.') ?></div>
          </div>
          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Caixa</span>
            <span class="badge bg-<?= ($caixaHoje && (string) $caixaHoje['status'] === 'Aberto') ? 'success' : 'secondary' ?>">
              <?= $caixaHoje ? (($caixaHoje['status'] ?? '') === 'Aberto' ? 'Aberto' : 'Fechado') : 'Não Aberto' ?>
            </span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">Saldo</span>
            <span class="fw-bold">R$ <?= number_format((float) ($caixaHoje['saldo_esperado'] ?? 0), 2, ',', '.') ?></span>
          </div>
          <a href="<?= \App\Core\View::url('/caixa') ?>" class="btn btn-outline-success btn-sm w-100">
            Abrir Caixa
          </a>
        </div>
      </div>

      <div class="card dashboard-card flex-fill d-flex flex-column">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="mb-0 fw-bold">👥 Clientes</h5>
        </div>
        <div class="card-body">
          <div class="row g-2 text-center">
            <div class="col-6">
              <div class="p-2 bg-light rounded">
                <div class="fs-4 fw-bold text-primary"><?= (int) ($stats['clientes'] ?? 0) ?></div>
                <small class="text-muted">Total</small>
              </div>
            </div>
            <div class="col-6">
              <a class="text-decoration-none" href="<?= \App\Core\View::url('/contas-receber') ?>">
                <div class="p-2 bg-light rounded">
                  <div class="fs-4 fw-bold text-warning"><?= (int) ($stats['contas_receber_clientes'] ?? 0) ?></div>
                  <small class="text-muted">A Receber</small>
                </div>
              </a>
            </div>
            <div class="col-12">
              <a class="text-decoration-none" href="<?= \App\Core\View::url('/contas-receber') ?>?atrasados=1">
                <div class="p-2 bg-light rounded">
                  <div class="fs-4 fw-bold text-danger"><?= (int) ($stats['inadimplentes'] ?? 0) ?></div>
                  <small class="text-muted">Inadimplentes (Atraso)</small>
                </div>
              </a>
            </div>
          </div>

          <?php if (((float) ($stats['contas_receber_total'] ?? 0)) > 0 || ((float) ($stats['valor_inadimplencia'] ?? 0)) > 0): ?>
            <div class="mt-3 p-2 bg-light rounded text-center">
              <?php if (((float) ($stats['contas_receber_total'] ?? 0)) > 0): ?>
                <div class="small fw-bold text-warning">A receber: R$ <?= number_format((float) ($stats['contas_receber_total'] ?? 0), 2, ',', '.') ?></div>
              <?php endif; ?>
              <?php if (((float) ($stats['valor_inadimplencia'] ?? 0)) > 0): ?>
                <div class="small fw-bold text-danger">Em atraso: R$ <?= number_format((float) ($stats['valor_inadimplencia'] ?? 0), 2, ',', '.') ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Coluna Resumo Mensal -->
    <div class="col-12 col-lg-3 d-flex flex-column mt-4 mt-lg-0">
      <div class="card dashboard-card flex-fill d-flex flex-column">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="mb-0 fw-bold">📈 Resumo do Mês</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="text-muted small">Lucro</div>
            <div class="fs-4 fw-bold text-success">R$ <?= number_format((float)($stats['lucro_mes'] ?? 0), 2, ',', '.') ?></div>
          </div>
          <div class="mb-3">
            <div class="text-muted small">Despesas</div>
            <div class="fs-5 fw-bold text-danger">R$ <?= number_format((float)($stats['despesas_mes'] ?? 0), 2, ',', '.') ?></div>
          </div>
          <div>
            <div class="text-muted small">OS Finalizadas</div>
            <div class="fs-5 fw-bold text-primary"><?= (int)($stats['os_finalizadas_mes'] ?? 0) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ACESSO RÁPIDO -->
  <div class="card dashboard-card mt-4">
    <div class="card-body py-3">
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="<?= \App\Core\View::url('/clientes') ?>" class="btn btn-outline-primary quick-action-btn btn-sm">
          🔍 Clientes
        </a>
        <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-outline-info quick-action-btn btn-sm">
          📋 OS
        </a>
        <a href="<?= \App\Core\View::url('/orcamentos') ?>" class="btn btn-outline-warning quick-action-btn btn-sm">
          📄 Orçamentos
        </a>
        <a href="<?= \App\Core\View::url('/relatorios') ?>" class="btn btn-outline-secondary quick-action-btn btn-sm">
          📊 Relatórios
        </a>
        <a href="<?= \App\Core\View::url('/despesas') ?>" class="btn btn-outline-danger quick-action-btn btn-sm">
          💸 Despesas
        </a>
      </div>
    </div>
  </div>
  <!-- Fim container-fluid -->
</div>

