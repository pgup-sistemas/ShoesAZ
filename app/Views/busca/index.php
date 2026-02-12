<?php

$termo = $termo ?? '';
$clientes = $clientes ?? [];
$os = $os ?? [];
$orcamentos = $orcamentos ?? [];

$total = count($clientes) + count($os) + count($orcamentos);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Resultados da Busca</h5>
  <a href="<?= \App\Core\View::url('/') ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<form class="mb-4" action="<?= \App\Core\View::url('/busca') ?>" method="get">
  <div class="input-group">
    <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($termo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar cliente, OS, orçamento..." required>
    <button class="btn btn-primary" type="submit">Buscar</button>
  </div>
</form>

<?php if ($termo === ''): ?>
  <div class="alert alert-info">
    Digite um termo para buscar clientes, ordens de serviço ou orçamentos.
  </div>
<?php elseif ($total === 0): ?>
  <div class="alert alert-warning">
    Nenhum resultado encontrado para "<strong><?= htmlspecialchars($termo, ENT_QUOTES, 'UTF-8') ?></strong>".
  </div>
<?php else: ?>
  <div class="alert alert-success">
    Encontrados <strong><?= $total ?></strong> resultados para "<strong><?= htmlspecialchars($termo, ENT_QUOTES, 'UTF-8') ?></strong>".
  </div>

  <?php if (!empty($clientes)): ?>
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">
        <strong>Clientes (<?= count($clientes) ?>)</strong>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($clientes as $c): ?>
          <a href="<?= \App\Core\View::url('/clientes/edit') ?>?id=<?= (int) $c['id'] ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between">
              <div>
                <strong><?= htmlspecialchars((string) $c['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="small text-muted"><?= htmlspecialchars((string) ($c['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <span class="badge bg-secondary">Cliente</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($os)): ?>
    <div class="card mb-3">
      <div class="card-header bg-success text-white">
        <strong>Ordens de Serviço (<?= count($os) ?>)</strong>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($os as $o): ?>
          <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $o['id'] ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between">
              <div>
                <strong>OS <?= htmlspecialchars((string) $o['numero'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="small text-muted"><?= htmlspecialchars((string) ($o['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <span class="badge bg-info"><?= htmlspecialchars((string) $o['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($orcamentos)): ?>
    <div class="card mb-3">
      <div class="card-header bg-warning text-dark">
        <strong>Orçamentos (<?= count($orcamentos) ?>)</strong>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($orcamentos as $o): ?>
          <a href="<?= \App\Core\View::url('/orcamentos/edit') ?>?id=<?= (int) $o['id'] ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between">
              <div>
                <strong>Orçamento <?= htmlspecialchars((string) $o['numero'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="small text-muted"><?= htmlspecialchars((string) ($o['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <span class="badge bg-secondary"><?= htmlspecialchars((string) $o['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>
