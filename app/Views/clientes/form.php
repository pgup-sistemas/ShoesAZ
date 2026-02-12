<?php

use App\Core\Csrf;

$cliente = $cliente ?? [];
$action = $action ?? \App\Core\View::url('/clientes/store');
$ordensServico = $ordensServico ?? [];
$orcamentos = $orcamentos ?? [];
$metricas = $metricas ?? [];

$isEdit = !empty($cliente['id']);

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0"><?= htmlspecialchars($pageTitle ?? 'Cliente', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="text-muted small">Campos obrigatórios: nome e telefone</div>
  </div>
  <a href="<?= \App\Core\View::url('/clientes') ?>" class="btn btn-outline-secondary">Voltar</a>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="col-12">
            <label class="form-label">Nome *</label>
            <input class="form-control form-control-lg" name="nome" required value="<?= htmlspecialchars((string) ($cliente['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Telefone/WhatsApp *</label>
            <input class="form-control form-control-lg" name="telefone" required value="<?= htmlspecialchars((string) ($cliente['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">CPF</label>
            <input class="form-control form-control-lg" name="cpf" value="<?= htmlspecialchars((string) ($cliente['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">E-mail</label>
            <input class="form-control form-control-lg" name="email" value="<?= htmlspecialchars((string) ($cliente['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Endereço</label>
            <textarea class="form-control" rows="2" name="endereco"><?= htmlspecialchars((string) ($cliente['endereco'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" rows="3" name="observacoes"><?= htmlspecialchars((string) ($cliente['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary btn-lg" type="submit">Salvar</button>
            <a class="btn btn-outline-secondary btn-lg" href="<?= \App\Core\View::url('/clientes') ?>">Cancelar</a>
          </div>
        </form>
      </div>
    </div>

    <?php if ($isEdit): ?>
      <!-- Timeline de Histórico -->
      <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">📋 Histórico do Cliente</span>
          <div>
            <a href="<?= \App\Core\View::url('/orcamentos/create') ?>?cliente_id=<?= (int) $cliente['id'] ?>" class="btn btn-sm btn-outline-primary">+ Orçamento</a>
            <a href="<?= \App\Core\View::url('/os/create') ?>?cliente_id=<?= (int) $cliente['id'] ?>" class="btn btn-sm btn-outline-success ms-1">+ OS</a>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($ordensServico) && empty($orcamentos)): ?>
            <div class="text-muted text-center py-4">
              Nenhum histórico encontrado.<br>
              <small>Crie um orçamento ou OS para este cliente.</small>
            </div>
          <?php else: ?>
            <div class="timeline">
              <?php 
              // Combinar e ordenar por data
              $historico = [];
              foreach ($ordensServico as $os) {
                $historico[] = [
                  'tipo' => 'OS',
                  'data' => $os['created_at'],
                  'numero' => $os['numero'],
                  'status' => $os['status'],
                  'valor' => $os['valor_total'],
                  'servicos' => $os['servicos'],
                  'id' => $os['id'],
                ];
              }
              foreach ($orcamentos as $orc) {
                $historico[] = [
                  'tipo' => 'Orçamento',
                  'data' => $orc['created_at'],
                  'numero' => $orc['numero'],
                  'status' => $orc['status'],
                  'valor' => $orc['valor_final'],
                  'servicos' => $orc['servicos'],
                  'id' => $orc['id'],
                ];
              }
              // Ordenar por data decrescente
              usort($historico, fn($a, $b) => strtotime($b['data']) - strtotime($a['data']));
              ?>
              
              <?php foreach ($historico as $item): ?>
                <div class="d-flex mb-3 pb-3 border-bottom">
                  <div class="flex-shrink-0 me-3">
                    <div class="badge bg-<?= $item['tipo'] === 'OS' ? 'success' : 'warning' ?> rounded-circle p-2">
                      <?= $item['tipo'] === 'OS' ? '📦' : '📄' ?>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <strong><?= $item['tipo'] ?> #<?= htmlspecialchars($item['numero']) ?></strong>
                        <span class="badge bg-secondary ms-1"><?= htmlspecialchars($item['status']) ?></span>
                      </div>
                      <small class="text-muted"><?= date('d/m/Y', strtotime($item['data'])) ?></small>
                    </div>
                    <div class="small text-muted mt-1">
                      <?= htmlspecialchars($item['servicos'] ?? 'Sem serviços') ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="fw-bold text-primary">R$ <?= number_format((float) $item['valor'], 2, ',', '.') ?></span>
                      <a href="<?= \App\Core\View::url($item['tipo'] === 'OS' ? '/os/edit' : '/orcamentos/edit') ?>?id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($isEdit && !empty($metricas)): ?>
    <div class="col-12 col-lg-4">
      <!-- Métricas do Cliente -->
      <div class="card">
        <div class="card-header bg-info text-white">
          <strong>📊 Métricas do Cliente</strong>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-6">
              <div class="text-center p-2 bg-light rounded">
                <div class="fs-4 fw-bold text-primary"><?= (int) $metricas['total_os'] ?></div>
                <small class="text-muted">Total OS</small>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center p-2 bg-light rounded">
                <div class="fs-4 fw-bold text-success"><?= (int) $metricas['os_concluidas'] ?></div>
                <small class="text-muted">Concluídas</small>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center p-2 bg-light rounded">
                <div class="fs-4 fw-bold text-warning"><?= (int) $metricas['total_orcamentos'] ?></div>
                <small class="text-muted">Orçamentos</small>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center p-2 bg-light rounded">
                <div class="fs-4 fw-bold text-info"><?= number_format((float) $metricas['taxa_conversao'], 0) ?>%</div>
                <small class="text-muted">Conversão</small>
              </div>
            </div>
          </div>

          <hr class="my-3">

          <div class="mb-2">
            <span class="text-muted">Total Gasto:</span>
            <span class="float-end fw-bold text-success">R$ <?= number_format((float) $metricas['valor_total_gasto'], 2, ',', '.') ?></span>
          </div>
          <div class="mb-2">
            <span class="text-muted">Ticket Médio:</span>
            <span class="float-end fw-bold">R$ <?= number_format((float) $metricas['valor_medio_os'], 2, ',', '.') ?></span>
          </div>

          <?php if ($metricas['ultima_visita']): ?>
            <hr class="my-3">
            <div class="small text-muted">
              <div>Primeira visita: <?= date('d/m/Y', strtotime($metricas['primeira_visita'])) ?></div>
              <div>Última visita: <?= date('d/m/Y', strtotime($metricas['ultima_visita'])) ?></div>
              <div>Há <?= $metricas['dias_ultima_visita'] ?> dias</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Botões de Ação Rápida -->
      <div class="card mt-3">
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', (string) ($cliente['telefone'] ?? '')) ?>" target="_blank" class="btn btn-success">
              💬 WhatsApp
            </a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
