# 📋 CHECKLIST: Melhorias UX/UI - ShoesAZ

## 🎯 SPRINT 1: Melhorias Críticas (1-2 semanas)

### Gap #1: Status Visual para OS ⭐
- [ ] Criar `app/Helpers/StatusHelper.php`
- [ ] Adicionar CSS para badges em `public/assets/css/style.css`
- [ ] Atualizar `/app/Views/os/index.php` para usar badges
- [ ] Atualizar `/app/Views/os/form.php` para mostrar status com ícone
- [ ] Atualizar `/app/Views/orcamentos/index.php` para usar badges
- [ ] Testar em diferentes resoluções (mobile/desktop)
- [ ] Validar cores em modo high-contrast

**Arquivos a modificar:**
```
app/Helpers/StatusHelper.php (criar)
app/Views/os/index.php
app/Views/os/form.php
app/Views/orcamentos/index.php
app/Views/orcamentos/form.php
public/assets/css/style.css
```

---

### Gap #2: Dashboard Mais Acionável ⭐
- [ ] Reorganizar seções do dashboard em 3 grupos
- [ ] Criar seção "URGENTE" com alertas em vermelho
- [ ] Adicionar badges com contadores (OS atrasadas, pagamentos vencidos)
- [ ] Criar "Cards de Ação Rápida" com CTAs
- [ ] Adicionar ícones aos cards de ações rápidas
- [ ] Fazer links dos cards para criar novo registro
- [ ] Testar fluxo: Dashboard → Novo Orçamento → Sucesso

**Arquivos a modificar:**
```
app/Views/dashboard/index.php
app/Controllers/OSController.php (fetch stats)
public/assets/css/style.css
```

**Dados a buscar em DashboardService:**
- osAtrasadas (contagem + últimas 5)
- pagamentosVencidos (contagem + valor total)
- semRecibo (contagem)
- recebitasHoje
- osEmExecução

---

### Gap #3: Confirmação antes de Deletar ⭐
- [ ] Criar modal template reutilizável
- [ ] Adicionar data-bs-toggle="modal" em todos os botões delete
- [ ] Implementar em `/clientes`, `/orcamentos`, `/os`, `/recibos`
- [ ] Adicionar JavaScript para preencher dinâmicamente nome/ID
- [ ] Testar em mobile (off-canvas vs modal)

**Arquivos a modificar:**
```
app/Views/components/delete_modal.php (criar)
app/Views/layouts/header.php (adicionar modal global)
app/Views/clientes/index.php
app/Views/orcamentos/index.php
app/Views/os/index.php
app/Views/recibos/index.php
public/assets/js/app.js (adicionar handler)
```

**Código template:**
```html
<!-- Modal Delete Global -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirmar Exclusão</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja deletar <strong id="deleteItemName"></strong>?</p>
        <p class="text-muted small">Esta ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form id="deleteForm" method="POST" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
          <button type="submit" class="btn btn-danger">Deletar Permanentemente</button>
        </form>
      </div>
    </div>
  </div>
</div>
```

---

### Gap #4: Notificações com Badges ⭐
- [ ] Criar rota `/api/notificacoes` que retorna JSON
- [ ] Adicionar badge no sidebar (vermelho para críticas)
- [ ] Adicionar ícone de sino no navbar com dropdown
- [ ] Buscar notificações a cada 30 segundos (AJAX)
- [ ] Mostrar contadores (OS atrasadas, pagtos vencidos, etc)
- [ ] Marcar como lido ao clicar
- [ ] Testar em diferentes browsers

**Arquivos a criar/modificar:**
```
app/Controllers/NotificacaoController.php (criar)
app/Views/components/notificacao_badge.php (criar)
app/Views/components/notificacao_dropdown.php (criar)
app/Views/layouts/header.php
app/Views/layouts/sidebar.php
public/assets/js/notificacoes.js (criar)
```

---

## 📊 SPRINT 2: Melhorias Importantes (2-3 semanas)

### Gap #5: Formulários com Abas
- [ ] Implementar abas Bootstrap em `/orcamentos/form.php`
- [ ] Dividir em 4 abas: Cliente | Itens | Valores | Observações
- [ ] Adicionar validação por aba
- [ ] Mover validação para client-side (HTML5 + JS)
- [ ] Testar save/restore de aba ativa

**Arquivos a modificar:**
```
app/Views/orcamentos/form.php
app/Views/os/form.php
app/Views/recibos/form.php
public/assets/js/form-tabs.js (criar)
```

---

### Gap #6: Timeline de Histórico
- [ ] Buscar audit log da tabela `auditoria` (se existir)
- [ ] Criar component `history_timeline.php`
- [ ] Mostrar: Data | Usuário | Ação em todas as detail views
- [ ] Adicionar ícones para ações (criar, editar, deletar, converter)
- [ ] Mostrar "Última atualização" em cada card

**Arquivos a modificar:**
```
app/Views/components/history_timeline.php (criar)
app/Views/orcamentos/form.php
app/Views/os/form.php
app/Views/recibos/visualizar.php
app/Controllers/OrcamentoController.php (buscar history)
app/Controllers/OSController.php (buscar history)
```

---

### Gap #7: Busca Avançada
- [ ] Criar dropdown/modal com filtros avançados
- [ ] Adicionar filtros por: status, período, valor, cliente
- [ ] Persistir filtros na URL (?status=xxx&periodo=xxx)
- [ ] Mostrar "filtros ativos" como badges removíveis
- [ ] Adicionar botão "Limpar Filtros"

**Arquivos a modificar:**
```
app/Views/components/advanced_filters.php (criar)
app/Views/os/index.php
app/Views/orcamentos/index.php
app/Views/recibos/index.php
app/Views/pagamentos/index.php
app/Controllers/OSController.php (parse filters)
app/Controllers/OrcamentoController.php (parse filters)
```

---

### Gap #8: Validação Client-Side
- [ ] Adicionar validação HTML5 (required, pattern, min, max)
- [ ] Criar validadores JS customizados (CPF, Email, Data)
- [ ] Mostrar mensagens de erro dinâmicas
- [ ] Desabilitar botão Submit enquanto há erros
- [ ] Adicionar checkmark verde para campos válidos

**Arquivos a criar/modificar:**
```
public/assets/js/validators.js (criar)
public/assets/css/form-validation.css (criar)
app/Views/components/form_input.php (criar helper)
app/Views/clientes/form.php
app/Views/orcamentos/form.php
app/Views/os/form.php
```

**Exemplo validador CPF:**
```javascript
function validateCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return false;
    
    // Validar dígitos verificadores...
    let sum = 0;
    let remainder;
    
    for (let i = 1; i <= 9; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }
    
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(9, 10))) return false;
    
    // Validar segundo dígito...
    sum = 0;
    for (let i = 1; i <= 10; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }
    
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(10, 11))) return false;
    
    return true;
}
```

---

## 🎨 SPRINT 3: Melhorias de UX (2-3 semanas)

### Gap #9: Link Visual Orç→OS→Recibo
- [ ] Adicionar "Status de Conversão" no orçamento
- [ ] Mostrar badge "Convertido em OS #XXXX" quando aplicável
- [ ] Clickable link para pular de Orç → OS → Recibo
- [ ] Mostrar timeline visual: Orçamento → Aprovação → OS → Recibo

**Arquivos a criar/modificar:**
```
app/Views/components/conversion_badge.php (criar)
app/Views/orcamentos/form.php
app/Views/os/form.php
app/Views/recibos/visualizar.php
```

---

### Gap #10: Atalhos de Teclado
- [ ] Implementar Ctrl+N para novo
- [ ] Implementar Ctrl+S para salvar
- [ ] Implementar Ctrl+K para busca rápida
- [ ] Implementar ? para mostrar ajuda
- [ ] Mostrar overlay com lista de atalhos

**Arquivos a criar:**
```
public/assets/js/shortcuts.js (criar)
app/Views/components/shortcuts_help.php (criar)
```

**Implementação Ctrl+K (Busca Rápida):**
```javascript
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('[data-search-focus]')?.focus();
    }
});
```

---

## 🔄 TESTING CHECKLIST

- [ ] Testar em Chrome/Firefox/Safari/Edge
- [ ] Testar em mobile (iOS/Android)
- [ ] Testar em tablet
- [ ] Testar com tela pequena (480px)
- [ ] Testar zoom de página (75%, 100%, 125%, 150%)
- [ ] Testar com high contrast mode ativado
- [ ] Testar com screen reader (NVDA/JAWS)
- [ ] Testar com mouse disabled (só teclado)
- [ ] Testar com conexão lenta (throttle)
- [ ] Testar com JavaScript desativado

---

## 📈 MÉTRICAS DE SUCESSO

Após implementar melhorias, medir:

1. **Tempo para completar tarefa:** Reduzir em 20%+
2. **Taxa de erro:** Reduzir deletions acidentais em 90%+
3. **Tempo no dashboard:** Aumentar ações iniciadas a partir do dashboard em 40%+
4. **Satisfação do usuário:** Feedback qualitativo (1-5 stars)
5. **Uso de busca avançada:** Aumentar em 50%+

---

## 💻 COMANDOS GIT SUGERIDOS

```bash
# Sprint 1 - Status Visual
git checkout -b feature/status-visual-badges
git add .
git commit -m "feat: implementar status badges com cores e ícones para OS"

# Sprint 1 - Dashboard
git checkout -b feature/dashboard-actionable
git commit -m "feat: reorganizar dashboard com seção urgente e CTAs"

# Sprint 1 - Confirmação Delete
git checkout -b feature/delete-confirmation
git commit -m "feat: adicionar modal de confirmação antes de deletar"

# Sprint 1 - Notificações
git checkout -b feature/notifications
git commit -m "feat: implementar sistema de notificações com badges"

# Merge após review
git pull origin main
git merge feature/status-visual-badges
```

---

## 📚 REFERÊNCIAS

- Bootstrap 5 Docs: https://getbootstrap.com/docs/5.0/
- Bootstrap Icons: https://icons.getbootstrap.com/
- WAI-ARIA Practices: https://www.w3.org/WAI/ARIA/apg/
- UX Checklist: https://www.nngroup.com/articles/
- Material Design: https://material.io/design/

---

## 📞 CONTATO

Para dúvidas sobre implementação:
- Documentação: `ANALISE_UX_UI.md` e `GUIA_IMPLEMENTACAO_STATUS.md`
- Help: `/ajuda` no sistema

