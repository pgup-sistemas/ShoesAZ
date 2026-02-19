<?php
// Gerar URL pública para QR Code
$linkPublicoUrl = '';
if (isset($os['id'])) {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = \App\Core\View::url('');
    if ($linkPublico && is_array($linkPublico) && !empty($linkPublico['token'])) {
        $linkPublicoUrl = $scheme . '://' . $host . $basePath . '/public?token=' . $linkPublico['token'];
    } else {
        // Fallback se o token não estiver disponível
        $linkPublicoUrl = $scheme . '://' . $host . $basePath . '/public?token=os-' . $os['id'];
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Etiqueta <?= htmlspecialchars((string) ($os['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page {
      size: 100mm auto;
      margin: 0;
    }
    body {
      font-family: Arial, sans-serif;
      font-size: 12pt;
      line-height: 1.4;
      padding: 8mm;
      max-width: 100mm;
      margin: 0 auto;
    }
    .etiqueta {
      border: 2px solid #333;
      padding: 8mm;
      border-radius: 3mm;
    }
    .header {
      text-align: center;
      border-bottom: 1px solid #ccc;
      padding-bottom: 4mm;
      margin-bottom: 4mm;
    }
    .nome-empresa {
      font-size: 14pt;
      font-weight: bold;
      color: #008bcd;
    }
    .dados-os {
      margin: 4mm 0;
    }
    .linha {
      display: flex;
      justify-content: space-between;
      margin: 2mm 0;
    }
    .label {
      font-weight: bold;
      color: #555;
    }
    .valor {
      text-align: right;
    }
    .qr-code {
      text-align: center;
      margin-top: 5mm;
      padding: 3mm;
    }
    .qr-code img {
      width: 25mm;
      height: 25mm;
    }
    .qr-texto {
      font-size: 8pt;
      color: #666;
      margin-top: 1mm;
    }
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
  <button class="btn-print no-print" onclick="window.print()">Imprimir Etiqueta</button>

  <div class="etiqueta">
    <div class="header">
      <div class="nome-empresa"><?= htmlspecialchars((string) ($empresa['nome'] ?? 'SAPATARIA MODELO'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php if (!empty($empresa['telefone'])): ?>
        <div style="font-size: 9pt; color: #666; margin-top: 1mm;">Tel: <?= htmlspecialchars((string) $empresa['telefone'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>

    <div class="dados-os">
      <div class="linha">
        <span class="label">OS:</span>
        <span class="valor">#<?= htmlspecialchars((string) ($os['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="linha">
        <span class="label">Cliente:</span>
        <span class="valor"><?= htmlspecialchars(substr((string) ($os['cliente_nome'] ?? ''), 0, 20), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="linha">
        <span class="label">Entrada:</span>
        <span class="valor"><?= isset($os['data_entrada']) ? date('d/m/Y', strtotime($os['data_entrada'])) : '-' ?></span>
      </div>
      <div class="linha">
        <span class="label">Entrega:</span>
        <span class="valor"><?= isset($os['prazo_entrega']) ? date('d/m/Y', strtotime($os['prazo_entrega'])) : '-' ?></span>
      </div>
    </div>

    <div style="border-top: 1px solid #ccc; padding-top: 3mm; margin-top: 3mm;">
      <div class="linha">
        <span class="label">Serviço:</span>
      </div>
      <div style="font-size: 10pt; margin-top: 1mm;">
        <?php
        $servicos = [];
        foreach ($sapatos as $s) {
            $servicos[] = $s['tipo_servico'];
        }
        echo htmlspecialchars(implode(', ', array_unique($servicos)), ENT_QUOTES, 'UTF-8');
        ?>
      </div>
      <div class="linha" style="margin-top: 2mm;">
        <span class="label">Qtd:</span>
        <span class="valor"><?= count($sapatos) ?> par(es)</span>
      </div>
      <div class="linha">
        <span class="label">Local:</span>
        <span class="valor"><?= htmlspecialchars((string) ($os['localizacao'] ?? 'Não definido'), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <div class="qr-code">
      <?php if ($linkPublicoUrl): ?>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($linkPublicoUrl) ?>" alt="QR Code">
        <div class="qr-texto">Escaneie para acompanhar</div>
      <?php else: ?>
        <div style="border: 1px dashed #999; padding: 5mm; background: #f5f5f5; font-size: 9pt; color: #666;">
          [ QR CODE ]<br>
          <small>Escaneie para acompanhar</small>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <button class="btn-print no-print" onclick="window.print()">Imprimir Etiqueta</button>

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
