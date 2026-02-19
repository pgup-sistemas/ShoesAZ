<?php

use App\Core\Csrf;

$orcamento = $orcamento ?? [];
$clienteSelecionado = $clienteSelecionado ?? null;
$sapatos = $sapatos ?? [];
$linkPublico = $linkPublico ?? null;
$action = $action ?? \App\Core\View::url('/orcamentos/store');
$isEdit = $isEdit ?? false;

$isConvertido = $isEdit && (string) ($orcamento['status'] ?? '') === 'Convertido';
$isAprovado = $isEdit && (string) ($orcamento['status'] ?? '') === 'Aprovado';

$podeCompartilhar = $isEdit && !$isConvertido;

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <div class="h5 mb-0">
      <?= $isEdit ? 'Orçamento ' . htmlspecialchars((string) $orcamento['numero'], ENT_QUOTES, 'UTF-8') : 'Novo Orçamento' ?>
    </div>
    <?php if ($isEdit): ?>
      <div class="text-muted small">Status: <span class="fw-semibold"><?= htmlspecialchars((string) $orcamento['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
    <?php endif; ?>
  </div>
  <div class="d-flex gap-2">
    <?php if ($isEdit && !$isConvertido): ?>
      <?php if (!$isAprovado): ?>
        <form method="post" action="<?= \App\Core\View::url('/orcamentos/aprovar') ?>?id=<?= (int) $orcamento['id'] ?>" class="m-0" onsubmit="return confirm('Aprovar este orçamento?');">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-success" type="submit">Aprovar</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= \App\Core\View::url('/orcamentos/converter') ?>?id=<?= (int) $orcamento['id'] ?>" class="m-0" onsubmit="return confirm('Converter em Ordem de Serviço?');">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-primary" type="submit">Converter em OS</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
    <a href="<?= \App\Core\View::url('/orcamentos') ?>" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="col-12">
            <label class="form-label">Cliente *</label>
            <div class="input-group">
              <input
                type="text"
                class="form-control form-control-lg"
                id="cliente-busca"
                placeholder="Digite nome ou telefone para buscar..."
                autocomplete="off"
                <?= $isConvertido ? 'disabled' : '' ?>
                value="<?= htmlspecialchars((string) (($clienteSelecionado['nome'] ?? '') !== '' ? ($clienteSelecionado['nome'] . ' - ' . ($clienteSelecionado['telefone'] ?? '')) : ''), ENT_QUOTES, 'UTF-8') ?>"
              >
              <input type="hidden" name="cliente_id" id="cliente-id" value="<?= (int) ($orcamento['cliente_id'] ?? 0) ?>">
              <?php if (!$isConvertido): ?>
                <button class="btn btn-outline-primary" type="button" onclick="document.getElementById('modal-novo-cliente').classList.remove('d-none')">
                  + Novo
                </button>
              <?php endif; ?>
            </div>
            <div id="cliente-resultados" class="list-group" style="position: relative; z-index: 20; display: none;"></div>
            <?php if ($isConvertido): ?>
              <input type="hidden" name="cliente_id" value="<?= (int) $orcamento['cliente_id'] ?>">
            <?php endif; ?>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Desconto (R$)</label>
            <input class="form-control" type="number" step="0.01" min="0" name="desconto" value="<?= number_format((float) ($orcamento['desconto'] ?? 0), 2, '.', '') ?>" <?= $isConvertido ? 'disabled' : '' ?>>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Validade</label>
            <input class="form-control" type="date" name="validade" value="<?= htmlspecialchars((string) ($orcamento['validade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isConvertido ? 'disabled' : '' ?>>
          </div>

          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" rows="2" name="observacoes" <?= $isConvertido ? 'disabled' : '' ?>><?= htmlspecialchars((string) ($orcamento['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <?php if (!$isConvertido): ?>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Salvar</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <?php if ($isEdit && !$isConvertido): ?>
      <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Sapatos/Serviços</span>
          <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('form-sapato').classList.toggle('d-none')">+ Adicionar</button>
        </div>
        <div class="card-body">
          <div id="form-sapato" class="d-none border rounded p-3 mb-3 bg-light">
            <form method="post" action="<?= \App\Core\View::url('/sapatos/store') ?>?orcamento_id=<?= (int) $orcamento['id'] ?>" class="row g-2" enctype="multipart/form-data">
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
                <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple id="foto-input" onchange="previewFotos(this)">
                <div id="foto-preview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                <small class="text-muted">JPG, PNG ou WebP. Máximo 5MB cada.</small>
              </div>
              <div class="col-12">
                <button class="btn btn-success" type="submit">Adicionar Sapato</button>
                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('form-sapato').classList.add('d-none')">Cancelar</button>
              </div>
            </form>
          </div>

          <?php if (!$sapatos): ?>
            <div class="text-muted text-center p-3">Nenhum sapato adicionado.</div>
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
                                  <form method="post" action="<?= \App\Core\View::url('/sapatos/remover-foto') ?>" class="position-absolute top-0 end-0">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="sapato_id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="foto_index" value="<?= $index ?>">
                                    <input type="hidden" name="orcamento_id" value="<?= (int) $orcamento['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger p-0" style="width: 20px; height: 20px; font-size: 10px;" onclick="return confirm('Remover esta foto?');">×</button>
                                  </form>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                          
                          <?php if (count($fotos) < 4): ?>
                            <form method="post" action="<?= \App\Core\View::url('/sapatos/upload-foto') ?>" enctype="multipart/form-data" class="mt-3 d-flex gap-2 align-items-center">
                              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                              <input type="hidden" name="sapato_id" value="<?= (int) $s['id'] ?>">
                              <input type="hidden" name="orcamento_id" value="<?= (int) $orcamento['id'] ?>">
                              <input type="file" name="foto" accept="image/*" class="form-control form-control-sm" style="max-width: 200px;" required>
                              <button type="submit" class="btn btn-sm btn-outline-primary">+ Foto</button>
                            </form>
                          <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                          <form method="post" action="<?= \App\Core\View::url('/sapatos/destroy') ?>?id=<?= (int) $s['id'] ?>&orcamento_id=<?= (int) $orcamento['id'] ?>" class="d-inline" onsubmit="return confirm('Remover este item?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Remover Sapato</button>
                          </form>
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
    <?php endif; ?>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Resumo</div>
        <div class="d-flex justify-content-between">
          <span>Valor Total:</span>
          <span class="fw-semibold">R$ <?= number_format((float) ($orcamento['valor_total'] ?? 0), 2, ',', '.') ?></span>
        </div>
        <div class="d-flex justify-content-between text-danger">
          <span>Desconto:</span>
          <span>- R$ <?= number_format((float) ($orcamento['desconto'] ?? 0), 2, ',', '.') ?></span>
        </div>
        <hr>
        <div class="d-flex justify-content-between fs-5 fw-bold">
          <span>Valor Final:</span>
          <span>R$ <?= number_format((float) ($orcamento['valor_final'] ?? 0), 2, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <?php if ($isEdit): ?>
      <div class="card mt-3 border-primary">
        <div class="card-body">
          <div class="fw-semibold mb-2">Ações</div>
          <?php if ($podeCompartilhar): ?>
            <?php
              $numeroOrcamento = (string) ($orcamento['numero'] ?? '');
              $valorFinal = number_format((float) ($orcamento['valor_final'] ?? 0), 2, ',', '.');
              $validadeTxt = $orcamento['validade'] ? date('d/m/Y', strtotime((string) $orcamento['validade'])) : '';

              $publicUrl = '';
              if (is_array($linkPublico) && !empty($linkPublico['token'])) {
                $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $publicUrl = $scheme . '://' . $host . \App\Core\View::url('/public') . '?token=' . $linkPublico['token'];
              }

              $mensagemZap = "Olá! Segue seu orçamento.\n\n";
              $mensagemZap .= "Orçamento Nº: {$numeroOrcamento}\n";
              $mensagemZap .= "Total: R$ {$valorFinal}\n";
              if ($validadeTxt !== '') {
                $mensagemZap .= "Validade: {$validadeTxt}\n";
              }
              if ($publicUrl !== '') {
                $mensagemZap .= "\nLink do orçamento (sem login): {$publicUrl}\n";
              }
              $mensagemZap .= "\nPara confirmar, responda aqui no WhatsApp com: CONFIRMAR ORÇAMENTO {$numeroOrcamento}.\n";
              $mensagemZap .= "Assim seu serviço já entra na fila.\n\n";
              $mensagemZap .= "Endereço: Rua Exemplo, 123 - Centro\n";
              $mensagemZap .= "Horário: Seg a Sex 08:00-18:00 | Sáb 08:00-12:00\n";
              $mensagemZap .= "\nSe tiver dúvidas, pode chamar por aqui.";
            ?>
            <?php if ($publicUrl): ?>
              <div class="mb-2">
                <div class="input-group input-group-sm mb-2">
                  <input class="form-control" id="link-orcamento" value="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                  <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('link-orcamento').value); showToast('Link copiado!', 'success')">Copiar</button>
                </div>
              </div>
            <?php endif; ?>
            <a class="btn btn-outline-success w-100 mb-2" target="_blank" href="https://wa.me/?text=<?= urlencode($mensagemZap) ?>">
              Enviar WhatsApp
            </a>
          <?php endif; ?>
          <a class="btn btn-outline-secondary w-100" href="<?= \App\Core\View::url('/orcamentos/imprimir') ?>?id=<?= (int) $orcamento['id'] ?>" target="_blank">
            🖨️ Imprimir
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Novo Cliente -->
<?php if (!$isConvertido): ?>
<div id="modal-novo-cliente" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 1050;">
  <div class="d-flex justify-content-center align-items-center h-100">
    <div class="card" style="width: 500px; max-width: 95%;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close" onclick="document.getElementById('modal-novo-cliente').classList.add('d-none')"></button>
      </div>
      <div class="card-body">
        <form id="form-novo-cliente" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
          <div class="col-12">
            <label class="form-label">Nome *</label>
            <input type="text" class="form-control" name="nome" required id="novo-cliente-nome">
          </div>
          <div class="col-12">
            <label class="form-label">Telefone/WhatsApp *</label>
            <input type="text" class="form-control" name="telefone" required id="novo-cliente-telefone">
          </div>
          <div class="col-12">
            <label class="form-label">CPF</label>
            <input type="text" class="form-control" name="cpf" id="novo-cliente-cpf">
          </div>
          <div class="col-12">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" name="email" id="novo-cliente-email">
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar Cliente</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-novo-cliente').classList.add('d-none')">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const inputBusca = document.getElementById('cliente-busca');
  const inputId = document.getElementById('cliente-id');
  const resultados = document.getElementById('cliente-resultados');

  function hideResultados() {
    if (resultados) {
      resultados.style.display = 'none';
      resultados.innerHTML = '';
    }
  }

  function setCliente(cliente) {
    if (!inputBusca || !inputId) return;
    inputId.value = String(cliente.id || '');
    inputBusca.value = (cliente.nome || '') + (cliente.telefone ? (' - ' + cliente.telefone) : '');
    hideResultados();
  }

  let debounceTimer = null;
  async function buscarClientes(term) {
    const url = '<?= \App\Core\View::url('/clientes/buscar') ?>' + '?q=' + encodeURIComponent(term);
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  }

  function renderResultados(rows) {
    if (!resultados) return;

    if (!rows || rows.length === 0) {
      hideResultados();
      return;
    }

    resultados.innerHTML = '';
    rows.forEach(row => {
      const a = document.createElement('button');
      a.type = 'button';
      a.className = 'list-group-item list-group-item-action';
      a.textContent = (row.nome || '') + (row.telefone ? (' - ' + row.telefone) : '');
      a.addEventListener('click', function() {
        setCliente(row);
      });
      resultados.appendChild(a);
    });
    resultados.style.display = 'block';
  }

  if (inputBusca && inputId && resultados && !inputBusca.hasAttribute('disabled')) {
    inputBusca.addEventListener('input', function() {
      const term = (inputBusca.value || '').trim();
      inputId.value = '';

      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      if (term.length < 2) {
        hideResultados();
        return;
      }

      debounceTimer = setTimeout(async function() {
        const rows = await buscarClientes(term);
        renderResultados(rows);
      }, 250);
    });

    inputBusca.addEventListener('blur', function() {
      setTimeout(hideResultados, 150);
    });

    inputBusca.addEventListener('focus', function() {
      const term = (inputBusca.value || '').trim();
      if (term.length >= 2) {
        buscarClientes(term).then(renderResultados);
      }
    });
  }

  const form = document.getElementById('form-novo-cliente');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(form);
      const data = {};
      formData.forEach((value, key) => data[key] = value);
      
      fetch('<?= \App\Core\View::url('/clientes/store') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
      })
      .then(response => {
        if (response.ok) {
          window.location.reload();
        } else {
          alert('Erro ao cadastrar cliente. Tente novamente.');
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao cadastrar cliente.');
      });
    });
  }
})();

// Preview de fotos antes do upload
function previewFotos(input) {
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
<?php endif; ?>
