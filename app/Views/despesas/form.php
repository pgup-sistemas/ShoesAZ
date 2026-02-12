<?php

use App\Core\Csrf;

$despesa = $despesa ?? [];
$isEdit = !empty($despesa['id']);

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0"><?= $isEdit ? 'Editar Despesa' : 'Nova Despesa' ?></div>
  </div>
  <a href="<?= \App\Core\View::url('/despesas') ?>" class="btn btn-outline-secondary">Voltar</a>
</div>

<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= $isEdit ? \App\Core\View::url('/despesas/update?id=' . (int) $despesa['id']) : \App\Core\View::url('/despesas/store') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">Descrição *</label>
            <input type="text" class="form-control" name="descricao" value="<?= htmlspecialchars((string) ($despesa['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Categoria *</label>
            <select class="form-select" name="categoria" required>
              <option value="">Selecione...</option>
              <option value="Materiais" <?= ((string) ($despesa['categoria'] ?? '')) === 'Materiais' ? 'selected' : '' ?>>Materiais</option>
              <option value="Aluguel" <?= ((string) ($despesa['categoria'] ?? '')) === 'Aluguel' ? 'selected' : '' ?>>Aluguel</option>
              <option value="Energia" <?= ((string) ($despesa['categoria'] ?? '')) === 'Energia' ? 'selected' : '' ?>>Energia</option>
              <option value="Água" <?= ((string) ($despesa['categoria'] ?? '')) === 'Água' ? 'selected' : '' ?>>Água</option>
              <option value="Telefone/Internet" <?= ((string) ($despesa['categoria'] ?? '')) === 'Telefone/Internet' ? 'selected' : '' ?>>Telefone/Internet</option>
              <option value="Salários" <?= ((string) ($despesa['categoria'] ?? '')) === 'Salários' ? 'selected' : '' ?>>Salários</option>
              <option value="Impostos" <?= ((string) ($despesa['categoria'] ?? '')) === 'Impostos' ? 'selected' : '' ?>>Impostos</option>
              <option value="Manutenção" <?= ((string) ($despesa['categoria'] ?? '')) === 'Manutenção' ? 'selected' : '' ?>>Manutenção</option>
              <option value="Outras" <?= ((string) ($despesa['categoria'] ?? '')) === 'Outras' ? 'selected' : '' ?>>Outras</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Valor (R$) *</label>
            <input type="number" step="0.01" class="form-control" name="valor" value="<?= (float) ($despesa['valor'] ?? 0) ?>" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Vencimento</label>
              <input type="date" class="form-control" name="vencimento" value="<?= htmlspecialchars((string) ($despesa['vencimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Data do Pagamento</label>
              <input type="date" class="form-control" name="data_pagamento" value="<?= htmlspecialchars((string) ($despesa['data_pagamento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Forma de Pagamento</label>
            <select class="form-select" name="forma_pagamento">
              <option value="">Selecione...</option>
              <option value="Dinheiro" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
              <option value="PIX" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'PIX' ? 'selected' : '' ?>>PIX</option>
              <option value="Cartão Débito" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'Cartão Débito' ? 'selected' : '' ?>>Cartão Débito</option>
              <option value="Cartão Crédito" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'Cartão Crédito' ? 'selected' : '' ?>>Cartão Crédito</option>
              <option value="Boleto" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'Boleto' ? 'selected' : '' ?>>Boleto</option>
              <option value="Transferência" <?= ((string) ($despesa['forma_pagamento'] ?? '')) === 'Transferência' ? 'selected' : '' ?>>Transferência</option>
            </select>
          </div>

          <?php if (!$isEdit): ?>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="recorrente" value="1" id="recorrente" <?= (int) ($despesa['recorrente'] ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label" for="recorrente">Despesa Recorrente</label>
            </div>
          </div>

          <div class="mb-3" id="periodicidade-container" style="display: <?= (int) ($despesa['recorrente'] ?? 0) ? 'block' : 'none' ?>;">
            <label class="form-label">Periodicidade</label>
            <select class="form-select" name="periodicidade">
              <option value="">Selecione...</option>
              <option value="Mensal" <?= ((string) ($despesa['periodicidade'] ?? '')) === 'Mensal' ? 'selected' : '' ?>>Mensal</option>
              <option value="Semanal" <?= ((string) ($despesa['periodicidade'] ?? '')) === 'Semanal' ? 'selected' : '' ?>>Semanal</option>
              <option value="Anual" <?= ((string) ($despesa['periodicidade'] ?? '')) === 'Anual' ? 'selected' : '' ?>>Anual</option>
            </select>
          </div>
          <?php endif; ?>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= \App\Core\View::url('/despesas') ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('recorrente')?.addEventListener('change', function() {
  document.getElementById('periodicidade-container').style.display = this.checked ? 'block' : 'none';
});
</script>
