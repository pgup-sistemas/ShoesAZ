<?php

$q = $q ?? '';
$status = $status ?? '';
$atrasados = $atrasados ?? 0;
$ordens = $ordens ?? [];
$isSapateiro = $isSapateiro ?? false;
$pagination = $pagination ?? null;

$hoje = date('Y-m-d');

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0"><?= $isSapateiro ? 'Minhas OS' : 'Ordens de Serviço' ?></div>
    <div class="text-muted small">Controle de prazos e status</div>
  </div>
  <?php if (!$isSapateiro): ?>
    <a href="<?= \App\Core\View::url('/orcamentos/create') ?>" class="btn btn-primary">+ Nova OS (via Orçamento)</a>
  <?php endif; ?>
</div>

<form class="row g-2 mb-3" method="get" action="<?= \App\Core\View::url('/os') ?>">
  <div class="col-12 col-md-4">
    <input class="form-control" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar número ou cliente...">
  </div>
  <div class="col-12 col-md-3">
    <select class="form-select" name="status">
      <option value="">Todos os status</option>
      <option value="Recebido" <?= $status === 'Recebido' ? 'selected' : '' ?>>Recebido</option>
      <option value="Em reparo" <?= $status === 'Em reparo' ? 'selected' : '' ?>>Em reparo</option>
      <option value="Aguardando retirada" <?= $status === 'Aguardando retirada' ? 'selected' : '' ?>>Aguardando retirada</option>
      <option value="Entregue" <?= $status === 'Entregue' ? 'selected' : '' ?>>Entregue</option>
      <option value="Cancelado" <?= $status === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
    </select>
  </div>
  <?php if (!$isSapateiro): ?>
    <div class="col-12 col-md-auto">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="atrasados" value="1" id="atrasados" <?= $atrasados ? 'checked' : '' ?> onchange="this.form.submit()">
        <label class="form-check-label" for="atrasados">Só atrasados</label>
      </div>
    </div>
  <?php endif; ?>
  <div class="col-12 col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/os') ?>">Limpar</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>Número</th>
          <th>Cliente</th>
          <th>Prazo</th>
          <th>Status</th>
          <?php if (!$isSapateiro): ?>
            <th>Sapateiro</th>
          <?php endif; ?>
          <th>Valor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$ordens): ?>
          <tr>
            <td colspan="<?= $isSapateiro ? 6 : 7 ?>" class="text-center text-muted p-4">Nenhuma OS encontrada.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($ordens as $o): ?>
            <?php
            $prazo = (string) ($o['prazo_entrega'] ?? '');
            $diasAtraso = 0;
            $badgePrazo = 'bg-success';
            if ($prazo !== '' && !in_array($o['status'], ['Entregue', 'Cancelado'])) {
                $diff = strtotime($prazo) - strtotime($hoje);
                $dias = (int) floor($diff / 86400);
                if ($dias < 0) {
                    $badgePrazo = 'bg-danger';
                    $diasAtraso = abs($dias);
                } elseif ($dias <= 2) {
                    $badgePrazo = 'bg-warning text-dark';
                }
            }

            $badgeStatus = match ((string) $o['status']) {
                'Recebido' => 'bg-secondary',
                'Em reparo' => 'bg-info text-dark',
                'Aguardando retirada' => 'bg-success',
                'Entregue' => 'bg-dark',
                'Cancelado' => 'bg-danger',
                default => 'bg-light text-dark',
            };
            ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars((string) $o['numero'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($o['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge <?= $badgePrazo ?>">
                  <?= $prazo ? date('d/m/Y', strtotime($prazo)) : '-' ?>
                  <?php if ($diasAtraso > 0): ?> (<?= $diasAtraso ?>d atraso)<?php endif; ?>
                </span>
              </td>
              <td><span class="badge <?= $badgeStatus ?>"><?= htmlspecialchars((string) $o['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <?php if (!$isSapateiro): ?>
                <td><?= htmlspecialchars((string) ($o['sapateiro_nome'] ?? 'Não atribuído'), ENT_QUOTES, 'UTF-8') ?></td>
              <?php endif; ?>
              <td>R$ <?= number_format((float) $o['valor_total'], 2, ',', '.') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $o['id'] ?>">Abrir</a>
                <?php if (!$isSapateiro): ?>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\View::url('/os/etiqueta') ?>?id=<?= (int) $o['id'] ?>" target="_blank">Etiqueta</a>
                <?php endif; ?>
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
      Mostrando <?= $pagination->getRange()[0] ?> - <?= $pagination->getRange()[1] ?> de <?= $pagination->total ?> OS
    </small>
  </div>
  <?= $pagination->render(\App\Core\View::url('/os'), ['q' => $q, 'status' => $status, 'atrasados' => $atrasados]) ?>
</div>
<?php endif; ?>
