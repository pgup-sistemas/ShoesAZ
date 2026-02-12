<?php

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Relatórios</div>
    <div class="text-muted small">Relatórios gerenciais do sistema</div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="fs-1 mb-2">📊</div>
        <h5 class="card-title">Lucro / Prejuízo</h5>
        <p class="card-text text-muted small">Análise financeira com receitas, despesas e resultado.</p>
        <a href="<?= \App\Core\View::url('/relatorios/lucro') ?>" class="btn btn-primary">Ver Relatório</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="fs-1 mb-2">📋</div>
        <h5 class="card-title">Ordens de Serviço</h5>
        <p class="card-text text-muted small">OS por período com status e valores.</p>
        <a href="<?= \App\Core\View::url('/relatorios/os') ?>" class="btn btn-primary">Ver Relatório</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="fs-1 mb-2">👥</div>
        <h5 class="card-title">Clientes</h5>
        <p class="card-text text-muted small">Top clientes e clientes inativos.</p>
        <a href="<?= \App\Core\View::url('/relatorios/clientes') ?>" class="btn btn-primary">Ver Relatório</a>
      </div>
    </div>
  </div>
</div>
