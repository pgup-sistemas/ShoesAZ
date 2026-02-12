<?php

use App\Core\Csrf;

$recibo = $recibo ?? [];
$pagamentos = $pagamentos ?? [];
$sapatos = $sapatos ?? [];
$linkPublico = $linkPublico ?? null;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Recibo <?= htmlspecialchars((string) ($recibo['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="text-muted small">Emitido em <?= date('d/m/Y H:i', strtotime((string) $recibo['created_at'])) ?> por <?= htmlspecialchars((string) ($recibo['created_by_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= \App\Core\View::url('/recibos/imprimir') ?>?id=<?= (int) $recibo['id'] ?>" target="_blank" class="btn btn-outline-secondary">Imprimir</a>
    <a href="<?= \App\Core\View::url('/os/edit') ?>?id=<?= (int) $recibo['os_id'] ?>" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="text-muted">Cliente</h6>
            <p class="mb-1 fw-semibold"><?= htmlspecialchars((string) ($recibo['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-muted"><?= htmlspecialchars((string) ($recibo['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="col-md-6 text-md-end">
            <h6 class="text-muted">OS de Referência</h6>
            <p class="fw-semibold"><?= htmlspecialchars((string) ($recibo['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <h6 class="text-muted mt-3">Data de Entrada</h6>
            <p><?= isset($recibo['data_entrada']) ? date('d/m/Y', strtotime($recibo['data_entrada'])) : '-' ?></p>
          </div>
        </div>

        <h6 class="text-muted mb-3">Serviços Realizados</h6>
        <div class="table-responsive mb-4">
          <table class="table table-sm">
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
                <td class="text-end"><strong>R$ <?= number_format((float) ($recibo['valor_total'] ?? 0), 2, ',', '.') ?></strong></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <h6 class="text-muted mb-3">Pagamentos</h6>
        <div class="table-responsive mb-4">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Parcela</th>
                <th>Forma</th>
                <th>Data</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pagamentos as $p): ?>
                <tr>
                  <td>#<?= (int) $p['parcela_numero'] ?></td>
                  <td><?= htmlspecialchars((string) ($p['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $p['data_pagamento'] ? date('d/m/Y', strtotime($p['data_pagamento'])) : '-' ?></td>
                  <td class="text-end">R$ <?= number_format((float) $p['valor'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="alert alert-info">
          <strong>Garantia:</strong> <?= (int) ($recibo['garantia_dias'] ?? 30) ?> dias<br>
          <strong>Termos:</strong> <?= nl2br(htmlspecialchars((string) ($recibo['termos'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-primary">
      <div class="card-body">
        <div class="fw-semibold mb-2">Compartilhar</div>
        <?php if ($linkPublico): ?>
          <div class="input-group mb-2">
            <input class="form-control form-control-sm" id="link-publico" value="<?= ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/public?token=<?= htmlspecialchars((string) $linkPublico['token'], ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('link-publico').value); showToast('Link copiado!', 'success')">Copiar</button>
          </div>
          <a class="btn btn-success w-100" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', (string) ($recibo['cliente_telefone'] ?? '')) ?>?text=<?= urlencode('Olá! Segue o link do seu recibo: ' . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public?token=' . $linkPublico['token']) ?>">
            Enviar WhatsApp
          </a>
        <?php else: ?>
          <p class="text-muted small">Link público não disponível.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
