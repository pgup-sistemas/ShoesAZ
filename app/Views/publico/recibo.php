<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recibo <?= htmlspecialchars((string) ($recibo['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { 
      background: #f8f9fa;
      padding: 20px;
    }
    .recibo-container { 
      max-width: 800px; 
      margin: 0 auto; 
      background: white;
      border: 2px solid #333; 
      padding: 30px; 
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .recibo-header { 
      text-align: center; 
      border-bottom: 2px solid #333; 
      padding-bottom: 20px; 
      margin-bottom: 20px; 
    }
    .recibo-numero { 
      font-size: 1.5rem; 
      font-weight: bold; 
      color: #008bcd; 
    }
    .table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    .total-row {
      font-weight: bold;
      font-size: 1.1rem;
      border-top: 2px solid #333;
    }
    .assinatura { 
      border-top: 1px solid #333; 
      margin-top: 60px; 
      padding-top: 10px; 
      text-align: center; 
    }
    .header-info {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="recibo-container">
    <div class="recibo-header">
      <h2 class="mb-1"><?= htmlspecialchars((string) ($empresa['nome'] ?? 'SAPATARIA MODELO'), ENT_QUOTES, 'UTF-8') ?></h2>
      <?php if (!empty($empresa['cnpj'])): ?>
        <p class="mb-1 text-muted">CNPJ: <?= htmlspecialchars((string) $empresa['cnpj'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if (!empty($empresa['endereco'])): ?>
        <p class="mb-1 text-muted"><?= htmlspecialchars((string) $empresa['endereco'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if (!empty($empresa['telefone'])): ?>
        <p class="mb-0 text-muted">Tel: <?= htmlspecialchars((string) $empresa['telefone'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>

    <div class="header-info">
      <div class="row">
        <div class="col-12 text-center mb-3">
          <div class="recibo-numero">RECIBO Nº <?= htmlspecialchars((string) ($recibo['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="col-md-6">
          <p class="mb-1"><strong>OS:</strong> <?= htmlspecialchars((string) ($recibo['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y', strtotime((string) $recibo['created_at'])) ?></p>
        </div>
        <div class="col-md-6 text-md-end">
          <p class="mb-1"><strong>Data Entrada:</strong> <?= isset($recibo['data_entrada']) ? date('d/m/Y', strtotime($recibo['data_entrada'])) : '-' ?></p>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <h5 class="mb-3">Dados do Cliente</h5>
      <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars((string) ($recibo['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <?php if (!empty($recibo['cliente_cpf'])): ?>
        <p class="mb-1"><strong>CPF:</strong> <?= htmlspecialchars((string) $recibo['cliente_cpf'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if (!empty($recibo['cliente_telefone'])): ?>
        <p class="mb-0"><strong>Telefone:</strong> <?= htmlspecialchars((string) $recibo['cliente_telefone'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>

    <?php if (!empty($sapatos)): ?>
      <div class="mb-4">
        <h5 class="mb-3">Serviços Realizados</h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th>Qtd</th>
                <th>Descrição</th>
                <th class="text-end">Valor Unit.</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sapatos as $s): ?>
                <tr>
                  <td>1</td>
                  <td><?= htmlspecialchars((string) $s['tipo_servico'] . ' - ' . $s['categoria'] . ($s['marca'] ? ' (' . $s['marca'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></td>
                  <td class="text-end">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="table-active" style="font-weight: bold;">
                <td colspan="3" class="text-end"><strong>VALOR TOTAL:</strong></td>
                <td class="text-end"><strong>R$ <?= number_format((float) ($recibo['valor_total'] ?? 0), 2, ',', '.') ?></strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($pagamentos)): ?>
      <div class="mb-4">
        <h5 class="mb-3">Pagamentos</h5>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Parcela</th>
                <th>Valor</th>
                <th>Data Pagamento</th>
                <th>Forma</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pagamentos as $pagamento): ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($pagamento['parcela_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">R$ <?= number_format((float) ($pagamento['valor'] ?? 0), 2, ',', '.') ?></td>
                  <td><?= $pagamento['data_pagamento'] ? date('d/m/Y', strtotime((string) $pagamento['data_pagamento'])) : '-' ?></td>
                  <td><?= htmlspecialchars((string) ($pagamento['forma_pagamento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="total-row">
                <td colspan="2" class="text-end"><strong>Total Pago:</strong></td>
                <td class="text-end">R$ <?= number_format(array_sum(array_column($pagamentos, 'valor')), 2, ',', '.') ?></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div class="mb-4 p-3 bg-light rounded">
      <h5 class="mb-3">Garantia e Termos</h5>
      <p class="mb-1"><strong>Garantia:</strong> <?= (int) ($recibo['garantia_dias'] ?? 30) ?> dias</p>
      <p class="mb-0"><strong>Termos:</strong> <?= nl2br(htmlspecialchars((string) ($recibo['termos'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    </div>

    <div class="row mt-5">
      <div class="col-6">
        <div class="assinatura">
          <p class="mb-0">Assinatura do Cliente</p>
        </div>
      </div>
      <div class="col-6">
        <div class="assinatura">
          <p class="mb-0">Assinatura do Responsável</p>
        </div>
      </div>
    </div>

    <div class="text-center mt-4 text-muted small">
      <p class="mb-0">Documento gerado em <?= date('d/m/Y H:i:s') ?></p>
    </div>
  </div>
</body>
</html>
