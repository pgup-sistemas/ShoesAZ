<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'OS', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; padding: 20px; }
    .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .badge-status { font-size: 1rem; padding: 8px 16px; }
    .timeline { position: relative; padding-left: 20px; }
    .timeline::before {
      content: '';
      position: absolute;
      left: 6px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #008bcd;
    }
    .timeline-item { position: relative; margin-bottom: 15px; }
    .timeline-item::before {
      content: '';
      position: absolute;
      left: -18px;
      top: 4px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #008bcd;
    }
    @media print {
      body { background: white; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="container" style="max-width: 800px;">
    <div class="card">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h4 class="mb-0"><?= htmlspecialchars((string) ($os['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
            <small class="text-muted">Ordem de Serviço</small>
          </div>
          <?php
          $badgeClass = match ((string) ($os['status'] ?? 'Recebido')) {
              'Entregue' => 'success',
              'Aguardando retirada' => 'success',
              'Em reparo' => 'info',
              'Cancelado' => 'danger',
              default => 'secondary',
          };
          ?>
          <span class="badge badge-status bg-<?= $badgeClass ?>">
            <?= htmlspecialchars((string) ($os['status'] ?? 'Recebido'), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="text-muted">Cliente</h6>
            <p class="mb-1 fw-semibold"><?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-muted"><?= htmlspecialchars((string) ($os['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="col-md-6 text-md-end">
            <h6 class="text-muted">Sapateiro</h6>
            <p><?= htmlspecialchars((string) ($os['sapateiro_nome'] ?? 'Não atribuído'), ENT_QUOTES, 'UTF-8') ?></p>
            <h6 class="text-muted mt-3">Localização</h6>
            <p><?= htmlspecialchars((string) ($os['localizacao'] ?? 'Não definida'), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-12">
            <h6 class="text-muted mb-3">Timeline</h6>
            <div class="timeline">
              <div class="timeline-item">
                <strong>Entrada:</strong> <?= isset($os['data_entrada']) ? date('d/m/Y', strtotime($os['data_entrada'])) : '-' ?>
              </div>
              <div class="timeline-item">
                <strong>Prazo de Entrega:</strong> <?= isset($os['prazo_entrega']) ? date('d/m/Y', strtotime($os['prazo_entrega'])) : '-' ?>
                <?php if (isset($os['prazo_entrega']) && $os['prazo_entrega'] < date('Y-m-d') && !in_array($os['status'], ['Entregue', 'Cancelado'])): ?>
                  <span class="badge bg-danger">Atrasado</span>
                <?php endif; ?>
              </div>
              <?php if ($os['data_conclusao']): ?>
                <div class="timeline-item">
                  <strong>Conclusão:</strong> <?= date('d/m/Y', strtotime($os['data_conclusao'])) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <h6 class="text-muted mb-3">Itens</h6>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Serviço</th>
                <th>Marca</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sapatos as $s): ?>
                <tr>
                  <td><?= htmlspecialchars((string) $s['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) $s['tipo_servico'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($s['marca'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="table-active">
                <td colspan="3" class="text-end"><strong>Valor Total:</strong></td>
                <td class="text-end"><strong>R$ <?= number_format((float) ($os['valor_total'] ?? 0), 2, ',', '.') ?></strong></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <?php if (!empty($os['observacoes'])): ?>
          <div class="mt-4">
            <h6 class="text-muted">Observações</h6>
            <p><?= nl2br(htmlspecialchars((string) $os['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-center mt-4 no-print">
      <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
      <p class="text-muted mt-2 small">Este é um documento apenas para visualização.<br>Para mais informações, entre em contato com a sapataria.</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
