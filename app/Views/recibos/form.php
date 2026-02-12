<?php

use App\Core\Csrf;

$os = $os ?? [];
$pagamentos = $pagamentos ?? [];
$totalPago = $totalPago ?? 0;
$recibo = $recibo ?? [];

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Emitir Recibo</div>
    <div class="text-muted small">OS <?= htmlspecialchars((string) ($os['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) ($os['id'] ?? 0) ?>" class="btn btn-outline-secondary">Voltar</a>
</div>

<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= \App\Core\View::url('/recibos/store') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="os_id" value="<?= (int) ($os['id'] ?? 0) ?>">

          <div class="alert alert-info mb-3">
            <strong>Cliente:</strong> <?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
            <strong>Telefone:</strong> <?= htmlspecialchars((string) ($os['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
            <strong>Valor Total OS:</strong> R$ <?= number_format((float) ($os['valor_total'] ?? 0), 2, ',', '.') ?><br>
            <strong>Total Pago:</strong> R$ <?= number_format($totalPago, 2, ',', '.') ?>
          </div>

          <?php if ($pagamentos): ?>
            <div class="mb-3">
              <label class="form-label">Pagamentos Registrados</label>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Parcela</th>
                      <th>Forma</th>
                      <th class="text-end">Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pagamentos as $p): ?>
                      <tr>
                        <td>#<?= (int) $p['parcela_numero'] ?></td>
                        <td><?= htmlspecialchars((string) ($p['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">R$ <?= number_format((float) $p['valor'], 2, ',', '.') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mb-3">
              Nenhum pagamento registrado para esta OS. É recomendado registrar os pagamentos antes de emitir o recibo.
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Garantia (dias)</label>
            <input type="number" class="form-control" name="garantia_dias" value="<?= (int) ($recibo['garantia_dias'] ?? 30) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Termos e Condições</label>
            <textarea class="form-control" name="termos" rows="4"><?= htmlspecialchars((string) ($recibo['termos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Emitir Recibo</button>
            <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) ($os['id'] ?? 0) ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
