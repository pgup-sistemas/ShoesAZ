<?php

use App\Core\Csrf;

$os = $os ?? [];
$sapatos = $sapatos ?? [];
$sapateiros = $sapateiros ?? [];
$linkPublico = $linkPublico ?? null;
$isSapateiro = $isSapateiro ?? false;
$pagamentos = $pagamentos ?? [];
$valorPago = $valorPago ?? 0;
$valorRestante = $valorRestante ?? 0;

$statusList = ['Recebido', 'Em reparo', 'Aguardando retirada', 'Entregue', 'Cancelado'];

$hoje = date('Y-m-d');
$prazo = (string) ($os['prazo_entrega'] ?? '');
$diasAtraso = 0;
$alertaPrazo = '';
if ($prazo !== '' && !in_array($os['status'], ['Entregue', 'Cancelado'])) {
    $diff = strtotime($prazo) - strtotime($hoje);
    $dias = (int) floor($diff / 86400);
    if ($dias < 0) {
        $alertaPrazo = 'text-danger fw-bold';
        $diasAtraso = abs($dias);
    } elseif ($dias <= 2) {
        $alertaPrazo = 'text-warning fw-bold';
    }
}

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">OS <?= htmlspecialchars((string) $os['numero'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="text-muted small">
      Cliente: <?= htmlspecialchars((string) ($os['cliente_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?> 
      | Tel: <?= htmlspecialchars((string) ($os['cliente_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <?php if (!$isSapateiro): ?>
      <a class="btn btn-outline-secondary" href="<?= \App\Core\View::url('/os/etiqueta') ?>?id=<?= (int) $os['id'] ?>" target="_blank">Imprimir Etiqueta</a>
      <a class="btn btn-outline-primary" href="<?= \App\Core\View::url('/recibos/create') ?>?os_id=<?= (int) $os['id'] ?>">Emitir Recibo</a>
    <?php endif; ?>
    <a href="<?= \App\Core\View::url('/os') ?>" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= \App\Core\View::url('/os/update') ?>?id=<?= (int) $os['id'] ?>" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="col-12 col-md-6">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <?php foreach ($statusList as $s): ?>
                <option value="<?= $s ?>" <?= ((string) ($os['status'] ?? '')) === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (!$isSapateiro): ?>
            <div class="col-12 col-md-6">
              <label class="form-label">Sapateiro Responsável</label>
              <select class="form-select" name="sapateiro_id">
                <option value="">Não atribuído</option>
                <?php foreach ($sapateiros as $sp): ?>
                  <option value="<?= (int) $sp['id'] ?>" <?= ((int) ($os['sapateiro_id'] ?? 0)) === (int) $sp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $sp['nome'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Prazo de Entrega *</label>
              <input class="form-control <?= $alertaPrazo ?>" type="date" name="prazo_entrega" required value="<?= htmlspecialchars($prazo, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($diasAtraso > 0): ?>
                <div class="text-danger small">Atrasado há <?= $diasAtraso ?> dia(s)</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="col-12 col-md-6">
            <label class="form-label">Localização Física</label>
            <input class="form-control" name="localizacao" value="<?= htmlspecialchars((string) ($os['localizacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Prateleira A - Caixa 3">
          </div>

          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" rows="3" name="observacoes"><?= htmlspecialchars((string) ($os['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12">
            <button class="btn btn-primary" type="submit">Salvar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Sapatos/Serviços</span>
        <?php if (!$isSapateiro): ?>
          <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('form-sapato').classList.toggle('d-none')">+ Adicionar</button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$isSapateiro): ?>
          <div id="form-sapato" class="d-none border rounded p-3 mb-3 bg-light">
            <form method="post" action="<?= \App\Core\View::url('/sapatos/store') ?>?os_id=<?= (int) $os['id'] ?>" class="row g-2" enctype="multipart/form-data">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
              <div class="col-12 col-md-3">
                <label class="form-label">Categoria *</label>
                <select class="form-select" name="categoria" required>
                  <option value="">Selecione...</option>
                  <option value="Social">Social</option>
                  <option value="Tênis">Tênis</option>
                  <option value="Bota">Bota</option>
                  <option value="Sandália">Sandália</option>
                  <option value="Sapatênis">Sapatênis</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Tipo de Serviço *</label>
                <select class="form-select" name="tipo_servico" required>
                  <option value="">Selecione...</option>
                  <option value="Conserto de solado">Conserto de solado</option>
                  <option value="Troca de sola completa">Troca de sola completa</option>
                  <option value="Costura">Costura</option>
                  <option value="Pintura/Tingimento">Pintura/Tingimento</option>
                  <option value="Colocação de salto">Colocação de salto</option>
                  <option value="Alongamento">Alongamento</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="col-12 col-md-2">
                <label class="form-label">Valor (R$) *</label>
                <input class="form-control" type="number" step="0.01" min="0" name="valor" required>
              </div>
              <div class="col-12 col-md-2">
                <label class="form-label">Cor</label>
                <input class="form-control" name="cor">
              </div>
              <div class="col-12 col-md-2">
                <label class="form-label">Marca</label>
                <input class="form-control" name="marca">
              </div>
              <div class="col-12">
                <label class="form-label">Modelo/Descrição</label>
                <input class="form-control" name="modelo">
              </div>
              <div class="col-12">
                <label class="form-label">Observações</label>
                <textarea class="form-control" rows="2" name="observacoes"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Fotos (opcional - máx 4 fotos)</label>
                <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple id="foto-input" onchange="previewFotosOS(this)">
                <div id="foto-preview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                <small class="text-muted">JPG, PNG ou WebP. Máximo 5MB cada.</small>
              </div>
              <div class="col-12">
                <button class="btn btn-success" type="submit">Adicionar Sapato</button>
                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('form-sapato').classList.add('d-none')">Cancelar</button>
              </div>
            </form>
          </div>
        <?php endif; ?>

        <?php if (!$sapatos): ?>
          <div class="text-muted text-center p-3">Nenhum sapato na OS.</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($sapatos as $s): 
              $fotos = json_decode((string) ($s['fotos'] ?? '[]'), true) ?: [];
            ?>
              <div class="col-12">
                <div class="card border">
                  <div class="card-body">
                    <div class="row g-2">
                      <div class="col-md-8">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <h6 class="mb-1"><?= htmlspecialchars((string) $s['categoria'], ENT_QUOTES, 'UTF-8') ?> 
                              <small class="text-muted"><?= htmlspecialchars((string) ($s['modelo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </h6>
                            <div class="text-muted small">
                              <?= htmlspecialchars((string) $s['tipo_servico'], ENT_QUOTES, 'UTF-8') ?> 
                              <?= $s['cor'] ? ' | Cor: ' . htmlspecialchars($s['cor']) : '' ?>
                              <?= $s['marca'] ? ' | Marca: ' . htmlspecialchars($s['marca']) : '' ?>
                            </div>
                            <?php if ($s['observacoes']): ?>
                              <div class="small text-secondary mt-1"><?= htmlspecialchars($s['observacoes']) ?></div>
                            <?php endif; ?>
                          </div>
                          <div class="fw-bold text-primary">R$ <?= number_format((float) $s['valor'], 2, ',', '.') ?></div>
                        </div>
                        
                        <?php if (!empty($fotos)): ?>
                          <div class="d-flex gap-2 mt-3 flex-wrap">
                            <?php foreach ($fotos as $index => $foto): ?>
                              <?php
                                $fotoPath = is_string($foto) ? $foto : (string) $foto;
                                if (str_starts_with($fotoPath, '/uploads/')) {
                                  $fotoPath = '/public' . $fotoPath;
                                }
                                $fotoUrl = str_starts_with($fotoPath, '/') ? \App\Core\View::url($fotoPath) : $fotoPath;
                              ?>
                              <div class="position-relative">
                                <img src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" 
                                     class="rounded cursor-pointer" 
                                     style="width: 80px; height: 80px; object-fit: cover;"
                                     onclick="abrirFotoModal('<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>')"
                                     alt="Foto do sapato">
                                <?php if (!$isSapateiro): ?>
                                  <form method="post" action="<?= \App\Core\View::url('/sapatos/remover-foto') ?>" class="position-absolute top-0 end-0">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="sapato_id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="foto_index" value="<?= $index ?>">
                                    <input type="hidden" name="os_id" value="<?= (int) $os['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger p-0" style="width: 20px; height: 20px; font-size: 10px;" onclick="return confirm('Remover esta foto?');">×</button>
                                  </form>
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                        
                        <?php if (!$isSapateiro && count($fotos) < 4): ?>
                          <form method="post" action="<?= \App\Core\View::url('/sapatos/upload-foto') ?>" enctype="multipart/form-data" class="mt-3 d-flex gap-2 align-items-center">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="sapato_id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="os_id" value="<?= (int) $os['id'] ?>">
                            <input type="file" name="foto" accept="image/*" class="form-control form-control-sm" style="max-width: 200px;" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary">+ Foto</button>
                          </form>
                        <?php endif; ?>
                      </div>
                      <div class="col-md-4 text-md-end">
                        <?php if (!$isSapateiro): ?>
                          <form method="post" action="<?= \App\Core\View::url('/sapatos/destroy') ?>?id=<?= (int) $s['id'] ?>&os_id=<?= (int) $os['id'] ?>" class="d-inline" onsubmit="return confirm('Remover este item?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Remover Sapato</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <?php if (!$isSapateiro): ?>
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Pagamentos</span>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalEntrada50()">Entrada 50%</button>
            <?php if ((float) $valorRestante > 0): ?>
              <button type="button" class="btn btn-sm btn-success" onclick="abrirModalReceberTotal()">Receber Total</button>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalParcela()">+ Parcela</button>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="text-muted small">Pago</div>
              <div class="fw-bold text-success">R$ <?= number_format((float) $valorPago, 2, ',', '.') ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Restante</div>
              <div class="fw-bold">R$ <?= number_format((float) $valorRestante, 2, ',', '.') ?></div>
            </div>
          </div>

          <?php if (!$pagamentos): ?>
            <div class="text-muted text-center py-2">Nenhuma parcela lançada.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th class="text-end">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pagamentos as $p): ?>
                    <?php
                      $statusPg = (string) ($p['status'] ?? '');
                      $badge = $statusPg === 'Pago' ? 'bg-success' : 'bg-secondary';
                    ?>
                    <tr>
                      <td><?= (int) ($p['parcela_numero'] ?? 0) ?></td>
                      <td>
                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($statusPg ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($p['data_pagamento'])): ?>
                          <div class="text-muted small"><?= date('d/m/Y', strtotime((string) $p['data_pagamento'])) ?></div>
                        <?php elseif (!empty($p['vencimento'])): ?>
                          <div class="text-muted small">Vence: <?= date('d/m/Y', strtotime((string) $p['vencimento'])) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div>R$ <?= number_format((float) ($p['valor'] ?? 0), 2, ',', '.') ?></div>
                        <?php if ($statusPg !== 'Pago'): ?>
                          <button type="button" class="btn btn-sm btn-success mt-1" onclick="abrirModalQuitar(<?= (int) ($p['id'] ?? 0) ?>, <?= (float) ($p['valor'] ?? 0) ?>)">Quitar</button>
                        <?php else: ?>
                          <div class="text-muted small"><?= htmlspecialchars((string) ($p['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Resumo</div>
        <div class="d-flex justify-content-between fs-5 fw-bold">
          <span>Valor Total:</span>
          <span>R$ <?= number_format((float) ($os['valor_total'] ?? 0), 2, ',', '.') ?></span>
        </div>
        <div class="text-muted small mt-2">Data entrada: <?= date('d/m/Y', strtotime((string) ($os['data_entrada'] ?? 'now'))) ?></div>
      </div>
    </div>

    <?php if ($linkPublico && !$isSapateiro): ?>
      <div class="card mt-3 border-info">
        <div class="card-body">
          <div class="fw-semibold mb-2">Link Público</div>
          <div class="input-group mb-2">
            <input class="form-control form-control-sm" id="link-publico" value="<?= ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/public?token=<?= htmlspecialchars((string) $linkPublico['token'], ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('link-publico').value); showToast('Link copiado!', 'success')">Copiar</button>
          </div>
          <a class="btn btn-success w-100" target="_blank" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', (string) ($os['cliente_telefone'] ?? '')) ?>?text=<?= urlencode('Olá! Segue o link para acompanhar seu serviço: ' . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public?token=' . $linkPublico['token']) ?>">
            Enviar WhatsApp
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!$isSapateiro): ?>
  <div id="modal-receber-total" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
    <div class="d-flex justify-content-center align-items-center h-100">
      <div class="card" style="width: 420px;">
        <div class="card-body">
          <h5 class="card-title">Receber Total (Adiantado)</h5>
          <form method="post" action="<?= \App\Core\View::url('/pagamentos/store') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="os_id" value="<?= (int) ($os['id'] ?? 0) ?>">
            <input type="hidden" name="status" value="Pago">

            <div class="mb-3">
              <label class="form-label">Valor</label>
              <input type="text" class="form-control" id="receber-total-valor" value="" readonly>
              <input type="hidden" name="valor" id="receber-total-valor-hidden" value="">
              <div class="text-muted small mt-1">Vai entrar no Caixa na data de hoje.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Forma de Pagamento</label>
              <select class="form-select" name="forma_pagamento" required>
                <option value="Dinheiro">Dinheiro</option>
                <option value="PIX">PIX</option>
                <option value="Cartão Débito">Cartão Débito</option>
                <option value="Cartão Crédito">Cartão Crédito</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-success">Receber</button>
              <button type="button" class="btn btn-outline-secondary" onclick="fecharModal('modal-receber-total')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="modal-parcela" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
    <div class="d-flex justify-content-center align-items-center h-100">
      <div class="card" style="width: 420px;">
        <div class="card-body">
          <h5 class="card-title">Nova Parcela (Pendente)</h5>
          <form method="post" action="<?= \App\Core\View::url('/pagamentos/store') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="os_id" value="<?= (int) ($os['id'] ?? 0) ?>">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" onchange="toggleFormaPagamento(this, 'parcela-forma-pagamento')" required>
                <option value="Pendente" selected>Pendente</option>
                <option value="Pago">Pago</option>
              </select>
              <div class="text-muted small mt-1">Se marcar como <strong>Pago</strong>, o valor entra no caixa de hoje (se houver caixa aberto).</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Valor (R$) *</label>
              <input id="parcela-valor" type="number" step="0.01" min="0" class="form-control" name="valor" required>
              <div class="text-muted small mt-1">Restante atual: R$ <?= number_format((float) $valorRestante, 2, ',', '.') ?></div>
            </div>

            <div class="mb-3" id="parcela-forma-pagamento" style="display:none;">
              <label class="form-label">Forma de Pagamento</label>
              <select class="form-select" name="forma_pagamento">
                <option value="">Selecione</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="PIX">PIX</option>
                <option value="Cartão Débito">Cartão Débito</option>
                <option value="Cartão Crédito">Cartão Crédito</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Vencimento (opcional)</label>
              <input type="date" class="form-control" name="vencimento">
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Salvar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="fecharModal('modal-parcela')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="modal-entrada" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
    <div class="d-flex justify-content-center align-items-center h-100">
      <div class="card" style="width: 420px;">
        <div class="card-body">
          <h5 class="card-title">Entrada 50% (Pendente)</h5>
          <form method="post" action="<?= \App\Core\View::url('/pagamentos/store') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="os_id" value="<?= (int) ($os['id'] ?? 0) ?>">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" onchange="toggleFormaPagamento(this, 'entrada-forma-pagamento')" required>
                <option value="Pendente" selected>Pendente</option>
                <option value="Pago">Pago</option>
              </select>
              <div class="text-muted small mt-1">Se marcar como <strong>Pago</strong>, o valor entra no caixa de hoje (se houver caixa aberto).</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Valor (R$)</label>
              <input id="entrada-valor" type="number" step="0.01" min="0" class="form-control" name="valor" required>
              <div class="text-muted small mt-1">Sugestão: 50% do restante.</div>
            </div>

            <div class="mb-3" id="entrada-forma-pagamento" style="display:none;">
              <label class="form-label">Forma de Pagamento</label>
              <select class="form-select" name="forma_pagamento">
                <option value="">Selecione</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="PIX">PIX</option>
                <option value="Cartão Débito">Cartão Débito</option>
                <option value="Cartão Crédito">Cartão Crédito</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Vencimento (opcional)</label>
              <input type="date" class="form-control" name="vencimento">
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Salvar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="fecharModal('modal-entrada')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="modal-quitar" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
    <div class="d-flex justify-content-center align-items-center h-100">
      <div class="card" style="width: 420px;">
        <div class="card-body">
          <h5 class="card-title">Quitar Parcela</h5>
          <form method="post" action="<?= \App\Core\View::url('/pagamentos/quitar') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="quitar-id" value="">

            <div class="mb-3">
              <label class="form-label">Valor</label>
              <input type="text" class="form-control" id="quitar-valor" value="" readonly>
              <div class="text-muted small mt-1">Valor fixo da parcela.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Forma de Pagamento</label>
              <select class="form-select" name="forma_pagamento" required>
                <option value="Dinheiro">Dinheiro</option>
                <option value="PIX">PIX</option>
                <option value="Cartão Débito">Cartão Débito</option>
                <option value="Cartão Crédito">Cartão Crédito</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-success">Quitar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="fecharModal('modal-quitar')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
function abrirModal(id) {
  document.getElementById(id)?.classList.remove('d-none');
}

function fecharModal(id) {
  document.getElementById(id)?.classList.add('d-none');
}

function toggleFormaPagamento(selectEl, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const isPago = (selectEl && selectEl.value === 'Pago');
  container.style.display = isPago ? '' : 'none';
  const forma = container.querySelector('select[name="forma_pagamento"]');
  if (forma) {
    forma.required = isPago;
    if (!isPago) forma.value = '';
  }
}

function abrirModalParcela() {
  const input = document.getElementById('parcela-valor');
  if (input) input.value = '';
  abrirModal('modal-parcela');
}

function abrirModalEntrada50() {
  const restante = <?= json_encode((float) $valorRestante) ?>;
  const sugestao = Math.max(0, restante * 0.5);
  const input = document.getElementById('entrada-valor');
  if (input) input.value = sugestao.toFixed(2);
  abrirModal('modal-entrada');
}

function abrirModalQuitar(id, valor) {
  const idInput = document.getElementById('quitar-id');
  const valorInput = document.getElementById('quitar-valor');
  if (idInput) idInput.value = String(id);
  if (valorInput) {
    const v = (typeof valor === 'number') ? valor : parseFloat(valor);
    valorInput.value = 'R$ ' + (isFinite(v) ? v.toFixed(2).replace('.', ',') : '0,00');
  }
  abrirModal('modal-quitar');
}

function abrirModalReceberTotal() {
  const restante = <?= json_encode((float) $valorRestante) ?>;
  const v = Math.max(0, restante);
  const valorTxt = document.getElementById('receber-total-valor');
  const valorHidden = document.getElementById('receber-total-valor-hidden');
  if (valorTxt) valorTxt.value = 'R$ ' + v.toFixed(2).replace('.', ',');
  if (valorHidden) valorHidden.value = v.toFixed(2);
  abrirModal('modal-receber-total');
}

// Preview de fotos antes do upload
function previewFotosOS(input) {
  const preview = document.getElementById('foto-preview');
  preview.innerHTML = '';
  
  if (input.files && input.files.length > 0) {
    if (input.files.length > 4) {
      alert('Máximo 4 fotos permitidas.');
      input.value = '';
      return;
    }
    
    Array.from(input.files).forEach(file => {
      if (file.size > 5 * 1024 * 1024) {
        alert('Arquivo ' + file.name + ' é muito grande. Máximo 5MB.');
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'rounded';
        img.style.width = '80px';
        img.style.height = '80px';
        img.style.objectFit = 'cover';
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  }
}

// Abrir foto em modal
function abrirFotoModal(src) {
  const modal = document.createElement('div');
  modal.className = 'position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center';
  modal.style.zIndex = '2000';
  modal.innerHTML = `
    <div class="position-relative">
      <img src="${src}" class="img-fluid rounded" style="max-width: 90vw; max-height: 90vh;">
      <button type="button" class="btn btn-light position-absolute top-0 end-0 m-2" onclick="this.parentElement.parentElement.remove()">×</button>
    </div>
  `;
  modal.onclick = function(e) {
    if (e.target === modal) modal.remove();
  };
  document.body.appendChild(modal);
}
</script>
