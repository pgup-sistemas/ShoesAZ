<?php

use App\Core\Csrf;

$caixa = $caixa ?? null;
$data = is_string($data ?? null) ? $data : date('Y-m-d');
$historico = $historico ?? [];
$receitasDoDia = $receitasDoDia ?? [];
$despesasDoDia = $despesasDoDia ?? [];
$movimentacoes = $movimentacoes ?? [];

$isOpen = $caixa && (string) ($caixa['status'] ?? '') === 'Aberto';
$totalReceitas = array_sum(array_column($receitasDoDia, 'valor'));
$totalDespesas = array_sum(array_column($despesasDoDia, 'valor'));

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">Caixa - <?= date('d/m/Y', strtotime($data)) ?></div>
    <div class="text-muted small">Controle de entradas e saídas</div>
  </div>
  <div class="d-flex gap-2">
    <?php if (!$isOpen && $data === date('Y-m-d')): ?>
      <button class="btn btn-success" onclick="document.getElementById('modal-abrir').classList.remove('d-none')">Abrir Caixa</button>
    <?php elseif ($isOpen): ?>
      <button class="btn btn-danger" onclick="document.getElementById('modal-fechar').classList.remove('d-none')">Fechar Caixa</button>
      <button class="btn btn-warning" onclick="document.getElementById('modal-retirada').classList.remove('d-none')">Retirada</button>
      <form method="post" action="<?= \App\Core\View::url('/caixa/importar-pagamentos') ?>" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Importar para o caixa os pagamentos do dia que estão como Pago mas não entraram no caixa?');">Importar Pagamentos</button>
      </form>
    <?php endif; ?>
    <a href="<?= \App\Core\View::url('/despesas/create') ?>" class="btn btn-outline-secondary">+ Despesa</a>
  </div>
</div>

<?php if ($caixa): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body text-center">
          <div class="text-muted small">Saldo Inicial</div>
          <div class="fs-4 fw-bold">R$ <?= number_format((float) ($caixa['saldo_inicial'] ?? 0), 2, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body text-center">
          <div class="small">Receitas</div>
          <div class="fs-4 fw-bold">R$ <?= number_format((float) ($caixa['receitas'] ?? 0), 2, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-danger text-white">
        <div class="card-body text-center">
          <div class="small">Despesas</div>
          <div class="fs-4 fw-bold">R$ <?= number_format((float) ($caixa['despesas'] ?? 0), 2, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body text-center">
          <div class="small">Saldo Esperado</div>
          <div class="fs-4 fw-bold">R$ <?= number_format((float) ($caixa['saldo_esperado'] ?? 0), 2, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning">
        <div class="card-body text-center">
          <div class="small">Retiradas</div>
          <div class="fs-4 fw-bold">R$ <?= number_format((float) ($caixa['retiradas'] ?? 0), 2, ',', '.') ?></div>
        </div>
      </div>
    </div>
  </div>

  <?php if ((string) ($caixa['status'] ?? '') === 'Fechado'): ?>
    <div class="alert alert-info mb-4">
      <strong>Caixa Fechado</strong><br>
      Saldo Esperado: R$ <?= number_format((float) ($caixa['saldo_esperado'] ?? 0), 2, ',', '.') ?><br>
      Saldo Real: R$ <?= number_format((float) ($caixa['saldo_real'] ?? 0), 2, ',', '.') ?> |
      Diferença: R$ <?= number_format((float) ($caixa['diferenca'] ?? 0), 2, ',', '.') ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if ($caixa): ?>
  <div class="card mb-4">
    <div class="card-header fw-semibold">Fluxo do Caixa (Sessão)</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>Horário</th>
              <th>Tipo</th>
              <th>Usuário</th>
              <th>Motivo</th>
              <th class="text-end">Valor</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$movimentacoes): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">Sem movimentações registradas</td></tr>
            <?php else: ?>
              <?php foreach ($movimentacoes as $m): ?>
                <?php
                  $tipo = (string) ($m['tipo'] ?? '');
                  $rowClass = '';
                  if ($tipo === 'Abertura') {
                    $rowClass = 'table-success';
                  } elseif ($tipo === 'Retirada') {
                    $rowClass = 'table-warning';
                  } elseif ($tipo === 'Fechamento') {
                    $rowClass = 'table-info';
                  }
                ?>
                <tr class="<?= $rowClass ?>">
                  <td><?= isset($m['created_at']) ? date('H:i', strtotime((string) $m['created_at'])) : '-' ?></td>
                  <td><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($m['created_by_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($m['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end"><?= $m['valor'] !== null ? ('R$ ' . number_format((float) $m['valor'], 2, ',', '.')) : '-' ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header fw-semibold">Receitas do Dia</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>OS</th>
                <th>Forma</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$receitasDoDia): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">Nenhuma receita hoje</td></tr>
              <?php else: ?>
                <?php foreach ($receitasDoDia as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($r['os_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['forma_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">R$ <?= number_format((float) $r['valor'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="table-success fw-bold">
                  <td colspan="2">Total</td>
                  <td class="text-end">R$ <?= number_format($totalReceitas, 2, ',', '.') ?></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header fw-semibold">Despesas do Dia</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Descrição</th>
                <th>Categoria</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$despesasDoDia): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">Nenhuma despesa hoje</td></tr>
              <?php else: ?>
                <?php foreach ($despesasDoDia as $d): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($d['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($d['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">R$ <?= number_format((float) $d['valor'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="table-danger fw-bold">
                  <td colspan="2">Total</td>
                  <td class="text-end">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Abrir Caixa -->
<div id="modal-abrir" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
  <div class="d-flex justify-content-center align-items-center h-100">
    <div class="card" style="width: 400px;">
      <div class="card-body">
        <h5 class="card-title">Abrir Caixa</h5>
        <form method="post" action="<?= \App\Core\View::url('/caixa/abrir') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3">
            <label class="form-label">Saldo Inicial (R$)</label>
            <input type="number" step="0.01" class="form-control" name="saldo_inicial" value="0" required>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Abrir</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-abrir').classList.add('d-none')">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Fechar Caixa -->
<?php if ($isOpen): ?>
<div id="modal-fechar" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
  <div class="d-flex justify-content-center align-items-center h-100">
    <div class="card" style="width: 400px;">
      <div class="card-body">
        <h5 class="card-title">Fechar Caixa</h5>
        <form method="post" action="<?= \App\Core\View::url('/caixa/fechar') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id" value="<?= (int) ($caixa['id'] ?? 0) ?>">
          <div class="mb-3">
            <label class="form-label">Saldo Real (R$)</label>
            <input type="number" step="0.01" class="form-control" name="saldo_real" value="<?= (float) ($caixa['saldo_esperado'] ?? 0) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="observacoes" rows="2"></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">Fechar</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-fechar').classList.add('d-none')">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="modal-retirada" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
  <div class="d-flex justify-content-center align-items-center h-100">
    <div class="card" style="width: 400px;">
      <div class="card-body">
        <h5 class="card-title">Registrar Retirada</h5>
        <form method="post" action="<?= \App\Core\View::url('/caixa/retirada') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id" value="<?= (int) ($caixa['id'] ?? 0) ?>">
          <div class="mb-3">
            <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" class="form-control" name="valor" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <input type="text" class="form-control" name="motivo" required>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning">Registrar</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-retirada').classList.add('d-none')">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
