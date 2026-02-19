# 📊 Análise UX/UI - ShoesAZ v2.0.0

## 🎯 Resumo Executivo

Sistema bem estruturado com bom design visual, mas com **gaps críticos de UX** que afetam intuitividade e fluxos principais. Recomendações essenciais: 4 críticas, 6 importantes, 3 nice-to-have.

---

## 🔴 GAPS CRÍTICOS (Implementar Imediatamente)

### 1. **Status Visual Insuficiente de Orders (OS)**
**Problema:** 
- Falta indicador visual claro do progresso de uma OS
- Usuários não veem rapidamente em qual etapa a OS está
- Status tekstual sem cor/ícone diferenciador

**Impacto:** Alto - fluxo principal afetado

**Solução:**
```
Implementar status badge com cores/ícones:
- Recebido → Azul (ℹ️)
- Em reparo → Amarelo (⚙️) 
- Aguardando retirada → Verde (✓)
- Entregue → Verde escuro (✅)
- Cancelado → Cinza (✗)
- Atrasado → Vermelho (⚠️)

Timeline visual mostrando: Entrada → Em Execução → Entrega
```

**Onde:** `/os/index`, `/os/form`, Dashboard

---

### 2. **Falta de Progresso Claro: Orçamento → OS → Recibo**
**Problema:**
- Não fica claro para o usuário que orçamento pode virar OS
- Não há indicação de que OS virou recibo emitido
- Fluxo de conversão não é intuitivo

**Impacto:** Alto - confunde workflow

**Solução:**
```
Criar "Status do Orçamento":
- Rascunho
- Enviado ao cliente (com link público)
- Aprovado
- Convertido em OS (com link para a OS)

Criar "Histórico de Conversão":
- Mostrar link visual: Orçamento → OS → Recibo
- Um clique para pular de um para outro
```

**Onde:** `/orcamentos/index`, `/orcamentos/form`

---

### 3. **Dashboard Pouco Acionável**
**Problema:**
- Mostra muitas informações, pouca ação
- Não há CTA (Call-To-Action) claro para tarefas urgentes
- Dados desconectados da ação

**Impacto:** Alto - baixa produtividade

**Solução:**
```
Reorganizar dashboard em 3 seções:

📌 URGENTE (topo, destacado):
- [OS atrasadas com botão "Resolver"] ← RED ALERT
- [Pagamentos vencidos] ← WARNING
- [Sem recibo emitido] ← INFO

📊 MÉTRICAS (resumo):
- Recebimentos hoje
- OS em execução
- Clientes sem contato há 30+ dias

🚀 AÇÕES RÁPIDAS (cards clicáveis):
- "+ Novo Cliente" 
- "+ Novo Orçamento"
- "+ Nova OS"
```

**Onde:** `/dashboard/index`

---

### 4. **Ausência de Sistema de Notificações**
**Problema:**
- Usuário não sabe se há algo que precisa fazer
- Sem alertas de eventos importantes
- Sem marca de "novo" para itens não lidos

**Impacto:** Alto - missas de prazos

**Solução:**
```
Implementar notificações em tempo real:
- OS atrasada (vermelho badge no menu)
- Pagamento vencido (warning)
- Novo orçamento aprovado (info)
- Recibo criado e disponível para download

Visual: Badge com número no sidebar, 
ícone de sino no navbar com dropdown
```

**Onde:** Layout global (navbar + sidebar)

---

## 🟠 GAPS IMPORTANTES (Implementar em Sprint 2)

### 5. **Formulários Longos e Desorganizados**
**Problema:**
- Formulários de OS/Orçamento muito longos
- Sem separação visual de seções (abas/acordeões)
- Usuário se perde no meio do preenchimento

**Solução:**
```
Usar abas ou acordeões:
Tab 1: Informações do Cliente
Tab 2: Itens/Sapatos
Tab 3: Valores e Datas
Tab 4: Observações

Mostrar progresso: [Step 1/4] ████░░░░ 25%
```

**Onde:** `/orcamentos/form`, `/os/form`

---

### 6. **Histórico de Ações Invisível**
**Problema:**
- Não há log de quem fez o quê e quando
- Usuário não sabe quem aprovou orçamento
- Sem rastreabilidade

**Solução:**
```
Adicionar "Timeline" em detail views:
- 18/02 14:30 - Orçamento criado por João (Atendente)
- 18/02 15:45 - Aprovado por Maria (Gerente)
- 18/02 16:00 - Convertido em OS por João

Mostrar "Última atualização por: fulano em HH:MM"
```

**Onde:** `/orcamentos/edit`, `/os/edit`, `/recibos/visualizar`

---

### 7. **Busca Insuficiente**
**Problema:**
- Busca global existe mas é básica
- Sem filtros avançados nas listas
- Difícil encontrar um cliente antigo

**Solução:**
```
Melhorar busca global:
- Buscar por número de OS, orçamento, recibo
- Buscar por período (data_inicio - data_fim)
- Buscar por status + cliente + valor

Adicionar filtros nas listas:
[Filtrar por Status ▼] [Período ▼] [Valor entre __ e __]
```

**Onde:** `/busca`, `/os/index`, `/orcamentos/index`, `/recibos/index`

---

### 8. **Falta de Confirmação Antes de Ações Destrutivas**
**Problema:**
- Botão "Delete" sem confirmação modal
- Usuário pode deletar acidentalmente

**Solução:**
```
Modal de confirmação:
"Tem certeza que deseja deletar o Cliente: João Silva?
Esta ação não pode ser desfeita.

[Cancelar] [Deletar]"
```

**Onde:** Todos os formulários com botão delete

---

### 9. **Falta de Validação Visual (Client-Side)**
**Problema:**
- Erros só aparecem após enviar formulário
- Usuário não sabe se preencheu certo
- Sem validação em tempo real

**Solução:**
```
Adicionar validação ao sair do campo:
- Email: validar formato
- CPF: validar dígito verificador
- Data: validar se é data válida
- Número: validar se é número

Mostrar checkmark verde quando válido
Mostrar mensagem de erro quando inválido
```

**Onde:** Todos os formulários

---

### 10. **Falta de Atalhos de Teclado**
**Problema:**
- Usuário precisa clicar muito para navegar
- Sem produtividade para power users

**Solução:**
```
Implementar atalhos:
- Ctrl+N = Novo (contexto-dependente)
- Ctrl+S = Salvar
- Ctrl+/ = Mostrar todos os atalhos
- Ctrl+K = Abrir busca rápida
```

---

## 🟡 MELHORIAS NICE-TO-HAVE (Sprint 3+)

### 11. **Dark Mode**
- Opção de tema escuro no perfil do usuário
- Economizar bateria em mobile

### 12. **Drag & Drop de Sapatos**
- Reordenar sapatos arrastando em OS/Orçamento
- Melhor UX em mobile

### 13. **Exportar/Imprimir Melhorado**
- Preview antes de imprimir
- Escolher dados a incluir (logo, termos, etc)

---

## 📋 MATRIZ DE IMPACTO vs ESFORÇO

| ID | Problema | Impacto | Esforço | Prioridade |
|---|---|---|---|---|
| 1 | Status Visual OS | 🔴 Alto | 🟢 Baixo | P1 |
| 2 | Progresso Orç→OS→Recibo | 🔴 Alto | 🟡 Médio | P1 |
| 3 | Dashboard Acionável | 🔴 Alto | 🟡 Médio | P1 |
| 4 | Notificações | 🔴 Alto | 🟠 Alto | P1 |
| 5 | Formulários Abas | 🟠 Médio | 🟡 Médio | P2 |
| 6 | Timeline/Histórico | 🟠 Médio | 🟡 Médio | P2 |
| 7 | Busca Avançada | 🟠 Médio | 🟡 Médio | P2 |
| 8 | Confirmação Delete | 🟠 Médio | 🟢 Baixo | P2 |
| 9 | Validação Client-Side | 🟠 Médio | 🟡 Médio | P2 |
| 10 | Atalhos Teclado | 🟡 Baixo | 🟠 Alto | P3 |

---

## 🎨 PADRÕES DE DESIGN A IMPLEMENTAR

### Padrão 1: Card com Status Indicador
```html
<div class="card-with-status status-warning">
  <div class="status-indicator">⚙️ Em Reparo</div>
  <div class="card-content">
    OS #1234 - João Silva
  </div>
  <div class="card-actions">
    <button>Editar</button>
    <button>Ver Detalhes</button>
  </div>
</div>
```

### Padrão 2: Progresso Linear
```html
<div class="progress-linear">
  <div class="step completed">Orçamento</div>
  <div class="step completed">Aprovação</div>
  <div class="step active">Execução</div>
  <div class="step">Entrega</div>
  <div class="step">Recibo</div>
</div>
```

### Padrão 3: Call-to-Action Destacado
```html
<div class="alert-action danger">
  <div class="alert-content">
    <strong>OS #5432 Atrasada!</strong>
    Prazo: 15/02/2026 - Hoje é 18/02
  </div>
  <button class="btn-action">Resolver Agora →</button>
</div>
```

---

## 🚀 ROADMAP SUGERIDO

**SPRINT 1 (1-2 semanas):**
- [ ] Status badges com cores nas listas OS
- [ ] Dashboard reorganizado com alertas
- [ ] Confirmação modal para deletar

**SPRINT 2 (2-3 semanas):**
- [ ] Notificações com badge no sidebar
- [ ] Timeline de histórico em detail views
- [ ] Validação client-side em formulários

**SPRINT 3 (2-3 semanas):**
- [ ] Formulários com abas/acordeões
- [ ] Busca avançada com filtros
- [ ] Link visual Orçamento→OS→Recibo

**SPRINT 4+ (nice-to-have):**
- [ ] Atalhos de teclado
- [ ] Dark mode
- [ ] Drag & Drop

---

## ✅ O QUE ESTÁ BOM

1. ✅ Design visual limpo com Bootstrap 5
2. ✅ Navegação lógica e bem organizada
3. ✅ Responsivo em mobile
4. ✅ Icons consistentes (Bootstrap Icons)
5. ✅ Breadcrumbs para contexto
6. ✅ Paginação nas listas
7. ✅ Sorting/Ordenação funcionando
8. ✅ Links públicos para compartilhar

---

## 📞 PRÓXIMOS PASSOS

1. **Prioritizar:** Começar pelos 4 gaps críticos
2. **Wireframes:** Criar mockups das mudanças antes de codificar
3. **Testes com usuários:** Validar se as mudanças melhoram UX
4. **Feedback iterativo:** Pequenas melhorias contínuas

