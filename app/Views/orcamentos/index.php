<?php

$q = $q ?? '';
$status = $status ?? '';
$orcamentos = $orcamentos ?? [];
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Orçamentos</div>
    <div class="text-muted small">Busque por número ou cliente</div>
  </div>
  <a href="<?= \App\Core\View::url('/orcamentos/create') ?>" class="btn btn-primary">+ Novo Orçamento</a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/orcamentos') ?>">
  <div class="col-12 col-md-4">
    <input class="form-control" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar número ou cliente...">
  </div>
  <div class="col-12 col-md-3">
    <select class="form-select" name="status">
      <option value="">Todos os status</option>
      <option value="Aguardando" <?= $status === 'Aguardando' ? 'selected' : '' ?>>Aguardando</option>
      <option value="Aprovado" <?= $status === 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
      <option value="Reprovado" <?= $status === 'Reprovado' ? 'selected' : '' ?>>Reprovado</option>
      <option value="Expirado" <?= $status === 'Expirado' ? 'selected' : '' ?>>Expirado</option>
      <option value="Convertido" <?= $status === 'Convertido' ? 'selected' : '' ?>>Convertido</option>
    </select>
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/orcamentos') ?>">Limpar</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Número</th>
          <th>Cliente</th>
          <th>Valor Final</th>
          <th>Status</th>
          <th>Validade</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$orcamentos): ?>
          <tr>
            <td colspan="6" class="text-center text-muted p-4">Nenhum orçamento encontrado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($orcamentos as $o): ?>
            <?php
            $badgeClass = match ((string) $o['status']) {
                'Aprovado' => 'bg-success',
                'Aguardando' => 'bg-warning text-dark',
                'Reprovado' => 'bg-danger',
                'Expirado' => 'bg-secondary',
                'Convertido' => 'bg-info text-dark',
                default => 'bg-light text-dark',
            };
            ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars((string) $o['numero'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($o['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>R$ <?= number_format((float) $o['valor_final'], 2, ',', '.') ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string) $o['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= $o['validade'] ? date('d/m/Y', strtotime($o['validade'])) : '-' ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <?php if ((string) ($o['status'] ?? '') === 'Aguardando'): ?>
                    <form method="post" action="<?= \App\Core\View::url('/orcamentos/aprovar') ?>" class="m-0">
                      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success">Aprovar</button>
                    </form>
                  <?php endif; ?>

                  <?php if ((string) ($o['status'] ?? '') === 'Aprovado'): ?>
                    <form method="post" action="<?= \App\Core\View::url('/orcamentos/converter') ?>" class="m-0" onsubmit="return confirm('Converter este orçamento em OS?');">
                      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary">Converter</button>
                    </form>
                  <?php endif; ?>

                  <a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\View::url('/orcamentos/edit') ?>?id=<?= (int) $o['id'] ?>">Abrir</a>
                </div>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> orçamentos
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/orcamentos'), ['q' => $q, 'status' => $status]) ?>
</div>
<?php endif; ?>
