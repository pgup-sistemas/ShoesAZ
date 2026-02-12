<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Orçamento', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; padding: 20px; }
    .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .badge-status { font-size: 1rem; padding: 8px 16px; }
    .valor-total { font-size: 1.5rem; color: #008bcd; }
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
            <h4 class="mb-0"><?= htmlspecialchars((string) ($orcamento['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
            <small class="text-muted">Orçamento</small>
          </div>
          <span class="badge badge-status bg-<?= match((string)($orcamento['status'] ?? 'Aguardando')) { 'Aprovado' => 'success', 'Reprovado' => 'danger', 'Convertido' => 'info', default => 'warning' } ?>">
            <?= htmlspecialchars((string) ($orcamento['status'] ?? 'Aguardando'), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="text-muted">Cliente</h6>
            <p class="mb-1 fw-semibold"><?= htmlspecialchars((string) ($orcamento['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-muted"><?= htmlspecialchars((string) ($orcamento['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="col-md-6 text-md-end">
            <h6 class="text-muted">Validade</h6>
            <p><?= isset($orcamento['validade']) ? date('d/m/Y', strtotime($orcamento['validade'])) : 'Não definida' ?></p>
            <h6 class="text-muted mt-3">Data do Orçamento</h6>
            <p><?= isset($orcamento['created_at']) ? date('d/m/Y', strtotime($orcamento['created_at'])) : '-' ?></p>
          </div>
        </div>

        <h6 class="text-muted mb-3">Itens</h6>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Serviço</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sapatos as $s): ?>
                <tr>
                  <td><?= htmlspecialchars((string) $s['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) $s['tipo_servico'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="table-active">
                <td colspan="2" class="text-end"><strong>Valor Total:</strong></td>
                <td class="text-end valor-total"><strong>R$ <?= number_format((float) ($orcamento['valor_total'] ?? 0), 2, ',', '.') ?></strong></td>
              </tr>
              <?php if ((float) ($orcamento['desconto'] ?? 0) > 0): ?>
                <tr>
                  <td colspan="2" class="text-end text-danger"><strong>Desconto:</strong></td>
                  <td class="text-end text-danger"><strong>- R$ <?= number_format((float) $orcamento['desconto'], 2, ',', '.') ?></strong></td>
                </tr>
                <tr class="table-active">
                  <td colspan="2" class="text-end"><strong>Valor Final:</strong></td>
                  <td class="text-end valor-total"><strong>R$ <?= number_format((float) ($orcamento['valor_final'] ?? 0), 2, ',', '.') ?></strong></td>
                </tr>
              <?php endif; ?>
            </tfoot>
          </table>
        </div>

        <?php if (!empty($orcamento['observacoes'])): ?>
          <div class="mt-4">
            <h6 class="text-muted">Observações</h6>
            <p><?= nl2br(htmlspecialchars((string) $orcamento['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-center mt-4 no-print">
      <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
      <p class="text-muted mt-2 small">Este é um documento apenas para visualização.</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
