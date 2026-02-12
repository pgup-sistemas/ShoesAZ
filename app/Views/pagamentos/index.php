<?php

$osId = $osId ?? 0;
$status = $status ?? '';
$pagamentos = $pagamentos ?? [];
$ordens = $ordens ?? [];
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Pagamentos</div>
    <div class="text-muted small">Controle de parcelas e quitações</div>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/pagamentos') ?>">
  <div class="col-12 col-md-3">
    <select class="form-select" name="os_id">
      <option value="">Todas as OS</option>
      <?php foreach ($ordens as $o): ?>
        <option value="<?= (int) $o['id'] ?>" <?= $osId === (int) $o['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string) $o['numero'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-3">
    <select class="form-select" name="status">
      <option value="">Todos os status</option>
      <option value="Pendente" <?= $status === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
      <option value="Pago" <?= $status === 'Pago' ? 'selected' : '' ?>>Pago</option>
      <option value="Atrasado" <?= $status === 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
    </select>
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/pagamentos') ?>">Limpar</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>OS</th>
          <th>Cliente</th>
          <th>Parcela</th>
          <th>Vencimento</th>
          <th>Status</th>
          <th>Forma</th>
          <th class="text-end">Valor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$pagamentos): ?>
          <tr>
            <td colspan="8" class="text-center text-muted p-4">Nenhum pagamento encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pagamentos as $p): ?>
            <?php
            $badgeClass = match ((string) $p['status']) {
                'Pago' => 'bg-success',
                'Atrasado' => 'bg-danger',
                default => 'bg-warning text-dark',
            };
            ?>
            <tr>
              <td><?= htmlspecialchars((string) ($p['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($p['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>#<?= (int) $p['parcela_numero'] ?></td>
              <td><?= $p['vencimento'] ? date('d/m/Y', strtotime($p['vencimento'])) : '-' ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string) $p['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars((string) ($p['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">R$ <?= number_format((float) $p['valor'], 2, ',', '.') ?></td>
              <td class="text-end">
                <?php if ((string) $p['status'] !== 'Pago'): ?>
                  <form method="post" action="<?= \App\Core\View::url('/pagamentos/quitar') ?>" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <select name="forma_pagamento" class="form-select form-select-sm d-inline w-auto" required>
                      <option value="">Forma...</option>
                      <option value="Dinheiro">Dinheiro</option>
                      <option value="PIX">PIX</option>
                      <option value="Cartão Débito">Cartão Débito</option>
                      <option value="Cartão Crédito">Cartão Crédito</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-success">Quitar</button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-success">Quitado <?= $p['data_pagamento'] ? date('d/m/Y', strtotime($p['data_pagamento'])) : '' ?></span>
                <?php endif; ?>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> pagamentos
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/pagamentos'), ['os_id' => $osId, 'status' => $status]) ?>
</div>
<?php endif; ?>
