<?php

$usuarios = $usuarios ?? [];
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Usuários</div>
    <div class="text-muted small">Gestão de usuários do sistema</div>
  </div>
  <a href="<?= \App\Core\View::url('/usuarios/create') ?>" class="btn btn-primary">+ Novo Usuário</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Login</th>
          <th>Perfil</th>
          <th>Status</th>
          <th>Criado em</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$usuarios): ?>
          <tr>
            <td colspan="6" class="text-center text-muted p-4">Nenhum usuário encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($usuarios as $u): ?>
            <?php
            $statusBadge = (int) $u['ativo'] === 1 
                ? '<span class="badge bg-success">Ativo</span>' 
                : '<span class="badge bg-secondary">Inativo</span>';
            ?>
            <tr>
              <td><?= htmlspecialchars((string) $u['nome'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $u['login'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $u['perfil'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $statusBadge ?></td>
              <td><?= date('d/m/Y', strtotime((string) $u['created_at'])) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\View::url('/usuarios/edit') ?>?id=<?= (int) $u['id'] ?>">Editar</a>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> usuários
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/usuarios'), []) ?>
</div>
<?php endif; ?>
