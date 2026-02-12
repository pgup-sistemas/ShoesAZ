<?php

$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-t');
$receitas = $receitas ?? 0;
$despesas = $despesas ?? 0;
$lucro = $lucro ?? 0;
$receitasPorForma = $receitasPorForma ?? [];
$despesasPorCategoria = $despesasPorCategoria ?? [];

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Relatório de Lucro/Prejuízo</div>
    <div class="text-muted small">Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= \App\Core\View::url('/relatorios/lucro/csv') ?>?data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>" class="btn btn-success btn-sm">📊 Exportar Excel</a>
    <a href="<?= \App\Core\View::url('/relatorios') ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/relatorios/lucro') ?>">
  <div class="col-12 col-md-3">
    <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="col-12 col-md-3">
    <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-primary" type="submit">Gerar</button>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card bg-success text-white">
      <div class="card-body text-center">
        <div class="fs-4 fw-bold">R$ <?= number_format($receitas, 2, ',', '.') ?></div>
        <div class="small">Receitas</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-danger text-white">
      <div class="card-body text-center">
        <div class="fs-4 fw-bold">R$ <?= number_format($despesas, 2, ',', '.') ?></div>
        <div class="small">Despesas</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card <?= $lucro >= 0 ? 'bg-primary' : 'bg-warning' ?> text-white">
      <div class="card-body text-center">
        <div class="fs-4 fw-bold">R$ <?= number_format($lucro, 2, ',', '.') ?></div>
        <div class="small"><?= $lucro >= 0 ? 'Lucro' : 'Prejuízo' ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Receitas por Forma de Pagamento</div>
      <div class="card-body">
        <?php if (!$receitasPorForma): ?>
          <div class="text-muted text-center">Nenhuma receita no período</div>
        <?php else: ?>
          <table class="table table-sm">
            <?php foreach ($receitasPorForma as $r): ?>
              <tr>
                <td><?= htmlspecialchars((string) ($r['forma_pagamento'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end">R$ <?= number_format((float) $r['total'], 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Despesas por Categoria</div>
      <div class="card-body">
        <?php if (!$despesasPorCategoria): ?>
          <div class="text-muted text-center">Nenhuma despesa no período</div>
        <?php else: ?>
          <table class="table table-sm">
            <?php foreach ($despesasPorCategoria as $d): ?>
              <tr>
                <td><?= htmlspecialchars((string) $d['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end">R$ <?= number_format((float) $d['total'], 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
