<?php

$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-t');
$ordens = $ordens ?? [];
$porStatus = $porStatus ?? [];
$valorTotal = $valorTotal ?? 0;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Relatório de Ordens de Serviço</div>
    <div class="text-muted small">Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= \App\Core\View::url('/relatorios/os/csv') ?>?data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>" class="btn btn-success btn-sm">📊 Exportar Excel</a>
    <a href="<?= \App\Core\View::url('/relatorios') ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/relatorios/os') ?>">
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
  <div class="col-md-6">
    <div class="card bg-primary text-white">
      <div class="card-body text-center">
        <div class="fs-4 fw-bold"><?= count($ordens) ?></div>
        <div class="small">Total de OS</div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card bg-success text-white">
      <div class="card-body text-center">
        <div class="fs-4 fw-bold">R$ <?= number_format($valorTotal, 2, ',', '.') ?></div>
        <div class="small">Valor Total</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">Por Status</div>
      <div class="card-body">
        <?php if (!$porStatus): ?>
          <div class="text-muted text-center">Nenhuma OS no período</div>
        <?php else: ?>
          <table class="table table-sm">
            <?php foreach ($porStatus as $s): ?>
              <tr>
                <td><?= htmlspecialchars((string) $s['status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end"><?= (int) $s['total'] ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">Ordens de Serviço</div>
      <div class="card-body p-0">
        <?php if (!$ordens): ?>
          <div class="text-muted text-center py-3">Nenhuma OS no período</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Nº</th>
                  <th>Cliente</th>
                  <th>Sapateiro</th>
                  <th>Status</th>
                  <th>Valor</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ordens as $os): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($os['sapateiro_nome'] ?? 'Não atribuído'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $os['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>R$ <?= number_format((float) $os['valor_total'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
