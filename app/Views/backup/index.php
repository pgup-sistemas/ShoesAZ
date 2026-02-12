<?php

use App\Core\Csrf;

$backups = $backups ?? [];

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Backup</div>
    <div class="text-muted small">Backup do banco de dados</div>
  </div>
  <form method="post" action="<?= \App\Core\View::url('/backup/create') ?>" class="m-0">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary">+ Criar Backup</button>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Arquivo</th>
          <th>Tamanho</th>
          <th>Data</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$backups): ?>
          <tr>
            <td colspan="4" class="text-center text-muted p-4">Nenhum backup encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($backups as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($b['size'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">
                <a href="<?= \App\Core\View::url('/backup/download') ?>?file=<?= urlencode($b['name']) ?>" class="btn btn-sm btn-outline-primary">Download</a>
                <form method="post" action="<?= \App\Core\View::url('/backup/delete') ?>" class="d-inline">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="file" value="<?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover este backup?')">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-info mt-3">
  <strong>Dica:</strong> Faça backups regularmente e armazene-os em local seguro. O sistema não faz backup automático - você deve criar manualmente.
</div>
