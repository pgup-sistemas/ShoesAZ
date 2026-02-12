<?php

use App\Core\Csrf;

$empresa = $empresa ?? [];

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Dados da Empresa</div>
    <div class="text-muted small">Informações que aparecem nos recibos</div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= \App\Core\View::url('/configuracoes/empresa') ?>">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

      <div class="mb-3">
        <label class="form-label">Nome da Empresa *</label>
        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars((string) ($empresa['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">CNPJ</label>
        <input type="text" class="form-control" name="cnpj" value="<?= htmlspecialchars((string) ($empresa['cnpj'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Endereço</label>
        <textarea class="form-control" name="endereco" rows="2"><?= htmlspecialchars((string) ($empresa['endereco'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Telefone</label>
        <input type="text" class="form-control" name="telefone" value="<?= htmlspecialchars((string) ($empresa['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string) ($empresa['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Termos Padrão do Recibo</label>
        <textarea class="form-control" name="termos_recibo" rows="3"><?= htmlspecialchars((string) ($empresa['termos_recibo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <div class="form-text">Estes termos aparecerão automaticamente nos recibos.</div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="<?= \App\Core\View::url('/') ?>" class="btn btn-outline-secondary">Voltar</a>
      </div>
    </form>
  </div>
</div>
