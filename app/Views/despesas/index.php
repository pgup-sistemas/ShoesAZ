<?php

$categoria = $categoria ?? '';
$dataInicio = $dataInicio ?? '';
$dataFim = $dataFim ?? '';
$despesas = $despesas ?? [];
$total = $total ?? 0;
$pagination = $pagination ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Despesas</div>
    <div class="text-muted small">Controle de despesas operacionais</div>
  </div>
  <a href="<?= \App\Core\View::url('/despesas/create') ?>" class="btn btn-primary">+ Nova Despesa</a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/despesas') ?>">
  <div class="col-12 col-md-3">
    <select class="form-select" name="categoria">
      <option value="">Todas as categorias</option>
      <option value="Materiais" <?= $categoria === 'Materiais' ? 'selected' : '' ?>>Materiais</option>
      <option value="Aluguel" <?= $categoria === 'Aluguel' ? 'selected' : '' ?>>Aluguel</option>
      <option value="Energia" <?= $categoria === 'Energia' ? 'selected' : '' ?>>Energia</option>
      <option value="Água" <?= $categoria === 'Água' ? 'selected' : '' ?>>Água</option>
      <option value="Telefone/Internet" <?= $categoria === 'Telefone/Internet' ? 'selected' : '' ?>>Telefone/Internet</option>
      <option value="Salários" <?= $categoria === 'Salários' ? 'selected' : '' ?>>Salários</option>
      <option value="Impostos" <?= $categoria === 'Impostos' ? 'selected' : '' ?>>Impostos</option>
      <option value="Manutenção" <?= $categoria === 'Manutenção' ? 'selected' : '' ?>>Manutenção</option>
      <option value="Outras" <?= $categoria === 'Outras' ? 'selected' : '' ?>>Outras</option>
    </select>
  </div>
  <div class="col-12 col-md-2">
    <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>" placeholder="De">
  </div>
  <div class="col-12 col-md-2">
    <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>" placeholder="Até">
  </div>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/despesas') ?>">Limpar</a>
  </div>
</form>

<div class="alert alert-info mb-3">
  <strong>Total no período:</strong> R$ <?= number_format($total, 2, ',', '.') ?>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Data</th>
          <th>Descrição</th>
          <th>Categoria</th>
          <th>Vencimento</th>
          <th>Pagamento</th>
          <th class="text-end">Valor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$despesas): ?>
          <tr>
            <td colspan="7" class="text-center text-muted p-4">Nenhuma despesa encontrada.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($despesas as $d): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime((string) $d['created_at'])) ?></td>
              <td><?= htmlspecialchars((string) $d['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $d['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= $d['vencimento'] ? date('d/m/Y', strtotime($d['vencimento'])) : '-' ?></td>
              <td>
                <?php if ($d['data_pagamento']): ?>
                  <span class="badge bg-success">Pago</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pendente</span>
                <?php endif; ?>
              </td>
              <td class="text-end">R$ <?= number_format((float) $d['valor'], 2, ',', '.') ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\View::url('/despesas/edit') ?>?id=<?= (int) $d['id'] ?>">Editar</a>
                  <form method="post" action="<?= \App\Core\View::url('/despesas/delete') ?>" class="m-0" onsubmit="return confirm('Excluir esta despesa?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> despesas
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/despesas'), ['categoria' => $categoria, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) ?>
</div>
<?php endif; ?>
