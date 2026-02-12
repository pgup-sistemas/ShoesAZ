<?php

$clientesTop = $clientesTop ?? [];
$clientesInativos = $clientesInativos ?? [];

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Relatório de Clientes</div>
    <div class="text-muted small">Análise de clientes</div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card border-success">
      <div class="card-header bg-success text-white">
        <strong>Top 20 Clientes (mais OS)</strong>
      </div>
      <div class="card-body p-0">
        <?php if (!$clientesTop): ?>
          <div class="text-muted text-center py-3">Nenhum cliente encontrado</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Telefone</th>
                  <th class="text-center">OS</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($clientesTop as $c): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) $c['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($c['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center"><?= (int) $c['total_os'] ?></td>
                    <td class="text-end">R$ <?= number_format((float) $c['valor_total'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-warning">
      <div class="card-header bg-warning text-dark">
        <strong>Clientes Inativos (+90 dias sem OS)</strong>
      </div>
      <div class="card-body p-0">
        <?php if (!$clientesInativos): ?>
          <div class="text-muted text-center py-3">Nenhum cliente inativo</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Telefone</th>
                  <th>Última OS</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($clientesInativos as $c): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) $c['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($c['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $c['ultima_os'] ? date('d/m/Y', strtotime($c['ultima_os'])) : 'Nunca' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
