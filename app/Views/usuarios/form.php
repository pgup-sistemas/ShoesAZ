<?php

use App\Core\Csrf;

$usuario = $usuario ?? [];
$isEdit = !empty($usuario['id']);

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0"><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?></div>
    <div class="text-muted small">Preencha os dados do usuário</div>
  </div>
  <a href="<?= \App\Core\View::url('/usuarios') ?>" class="btn btn-outline-secondary">Voltar</a>
</div>

<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= $isEdit ? \App\Core\View::url('/usuarios/update?id=' . (int) $usuario['id']) : \App\Core\View::url('/usuarios/store') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">Nome *</label>
            <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars((string) ($usuario['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Login *</label>
            <input type="text" class="form-control" name="login" value="<?= htmlspecialchars((string) ($usuario['login'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Senha <?= $isEdit ? '(deixe em branco para manter)' : '*' ?></label>
            <input type="password" class="form-control" name="senha" <?= $isEdit ? '' : 'required' ?> minlength="6">
            <div class="form-text">Mínimo 6 caracteres.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Perfil *</label>
            <select class="form-select" name="perfil" required>
              <option value="Administrador" <?= ((string) ($usuario['perfil'] ?? '')) === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
              <option value="Gerente" <?= ((string) ($usuario['perfil'] ?? '')) === 'Gerente' ? 'selected' : '' ?>>Gerente</option>
              <option value="Atendente" <?= ((string) ($usuario['perfil'] ?? '')) === 'Atendente' ? 'selected' : '' ?>>Atendente</option>
              <option value="Sapateiro" <?= ((string) ($usuario['perfil'] ?? '')) === 'Sapateiro' ? 'selected' : '' ?>>Sapateiro</option>
            </select>
          </div>

          <?php if ($isEdit): ?>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="ativo">
                <option value="1" <?= (int) ($usuario['ativo'] ?? 1) === 1 ? 'selected' : '' ?>>Ativo</option>
                <option value="0" <?= (int) ($usuario['ativo'] ?? 1) === 0 ? 'selected' : '' ?>>Inativo</option>
              </select>
            </div>
          <?php endif; ?>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= \App\Core\View::url('/usuarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
