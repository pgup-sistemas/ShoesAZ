<?php

use App\Core\Csrf;

$q = $q ?? '';
$apenasAtrasados = $apenasAtrasados ?? false;
$contas = $contas ?? [];
$totalValor = $totalValor ?? 0;
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Contas a Receber</div>
    <div class="text-muted small">Parcelas pendentes por OS</div>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/contas-receber') ?>">
  <div class="col-12 col-md-5">
    <input class="form-control" name="q" value="<?= htmlspecialchars((string) $q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por OS, cliente ou telefone">
  </div>
  <div class="col-12 col-md-3">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="atrasados" value="1" id="atrasados" <?= $apenasAtrasados ? 'checked' : '' ?>>
      <label class="form-check-label" for="atrasados">Somente atrasados</label>
    </div>
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/contas-receber') ?>">Limpar</a>
  </div>
</form>

<div class="alert alert-info mb-3">
  <strong>Total pendente (filtro atual):</strong> R$ <?= number_format((float) $totalValor, 2, ',', '.') ?>
</div>

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
          <th class="text-end">Valor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$contas): ?>
          <tr>
            <td colspan="7" class="text-center text-muted p-4">Nenhuma parcela pendente encontrada.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($contas as $p): ?>
            <?php
            $vencimento = (string) ($p['vencimento'] ?? '');
            $isAtrasado = $vencimento !== '' && strtotime($vencimento) < strtotime(date('Y-m-d'));
            $badgeClass = $isAtrasado ? 'bg-danger' : 'bg-warning text-dark';
            ?>
            <tr>
              <td>
                <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) ($p['os_id'] ?? 0) ?>" class="text-decoration-none">
                  <?= htmlspecialchars((string) ($p['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <div class="text-muted small"><?= htmlspecialchars((string) ($p['os_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td>
                <?= htmlspecialchars((string) ($p['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <div class="text-muted small"><?= htmlspecialchars((string) ($p['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td>#<?= (int) ($p['parcela_numero'] ?? 0) ?></td>
              <td><?= $vencimento !== '' ? date('d/m/Y', strtotime($vencimento)) : '-' ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= $isAtrasado ? 'Atrasado' : 'Pendente' ?></span></td>
              <td class="text-end">R$ <?= number_format((float) ($p['valor'] ?? 0), 2, ',', '.') ?></td>
              <td class="text-end">
                <form method="post" action="<?= \App\Core\View::url('/pagamentos/quitar') ?>" class="d-inline">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">
                  <select name="forma_pagamento" class="form-select form-select-sm d-inline w-auto" required>
                    <option value="">Forma...</option>
                    <option value="Dinheiro">Dinheiro</option>
                    <option value="PIX">PIX</option>
                    <option value="Cartão Débito">Cartão Débito</option>
                    <option value="Cartão Crédito">Cartão Crédito</option>
                  </select>
                  <button type="submit" class="btn btn-sm btn-success">Quitar</button>
                </form>
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
        Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> parcelas
      </small>
    </div>
    <?= $pagination->render(\App\Core\View::url('/contas-receber'), ['q' => $q, 'atrasados' => $apenasAtrasados ? '1' : '0']) ?>
  </div>
<?php endif; ?>
