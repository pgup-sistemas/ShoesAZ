<div class="p-3">
  <div class="fw-semibold mb-2">Menu</div>
  <div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/ajuda') ?>"><i class="bi bi-question-circle me-2"></i>Guia de Ajuda</a>
    <?php if (\App\Core\Auth::hasRole(['Administrador', 'Gerente', 'Atendente'])): ?>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/clientes') ?>"><i class="bi bi-people me-2"></i>Clientes</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/orcamentos') ?>"><i class="bi bi-file-text me-2"></i>Orçamentos</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/os') ?>"><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::hasRole(['Sapateiro'])): ?>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/os') ?>"><i class="bi bi-person-workspace me-2"></i>Minhas OS</a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::hasRole(['Administrador', 'Gerente'])): ?>
      <div class="mt-2 mb-1 text-muted small">FINANCEIRO</div>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/caixa') ?>"><i class="bi bi-cash-stack me-2"></i>Caixa</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/contas-receber') ?>"><i class="bi bi-journal-text me-2"></i>Contas a Receber</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/pagamentos') ?>"><i class="bi bi-credit-card me-2"></i>Pagamentos</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/despesas') ?>"><i class="bi bi-receipt-cutoff me-2"></i>Despesas</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/recibos') ?>"><i class="bi bi-receipt me-2"></i>Recibos</a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::hasRole(['Administrador', 'Gerente'])): ?>
      <div class="mt-2 mb-1 text-muted small">RELATÓRIOS</div>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/relatorios') ?>"><i class="bi bi-bar-chart-line me-2"></i>Relatórios</a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::hasRole(['Administrador'])): ?>
      <div class="mt-2 mb-1 text-muted small">CONFIGURAÇÕES</div>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/usuarios') ?>"><i class="bi bi-person-gear me-2"></i>Usuários</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/configuracoes/empresa') ?>"><i class="bi bi-building me-2"></i>Dados da Empresa</a>
      <a class="list-group-item list-group-item-action" href="<?= \App\Core\View::url('/backup') ?>"><i class="bi bi-hdd-stack me-2"></i>Backup</a>
    <?php endif; ?>
  </div>
</div>
