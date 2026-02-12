<?php

use App\Core\Csrf;

$os = $os ?? [];
$pagamento = $pagamento ?? [];
$valorPago = $valorPago ?? 0;
$valorRestante = $valorRestante ?? 0;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Novo Pagamento</div>
    <div class="text-muted small">OS <?= htmlspecialchars((string) ($os['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) ($os['id'] ?? 0) ?>" class="btn btn-outline-secondary">Voltar</a>
</div>

<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= \App\Core\View::url('/pagamentos/store') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="os_id" value="<?= (int) ($os['id'] ?? 0) ?>">

          <div class="row mb-3">
            <div class="col-6">
              <div class="text-muted small">Valor Total OS</div>
              <div class="fs-5">R$ <?= number_format((float) ($os['valor_total'] ?? 0), 2, ',', '.') ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Valor Pago</div>
              <div class="fs-5 text-success">R$ <?= number_format($valorPago, 2, ',', '.') ?></div>
            </div>
          </div>

          <div class="alert alert-info mb-3">
            <strong>Restante:</strong> R$ <?= number_format($valorRestante, 2, ',', '.') ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Parcela Nº</label>
            <input type="number" class="form-control" name="parcela_numero" value="<?= (int) ($pagamento['parcela_numero'] ?? 1) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Valor (R$) *</label>
            <input type="number" step="0.01" class="form-control" name="valor" value="<?= number_format($valorRestante, 2, '.', '') ?>" max="<?= $valorRestante ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Vencimento</label>
            <input type="date" class="form-control" name="vencimento" value="<?= htmlspecialchars((string) ($pagamento['vencimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Forma de Pagamento</label>
            <select class="form-select" name="forma_pagamento">
              <option value="">Selecione...</option>
              <option value="Dinheiro">Dinheiro</option>
              <option value="PIX">PIX</option>
              <option value="Cartão Débito">Cartão Débito</option>
              <option value="Cartão Crédito">Cartão Crédito</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="Pendente">Pendente</option>
              <option value="Pago">Pago (Receber agora)</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) ($os['id'] ?? 0) ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
