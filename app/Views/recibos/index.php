<?php

$osId = $osId ?? 0;
$recibos = $recibos ?? [];
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Recibos</div>
    <div class="text-muted small">Histórico de recibos emitidos</div>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <form class="d-flex gap-2 align-items-center" method="get" action="<?= \App\Core\View::url('/recibos') ?>">
      <input type="number" class="form-control form-control-sm" style="max-width: 140px" name="os_id" placeholder="OS ID" value="<?= (int) $osId ?>">
      <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
    </form>

    <?php if ((int) $osId > 0): ?>
      <a class="btn btn-sm btn-primary" href="<?= \App\Core\View::url('/recibos/create') ?>?os_id=<?= (int) $osId ?>">Emitir Recibo</a>
    <?php else: ?>
      <a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\View::url('/os') ?>">Escolher OS</a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Número</th>
          <th>OS</th>
          <th>Cliente</th>
          <th>Valor</th>
          <th>Forma Pagto</th>
          <th>Data</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recibos): ?>
          <tr>
            <td colspan="7" class="text-center text-muted p-4">Nenhum recibo encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recibos as $r): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars((string) $r['numero'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($r['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($r['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>R$ <?= number_format((float) $r['valor_total'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars((string) ($r['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= date('d/m/Y', strtotime((string) $r['created_at'])) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\View::url('/recibos/visualizar') ?>?id=<?= (int) $r['id'] ?>">Ver</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\View::url('/recibos/imprimir') ?>?id=<?= (int) $r['id'] ?>" target="_blank">Imprimir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pagination && $pagination->hasPages()): ?>
<div class="mt-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <small class="text-muted">
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> recibos
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/recibos'), ['os_id' => $osId]) ?>
</div>
<?php endif; ?>
