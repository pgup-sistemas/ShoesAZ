<?php

$q = $q ?? '';
$clientes = $clientes ?? [];
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Clientes</div>
    <div class="text-muted small">Busca por nome, telefone ou CPF</div>
  </div>
  <a href="<?= \App\Core\View::url('/clientes/create') ?>" class="btn btn-primary">+ Novo Cliente</a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/clientes') ?>">
  <div class="col-12 col-md-6">
    <input class="form-control form-control-lg" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar...">
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary btn-lg" type="submit">Buscar</button>
    <a class="btn btn-outline-secondary btn-lg" href="<?= \App\Core\View::url('/clientes') ?>">Limpar</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>CPF</th>
          <th class="d-none d-md-table-cell">E-mail</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$clientes): ?>
          <tr>
            <td colspan="6" class="text-center text-muted p-4">Nenhum cliente encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($clientes as $c): ?>
            <tr>
              <td><?= (int) $c['id'] ?></td>
              <td><?= htmlspecialchars((string) $c['nome'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $c['telefone'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($c['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="d-none d-md-table-cell"><?= htmlspecialchars((string) ($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\View::url('/clientes/edit') ?>?id=<?= (int) $c['id'] ?>">Editar</a>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> clientes
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/clientes'), ['q' => $q]) ?>
</div>
<?php endif; ?>
