<?php
$orcamento = $orcamento ?? [];
$sapatos = $sapatos ?? [];
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orçamento <?= htmlspecialchars((string) ($orcamento['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      font-size: 12pt;
      line-height: 1.5;
      padding: 20mm;
      max-width: 210mm;
      margin: 0 auto;
      color: #333;
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #008bcd;
    }
    .nome-empresa {
      font-size: 18pt;
      font-weight: bold;
      color: #008bcd;
    }
    .dados-empresa {
      font-size: 9pt;
      color: #666;
      margin-top: 5px;
    }
    .titulo {
      font-size: 14pt;
      font-weight: bold;
      text-align: center;
      margin: 20px 0;
    }
    .secao {
      margin: 15px 0;
    }
    .secao-titulo {
      font-weight: bold;
      font-size: 11pt;
      color: #008bcd;
      margin-bottom: 5px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 3px;
    }
    .linha {
      display: flex;
      margin: 5px 0;
    }
    .label {
      font-weight: bold;
      width: 120px;
      color: #555;
    }
    .valor {
      flex: 1;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    th {
      background: #f5f5f5;
      font-weight: bold;
    }
    .totais {
      margin-top: 20px;
      text-align: right;
    }
    .total-final {
      font-size: 14pt;
      font-weight: bold;
      color: #008bcd;
    }
    .assinaturas {
      margin-top: 50px;
      display: flex;
      justify-content: space-between;
    }
    .assinatura {
      text-align: center;
      width: 200px;
    }
    .linha-assinatura {
      border-top: 1px solid #333;
      margin-top: 30px;
      padding-top: 5px;
    }
    .termos {
      margin-top: 30px;
      padding: 10px;
      background: #f9f9f9;
      font-size: 9pt;
      border: 1px solid #eee;
    }
    .status {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 3px;
      font-size: 10pt;
      font-weight: bold;
    }
    .status-aguardando { background: #ffc107; color: #000; }
    .status-aprovado { background: #28a745; color: #fff; }
    .status-reprovado { background: #dc3545; color: #fff; }
    .status-expirado { background: #6c757d; color: #fff; }
    .status-convertido { background: #17a2b8; color: #fff; }
    @media print {
      body { padding: 0; }
      .no-print { display: none; }
    }
    .btn-print {
      display: block;
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      background: #008bcd;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12pt;
    }
  </style>
</head>
<body>
  <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Orçamento</button>

  <div class="header">
    <div class="nome-empresa"><?= htmlspecialchars((string) ($empresa['nome'] ?? 'Sapataria Modelo'), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="dados-empresa">
      <?php if (!empty($empresa['cnpj'])): ?>CNPJ: <?= htmlspecialchars((string) $empresa['cnpj'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
      <?php if (!empty($empresa['endereco'])): ?><?= htmlspecialchars((string) $empresa['endereco'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
      <?php if (!empty($empresa['telefone'])): ?>Tel: <?= htmlspecialchars((string) $empresa['telefone'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
    </div>
  </div>

  <div class="titulo">
    ORÇAMENTO Nº <?= htmlspecialchars((string) ($orcamento['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    <span class="status status-<?= strtolower((string) ($orcamento['status'] ?? 'aguardando')) ?>">
      <?= htmlspecialchars((string) ($orcamento['status'] ?? 'Aguardando'), ENT_QUOTES, 'UTF-8') ?>
    </span>
  </div>

  <div class="secao">
    <div class="secao-titulo">DADOS DO CLIENTE</div>
    <div class="linha">
      <span class="label">Nome:</span>
      <span class="valor"><?= htmlspecialchars((string) ($orcamento['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if ($orcamento['cliente_telefone']): ?>
    <div class="linha">
      <span class="label">Telefone:</span>
      <span class="valor"><?= htmlspecialchars((string) $orcamento['cliente_telefone'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>
    <?php if ($orcamento['cliente_cpf']): ?>
    <div class="linha">
      <span class="label">CPF:</span>
      <span class="valor"><?= htmlspecialchars((string) $orcamento['cliente_cpf'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>
  </div>

  <div class="secao">
    <div class="secao-titulo">ITENS/SERVIÇOS</div>
    <?php if (empty($sapatos)): ?>
      <p style="color: #999; font-style: italic;">Nenhum item adicionado ao orçamento.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Categoria</th>
            <th>Serviço</th>
            <th style="text-align: right;">Valor</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sapatos as $index => $s): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars((string) $s['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?= htmlspecialchars((string) $s['tipo_servico'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($s['modelo']): ?>
                  <br><small style="color: #666;"><?= htmlspecialchars($s['modelo'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="totais">
    <?php if ($orcamento['desconto'] > 0): ?>
      <div>Subtotal: R$ <?= number_format((float) ($orcamento['valor_total'] ?? 0), 2, ',', '.') ?></div>
      <div style="color: #dc3545;">Desconto: - R$ <?= number_format((float) $orcamento['desconto'], 2, ',', '.') ?></div>
    <?php endif; ?>
    <div class="total-final">
      TOTAL: R$ <?= number_format((float) ($orcamento['valor_final'] ?? 0), 2, ',', '.') ?>
    </div>
  </div>

  <?php if ($orcamento['validade']): ?>
  <div class="secao" style="margin-top: 20px;">
    <div class="linha">
      <span class="label">Validade:</span>
      <span class="valor"><?= date('d/m/Y', strtotime((string) $orcamento['validade'])) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($orcamento['observacoes']): ?>
  <div class="secao">
    <div class="secao-titulo">OBSERVAÇÕES</div>
    <p><?= nl2br(htmlspecialchars((string) $orcamento['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
  </div>
  <?php endif; ?>

  <div class="termos">
    <strong>TERMOS E CONDIÇÕES:</strong><br>
    • Este orçamento é válido por 30 dias a partir da data de emissão.<br>
    • O prazo de entrega pode variar de acordo com a demanda.<br>
    • Garantia de 30 dias para o serviço executado.<br>
    • Não nos responsabilizamos por objetos deixados após 90 dias.
  </div>

  <div class="assinaturas">
    <div class="assinatura">
      <div class="linha-assinatura"></div>
      <div>Assinatura do Cliente</div>
    </div>
    <div class="assinatura">
      <div class="linha-assinatura"></div>
      <div>Assinatura da Sapataria</div>
    </div>
  </div>

  <div style="text-align: center; margin-top: 30px; font-size: 9pt; color: #999;">
    Documento gerado em <?= date('d/m/Y H:i:s') ?>
  </div>

  <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Orçamento</button>

  <script>
    window.onload = function() {
      if (window.location.search.includes('auto=1')) {
        setTimeout(function() {
          window.print();
        }, 500);
      }
    };
  </script>
</body>
</html>
