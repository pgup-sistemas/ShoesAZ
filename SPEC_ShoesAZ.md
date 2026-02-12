# SPEC – Sistema de Controle de Sapataria v2.0

## 1. Visão Geral

Sistema web para gestão completa de uma **sapataria tradicional**, focado em:
- Controle de **orçamentos**, **ordens de serviço (OS)** e **recibos**
- **Gestão financeira completa** (pagamentos, despesas, fluxo de caixa)
- **Controle de prazos** e alertas inteligentes
- Fluxo simples e intuitivo (poucos cliques)
- Uso por pessoas com **baixa familiaridade com software**
- Segurança, rastreabilidade e organização

Tecnologia pensada para **hospedagem compartilhada (Locaweb)**, utilizando **PHP** e diretório `public/`.

---

## 2. Objetivos do Sistema

### Objetivos Principais
- Transformar **orçamentos em ordens de serviço** com mínimo esforço
- Registrar **sapatos individualmente ou em conjunto**, com fotos
- Controlar **status, localização e histórico** dos serviços
- Emitir **recibos** e manter histórico financeiro completo
- Reduzir erros manuais e perda de informações

### Objetivos Secundários
- Controlar **pagamentos parciais** e inadimplência
- Gerenciar **prazos e alertas** de serviços
- Registrar **despesas operacionais**
- Realizar **fechamento de caixa** diário
- Gerar **relatórios gerenciais** simples
- Manter **histórico completo** do cliente

---

## 3. Público-Alvo

- Dono de sapataria
- Sapateiros tradicionais
- Atendentes

> Premissa central: **qualquer pessoa consegue usar sem treinamento técnico**.

---

## 4. Princípios de UX/UI

### Interface
- Layout limpo e intuitivo
- Botões grandes e visíveis
- Textos claros e objetivos
- Fluxos guiados (passo a passo)
- Evitar termos técnicos
- Máximo de 2–3 cliques para ações comuns

### Sistema de Cores

#### Cor Primária
- **#008bcd** (azul sapataria)

#### Cores Funcionais
- **Verde (#28a745):** Ações concluídas, pagamentos confirmados
- **Amarelo (#ffc107):** Alertas, serviços próximos do prazo
- **Vermelho (#dc3545):** Cancelamentos, serviços atrasados, inadimplência
- **Cinza (#6c757d):** Informações secundárias

#### Cores de Status por Urgência
- **Verde:** No prazo (prazo > 2 dias)
- **Amarelo:** Atenção (prazo entre 1-2 dias)
- **Vermelho:** Atrasado (prazo vencido)

### Feedback Visual
- **Notificações Toast:** Para todas as ações (sucesso, erro, aviso)
- **Animações sutis:** Confirmações visuais
- **Ícones intuitivos:** Bootstrap Icons

---

## 5. Arquitetura Técnica

### Stack Completa
- **Linguagem:** PHP 8.x
- **Banco de Dados:** MySQL 8.0+
- **Framework CSS:** Bootstrap 5.3
- **JavaScript:** Vanilla JS + Toast notifications
- **Upload de arquivos:** Imagens JPG/PNG (compressão automática)

### Estrutura de Pastas
```
/public
  /assets
    /css
      style.css
      toast.css
    /js
      app.js
      toast.js
    /img
  /uploads
    /clientes
    /sapatos
  index.php
  
/app
  /Controllers
    AuthController.php
    ClienteController.php
    OrcamentoController.php
    OSController.php
    FinanceiroController.php
    RelatorioController.php
  /Models
    Usuario.php
    Cliente.php
    Orcamento.php
    OrdemServico.php
    Sapato.php
    Pagamento.php
    Despesa.php
    Caixa.php
  /Services
    OrcamentoService.php
    OSService.php
    FinanceiroService.php
    NotificacaoService.php
    WhatsAppService.php
  /Repositories
    ClienteRepository.php
    OrcamentoRepository.php
    OSRepository.php
    FinanceiroRepository.php
  /Validators
    ClienteValidator.php
    OrcamentoValidator.php
    PagamentoValidator.php
  /Views
    /layouts
      header.php
      footer.php
      sidebar.php
    /auth
    /dashboard
    /clientes
    /orcamentos
    /os
    /financeiro
    /relatorios
    
/config
  database.php
  app.php
  
/storage
  /logs
  /backups
  
/database
  /migrations
  schema.sql
```

---

## 6. Autenticação e Segurança

### Login
- Usuário + senha
- Controle de sessão seguro
- Timeout após 2 horas de inatividade
- Limite de tentativas (5 tentativas / 15 minutos)

### Redefinição de Senha (sem e-mail)

**Fluxo baseado em validação por SMS:**

1. Usuário informa **login ou CPF**
2. Sistema solicita confirmação de:
   - Nome completo
   - Telefone cadastrado
3. Sistema envia **SMS com código de 6 dígitos**
4. Código válido por **10 minutos**
5. Usuário insere código
6. Se validado → permitir redefinir senha
7. Invalida todos os tokens anteriores

> **Alternativa:** Se SMS não disponível, usar pergunta de segurança + CPF

### Segurança Adicional
- Senhas com hash bcrypt
- Proteção CSRF em todos os formulários
- Sanitização de inputs
- Prepared statements (PDO)
- Logs de auditoria para ações críticas

### Auditoria (Log de Ações)

Registrar obrigatoriamente:
- Login/Logout
- Criação/edição/exclusão de OS
- Alterações de valores
- Emissão de recibos
- Fechamento de caixa
- Exclusão de registros

Dados do log:
- Usuário
- Ação
- Data/hora
- IP
- Dados alterados (antes/depois)

---

## 7. Cadastro de Clientes

### Dados Básicos
- Nome completo (obrigatório)
- CPF (opcional, mas recomendado)
- Telefone/WhatsApp (obrigatório)
- E-mail (opcional)
- Endereço completo (opcional)
- Observações

### Histórico do Cliente

**Timeline Visual** com todos os serviços anteriores:
- Data do serviço
- Tipo de serviço
- Sapatos atendidos (com fotos)
- Valor total
- Status
- Prazo de entrega
- Sapateiro responsável

### Métricas do Cliente
- **Total gasto** (lifetime value)
- **Frequência de retorno** (dias médios entre serviços)
- **Último serviço** (data)
- **Serviços realizados** (quantidade)
- **Taxa de inadimplência** (%)

### Relacionamento
- Campo para **preferências** (ex: "gosta de sola de couro")
- **Tags** personalizadas (ex: "VIP", "Desconto 10%")

---

## 8. Fluxo Principal do Negócio

### 8.1 Atendimento Inicial

Cliente chega e escolhe:
1. **Orçamento** (consulta de preço)
2. **Ordem de Serviço direta** (serviço aprovado)

---

### 8.2 Cadastro de Sapatos

Para cada atendimento:

**Dados do sapato:**
- Categoria (ex: social, tênis, bota, sandália, sapatênis)
- Cor principal
- Modelo/descrição
- Tipo de serviço (dropdown com opções configuráveis)
  - Conserto de solado
  - Troca de sola completa
  - Costura
  - Pintura/tingimento
  - Colocação de salto
  - Alongamento
  - Outros
- Marca (opcional)
- Valor do serviço (individual)
- Material necessário (opcional)
- Observações específicas

**Upload de Imagens:**
- Foto individual do sapato **OU**
- Foto do conjunto (múltiplos sapatos)
- Compressão automática para otimizar storage
- Máximo 4 fotos por sapato

**Interface de Cadastro:**
- Botão **"+ Adicionar Outro Sapato"**
- Duplicar sapato anterior (facilita cadastro de pares)
- Cálculo automático do valor total

---

### 8.3 Orçamento

#### Criação
- Sistema calcula **valor total** automaticamente
- Define **prazo estimado** (opcional)
- Permite adicionar **desconto** (% ou R$)
- Gera **número único** do orçamento

#### Status do Orçamento
- **Aguardando aprovação** (amarelo)
- **Aprovado** (verde)
- **Reprovado** (vermelho)
- **Expirado** (cinza) - após 30 dias

#### Ações Disponíveis
- Enviar por WhatsApp
- Imprimir
- Converter em OS
- Editar (apenas se não aprovado)
- Cancelar

---

### 8.4 Conversão para Ordem de Serviço (OS)

**Fluxo simplificado:**
1. Botão **"Converter em OS"** no orçamento aprovado
2. Sistema copia todos os dados automaticamente
3. Solicita informações adicionais:
   - Prazo de entrega (obrigatório)
   - Sapateiro responsável
   - Forma de pagamento (à vista ou parcelado)
   - Valor de entrada (se pagamento parcelado)
4. Gera **número único da OS**
5. Atualiza status do orçamento para "Convertido"
6. Opção de **imprimir etiqueta** imediatamente

---

## 9. Ordem de Serviço (OS)

### Dados da OS
- Número único (sequencial)
- Cliente
- Data de entrada
- **Prazo de entrega** (obrigatório)
- **Sapateiro responsável** (obrigatório)
- Lista de sapatos
- Valor total
- Status atual
- Localização física
- Observações

### Status Possíveis

| Status | Cor | Descrição |
|--------|-----|-----------|
| Recebido | Azul | OS criada, aguardando início |
| Em reparo | Laranja | Sapateiro trabalhando |
| Aguardando retirada | Verde | Serviço concluído |
| Entregue | Verde escuro | Cliente retirou |
| Cancelado | Vermelho | OS cancelada |
| Atrasado | Vermelho | Prazo vencido |

### Sistema de Cores por Urgência

**Badge visual ao lado do prazo:**
- 🟢 **Verde:** Prazo > 2 dias
- 🟡 **Amarelo:** Prazo entre 1-2 dias  
- 🔴 **Vermelho:** Prazo vencido (atrasado)

### Localização do Sapato

Campo obrigatório e visível:
- Formato livre (ex: "Prateleira A – Caixa 3")
- Dropdown com localizações pré-cadastradas
- Histórico de movimentações

### Controle de Prazos e Alertas

#### Alertas Automáticos

**No Dashboard:**
- Card destacado com "Serviços em Atraso" (vermelho)
- Card "Serviços Próximos do Prazo" (amarelo)
- Contador numérico visível

**Notificações Toast:**
- Ao fazer login: "Você tem X serviços atrasados"
- Diariamente: Alerta de serviços que vencem amanhã

**Timeline na OS:**
- Linha do tempo visual mostrando:
  - Data de entrada
  - Prazo original
  - Alterações de prazo
  - Data de conclusão real

### Atribuição de Sapateiro

- Selecionar sapateiro ao criar/editar OS
- Filtrar OS por sapateiro
- Dashboard individual por sapateiro
- Registro de tempo de início e fim do serviço

### Tempo Médio por Tipo de Serviço

**Cálculo automático:**
- Sistema registra tempo real de execução
- Calcula média por tipo de serviço
- Exibe no cadastro de novo serviço como "tempo estimado"
- Relatório de produtividade

---

## 10. Sistema de Etiquetas (Impressão)

### Objetivo
Permitir a **identificação física dos sapatos** por meio de **etiquetas impressas**.

### Quando Imprimir
- Ao criar um **Orçamento** (opcional)
- Ao converter para **Ordem de Serviço (OS)** (padrão)
- A qualquer momento via botão "Reimprimir Etiqueta"

### Conteúdo da Etiqueta

**Layout simples e legível:**

```
┌────────────────────────────────┐
│  SAPATARIA [NOME]              │
│                                │
│  OS: #00123                    │
│  Cliente: João Silva           │
│  Entrada: 09/02/2026           │
│  Entrega: 16/02/2026           │
│                                │
│  Serviço: Troca de Sola        │
│  Qtd: 1 par                    │
│  Local: Prateleira A - Caixa 3 │
│                                │
│  [  QR CODE  ]                 │
└────────────────────────────────┘
```

### Formato de Impressão
- Impressão direta via navegador (window.print)
- Layout otimizado para:
  - Impressora térmica (80mm)
  - Etiqueta adesiva A4
  - Papel comum A4 (modo econômico)
- CSS específico para impressão (@media print)

### Usabilidade
- Botão único: **"Imprimir Etiqueta"**
- Pré-visualização antes de imprimir
- Opção de imprimir múltiplas etiquetas

---

## 11. Gestão Financeira

### 11.1 Pagamentos

#### Formas de Pagamento
- Dinheiro
- PIX
- Cartão de Débito
- Cartão de Crédito

#### Pagamentos Parciais

**Sistema completo de parcelamento:**

Ao criar/finalizar OS, permitir:
- **À vista:** Pagamento total
- **Parcelado:** Definir número de parcelas

**Registro de Pagamentos:**
- Cada parcela registrada individualmente
- Status: Pendente, Pago, Atrasado
- Data de vencimento
- Data de pagamento efetivo
- Valor pago
- Forma de pagamento
- Juros/multa (se aplicável)

**Interface:**
```
Valor Total: R$ 150,00
Entrada: R$ 50,00
Saldo: R$ 100,00

┌─────────────────────────────────────┐
│ Parcela 1: R$ 50,00 - Venc: 16/02  │ ✅ PAGO
│ Parcela 2: R$ 50,00 - Venc: 16/03  │ ⏳ PENDENTE
└─────────────────────────────────────┘

[+ Registrar Pagamento]
```

#### Histórico de Pagamentos
- Timeline com todos os pagamentos
- Comprovantes anexados (opcional)
- Filtros por período e forma de pagamento

### 11.2 Controle de Inadimplência

**Identificação Automática:**
- Cliente com parcela vencida > 7 dias
- Badge vermelho "INADIMPLENTE" no cadastro
- Alerta ao tentar criar nova OS para cliente inadimplente

**Dashboard de Inadimplência:**
- Lista de clientes com pagamentos em atraso
- Valor total em atraso
- Ações: Enviar lembrete WhatsApp, Negociar

### 11.3 Despesas Operacionais

**Categorias de Despesas:**
- Materiais (couro, cola, tintas, solas, etc.)
- Aluguel
- Energia elétrica
- Água
- Telefone/Internet
- Salários/Pró-labore
- Impostos
- Manutenção de equipamentos
- Outras

**Cadastro de Despesa:**
- Descrição
- Categoria
- Valor
- Data de vencimento
- Data de pagamento
- Forma de pagamento
- Recorrente (sim/não, periodicidade)
- Anexar comprovante (opcional)

**Despesas Recorrentes:**
- Sistema gera automaticamente todo mês
- Ex: Aluguel, salários, energia
- Editar/excluir a qualquer momento

### 11.4 Fechamento de Caixa Diário

**Fluxo de Fechamento:**

1. **Abertura do Caixa** (início do dia)
   - Saldo inicial (dinheiro em caixa)
   - Responsável

2. **Movimentações do Dia**
   - Receitas (pagamentos de OS)
   - Despesas pagas
   - Retiradas (sangria)

3. **Fechamento** (fim do dia)
   - Saldo esperado (calculado)
   - Saldo real (contado)
   - Diferença (quebra de caixa)
   - Observações
   - Responsável

**Interface do Caixa:**
```
┌─────────────────────────────────────┐
│  CAIXA - 09/02/2026                │
│                                     │
│  Saldo Inicial:    R$  200,00      │
│  (+) Receitas:     R$  850,00      │
│  (-) Despesas:     R$  120,00      │
│  (-) Retiradas:    R$  300,00      │
│  ─────────────────────────────      │
│  Saldo Esperado:   R$  630,00      │
│  Saldo Real:       R$  630,00      │
│  Diferença:        R$    0,00      │
│                                     │
│  [Fechar Caixa]                    │
└─────────────────────────────────────┘
```

**Histórico de Caixas:**
- Listar todos os fechamentos
- Filtrar por período
- Exportar para Excel/PDF

### 11.5 Relatório de Lucro/Prejuízo

**Período selecionável:**
- Hoje
- Esta semana
- Este mês
- Personalizado

**Dados do Relatório:**

```
RELATÓRIO FINANCEIRO - FEVEREIRO/2026
════════════════════════════════════════

RECEITAS
├─ Serviços Concluídos:    R$ 5.400,00
├─ Pagamentos Recebidos:   R$ 4.800,00
└─ A Receber:              R$   600,00

DESPESAS
├─ Materiais:              R$ 1.200,00
├─ Aluguel:                R$   800,00
├─ Energia:                R$   150,00
├─ Salários:               R$ 2.000,00
└─ Outras:                 R$   250,00
    ─────────────────────────────────
    Total Despesas:        R$ 4.400,00

════════════════════════════════════════
LUCRO LÍQUIDO:             R$ 1.000,00
════════════════════════════════════════

Margem de Lucro: 20,83%
```

**Visualização:**
- Gráfico simples de barras (Receitas vs Despesas)
- Evolução mensal (últimos 6 meses)

---

## 12. Emissão de Recibos

### Dados da Sapataria (Empresa)

**Cadastro único no sistema:**
- Nome da sapataria (razão social/nome fantasia)
- CNPJ/CPF
- Endereço completo
- Telefone / WhatsApp
- E-mail (opcional)
- Logotipo (upload de imagem)

> Configurado uma vez em "Configurações da Empresa"

### Dados do Recibo

**Informações Obrigatórias:**
- Número do recibo (sequencial)
- Número da OS vinculada
- Data de emissão
- Dados da empresa
- Dados do cliente
- Serviços realizados (descrição)
- Valores (unitários e total)
- Forma de pagamento
- Valor pago
- Valor pendente (se houver)

**Informações Adicionais:**
- **Garantia:** 30 dias (configurável)
- **Termos e Condições:**
  - "Não nos responsabilizamos por objetos deixados após 90 dias"
  - "Garantia válida apenas para o serviço executado"
  - Personalizável no sistema

### Layout do Recibo

```
┌────────────────────────────────────────────┐
│          [LOGO] SAPATARIA MODELO           │
│     CNPJ: 00.000.000/0001-00              │
│  Rua Exemplo, 123 - Centro - Cidade/UF   │
│        Tel: (00) 0000-0000                │
│                                            │
│        RECIBO Nº 000123                   │
│        OS Nº 000456                       │
│        Data: 09/02/2026                   │
│                                            │
│  Cliente: João da Silva                   │
│  CPF: 000.000.000-00                      │
│  Tel: (00) 00000-0000                     │
│                                            │
│  SERVIÇOS REALIZADOS                      │
│  ────────────────────────────────────     │
│  1x Troca de Sola (Social Preto)          │
│                             R$ 80,00      │
│  1x Costura (Tênis Azul)                  │
│                             R$ 40,00      │
│                                            │
│  ────────────────────────────────────     │
│  VALOR TOTAL:               R$ 120,00     │
│  Forma de Pagamento: PIX                  │
│  Valor Pago:                R$ 120,00     │
│                                            │
│  GARANTIA: 30 dias                        │
│                                            │
│  TERMOS E CONDIÇÕES                       │
│  • Garantia válida apenas para o serviço │
│    executado                              │
│  • Objetos não retirados em 90 dias      │
│    serão descartados                      │
│                                            │
│  ___________________                      │
│  Assinatura do Cliente                    │
└────────────────────────────────────────────┘
```

### Funcionalidades do Recibo
- Visualização em tela
- Impressão direta
- Download em PDF
- Compartilhar via WhatsApp
- Enviar por e-mail (opcional)
- Histórico de recibos emitidos

---

## 13. Compartilhamento via WhatsApp

### Documentos Compartilháveis
- Orçamento
- Ordem de Serviço
- Recibo

### Fluxo de Compartilhamento

1. Botão **"Compartilhar no WhatsApp"** em cada documento
2. Sistema gera **link público seguro** (token único)
3. Abre WhatsApp Web/App com mensagem pré-formatada:

```
Olá João! 👋

Segue o(a) *Orçamento #00123* da Sapataria Modelo:

🔗 https://sistema.com/view/abc123xyz

📱 Qualquer dúvida, entre em contato!

Sapataria Modelo
(00) 0000-0000
```

### Página de Visualização Pública

**Características:**
- Layout responsivo (mobile-first)
- Sem necessidade de login
- Apenas leitura
- Exibe dados completos do documento
- Botão "Imprimir"
- Botão "Baixar PDF"

**Segurança:**
- Token único e aleatório (32 caracteres)
- Validade: 30 dias (configurável)
- Somente leitura
- Sem acesso ao sistema interno
- Log de acessos

---

## 14. Dashboard de Gestão

### Visão Geral (Cards Principais)

```
┌──────────────────┬──────────────────┬──────────────────┐
│  SERVIÇOS        │  SERVIÇOS        │  SERVIÇOS        │
│  ATRASADOS       │  HOJE            │  AMANHÃ          │
│                  │                  │                  │
│     🔴 5         │     🟢 8         │     🟡 12        │
└──────────────────┴──────────────────┴──────────────────┘

┌──────────────────┬──────────────────┬──────────────────┐
│  ORÇAMENTOS      │  EM REPARO       │  AGUARDANDO      │
│  PENDENTES       │                  │  RETIRADA        │
│                  │                  │                  │
│       3          │       15         │       7          │
└──────────────────┴──────────────────┴──────────────────┘

┌─────────────────────────────────────────────────────────┐
│  FINANCEIRO (HOJE)                                      │
│  Receitas: R$ 450,00  |  Despesas: R$ 80,00            │
│  Saldo: R$ 370,00                                      │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  INADIMPLÊNCIA                                          │
│  Clientes: 3  |  Valor Total: R$ 280,00                │
└─────────────────────────────────────────────────────────┘
```

### Lista de Serviços em Destaque

**Serviços Atrasados (vermelho):**
- Cliente
- OS
- Prazo original
- Dias de atraso
- Ação rápida: "Avisar Cliente"

**Serviços Próximos (amarelo):**
- Cliente
- OS
- Prazo
- Tempo restante

### Indicadores Simples
- Total faturado (hoje/semana/mês)
- Serviços ativos
- Taxa de conversão (orçamento → OS)
- Ticket médio

### Atalhos Rápidos
- [+ Novo Orçamento]
- [+ Nova OS]
- [🔍 Buscar Sapato]
- [💰 Abrir Caixa]
- [📊 Relatórios]

---

## 15. Pesquisa e Localização

### Busca Global (Barra Superior)

Buscar por:
- **Cliente:** Nome, telefone, CPF
- **Número da OS/Orçamento**
- **Marca do sapato**
- **Localização física**
- **Sapateiro responsável**

### Filtros Avançados

**Na lista de OS:**
- Status
- Período (data entrada/entrega)
- Sapateiro
- Situação (no prazo, atrasado, próximo do prazo)
- Valor (faixa)

**Resultado:**
- Lista clara e organizada
- Destaque visual por urgência
- Ações rápidas: Visualizar, Editar, Imprimir

---

## 16. Controle de Usuários

### Perfis de Acesso

| Perfil | Permissões |
|--------|------------|
| **Administrador** | Acesso total ao sistema |
| **Gerente** | Tudo exceto: configurações, exclusão de registros |
| **Atendente** | Criar orçamento/OS, visualizar, emitir recibos |
| **Sapateiro** | Visualizar próprias OS, atualizar status |

### Permissões Detalhadas

**Atendente:**
- ✅ Criar/editar orçamento
- ✅ Converter em OS
- ✅ Registrar pagamentos
- ✅ Emitir recibos
- ✅ Visualizar clientes
- ❌ Excluir registros
- ❌ Acessar relatórios financeiros
- ❌ Gerenciar usuários

**Sapateiro:**
- ✅ Visualizar OS atribuídas
- ✅ Atualizar status da OS
- ✅ Adicionar observações
- ❌ Criar/editar valores
- ❌ Acessar financeiro

**Gerente:**
- ✅ Tudo que atendente faz
- ✅ Acessar relatórios
- ✅ Gerenciar despesas
- ✅ Fechar caixa
- ❌ Configurações do sistema
- ❌ Excluir usuários

**Administrador:**
- ✅ Acesso total
- ✅ Configurações do sistema
- ✅ Gerenciar usuários
- ✅ Backup/restore
- ✅ Logs de auditoria

---

## 17. Relatórios

### 17.1 Relatório de Serviços

**Filtros:**
- Período
- Status
- Sapateiro
- Tipo de serviço

**Dados Exibidos:**
- Quantidade de serviços
- Valor total
- Ticket médio
- Serviços por tipo
- Taxa de conclusão

### 17.2 Relatório Financeiro

- Receitas (total e por forma de pagamento)
- Despesas (total e por categoria)
- Lucro/Prejuízo
- Contas a receber
- Inadimplência

### 17.3 Relatório de Produtividade

**Por Sapateiro:**
- Quantidade de serviços concluídos
- Tempo médio por serviço
- Taxa de atraso
- Valor gerado

**Por Tipo de Serviço:**
- Serviços mais realizados
- Tempo médio de execução
- Valor médio

### 17.4 Relatório de Clientes

- Clientes mais frequentes
- Clientes inativos (> 90 dias sem serviço)
- Lifetime value por cliente
- Taxa de retorno

### Exportação
- PDF (visualização e download)
- Excel/CSV
- Impressão direta

---

## 18. Requisitos Não Funcionais

### Performance
- Tempo de resposta < 2 segundos
- Compressão de imagens automática
- Cache de queries frequentes
- Paginação em listas (25 itens/página)
- Índices otimizados no banco

### Responsividade
- Layout 100% responsivo (Bootstrap)
- Mobile-first design
- Testado em:
  - Desktop (1920x1080)
  - Tablet (768x1024)
  - Mobile (375x667)

### Backup
- **Backup automático diário** (2h da manhã)
- Retenção: 30 dias
- Armazenamento: pasta `/storage/backups`
- Notificação em caso de falha
- Opção de backup manual

### Logs
- Registro de erros PHP
- Log de ações críticas (auditoria)
- Rotação diária
- Retenção: 90 dias

### Compatibilidade
- Navegadores:
  - Chrome 90+
  - Firefox 88+
  - Safari 14+
  - Edge 90+
- PHP 8.0+
- MySQL 8.0+

---

## 19. Banco de Dados

### Estrutura (principais tabelas)

```sql
-- Usuários
usuarios
  id, nome, login, senha, perfil, ativo, created_at

-- Clientes
clientes
  id, nome, cpf, telefone, email, endereco, observacoes, created_at

-- Orçamentos
orcamentos
  id, numero, cliente_id, valor_total, desconto, valor_final,
  status, validade, created_by, created_at

-- Sapatos (itens do orçamento/OS)
sapatos
  id, orcamento_id, os_id, categoria, cor, modelo, tipo_servico,
  marca, valor, material, observacoes, foto_path

-- Ordens de Serviço
ordens_servico
  id, numero, orcamento_id, cliente_id, sapateiro_id,
  data_entrada, prazo_entrega, data_conclusao,
  valor_total, status, localizacao, observacoes,
  created_by, created_at

-- Pagamentos
pagamentos
  id, os_id, parcela_numero, valor, vencimento, data_pagamento,
  forma_pagamento, status, observacoes, created_at

-- Despesas
despesas
  id, descricao, categoria, valor, vencimento, data_pagamento,
  forma_pagamento, recorrente, periodicidade, comprovante_path,
  created_at

-- Caixa
caixa
  id, data, saldo_inicial, receitas, despesas, retiradas,
  saldo_esperado, saldo_real, diferenca, observacoes,
  responsavel_abertura, responsavel_fechamento,
  data_abertura, data_fechamento

-- Recibos
recibos
  id, numero, os_id, cliente_id, valor_total, forma_pagamento,
  garantia_dias, termos, created_by, created_at

-- Links Públicos
links_publicos
  id, token, tipo (orcamento/os/recibo), referencia_id,
  data_criacao, data_expiracao, acessos, ultimo_acesso

-- Logs de Auditoria
auditoria
  id, usuario_id, acao, tabela, registro_id,
  dados_antes, dados_depois, ip, created_at

-- Configurações
configuracoes
  id, chave, valor

-- Empresa
empresa
  id, nome, cnpj, endereco, telefone, email, logo_path
```

### Índices Importantes
```sql
CREATE INDEX idx_os_status ON ordens_servico(status);
CREATE INDEX idx_os_prazo ON ordens_servico(prazo_entrega);
CREATE INDEX idx_os_cliente ON ordens_servico(cliente_id);
CREATE INDEX idx_pagamentos_status ON pagamentos(status);
CREATE INDEX idx_clientes_telefone ON clientes(telefone);
```

---

## 20. Notificações Toast

### Biblioteca
- Bootstrap Toast (nativo do Bootstrap 5)
- Posicionamento: top-right
- Auto-hide: 5 segundos (configurável)

### Tipos de Notificação

**Sucesso (verde):**
```javascript
showToast('Ordem de serviço criada com sucesso!', 'success');
```

**Erro (vermelho):**
```javascript
showToast('Erro ao salvar. Tente novamente.', 'error');
```

**Aviso (amarelo):**
```javascript
showToast('Cliente possui débito pendente.', 'warning');
```

**Info (azul):**
```javascript
showToast('Orçamento enviado por WhatsApp.', 'info');
```

### Implementação Básica

```javascript
// toast.js
function showToast(message, type = 'info') {
  const colors = {
    success: '#28a745',
    error: '#dc3545',
    warning: '#ffc107',
    info: '#008bcd'
  };
  
  const toastHTML = `
    <div class="toast align-items-center text-white border-0" 
         style="background-color: ${colors[type]}" 
         role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                data-bs-dismiss="toast"></button>
      </div>
    </div>
  `;
  
  document.getElementById('toast-container').innerHTML = toastHTML;
  const toast = new bootstrap.Toast(document.querySelector('.toast'));
  toast.show();
}
```

---

## 21. Diferenciais do Sistema

### Pontos Fortes
1. **Gestão financeira completa** (pagamentos parciais, despesas, caixa)
2. **Controle de prazos inteligente** (alertas automáticos)
3. **Histórico completo do cliente** (timeline visual)
4. **Sistema de cores por urgência** (identificação rápida)
5. **Compartilhamento via WhatsApp** (facilita comunicação)
6. **Etiquetas de identificação** (reduz extravios)
7. **Relatórios gerenciais** (tomada de decisão)
8. **Controle de inadimplência** (reduz perdas)
9. **Backup automático** (segurança dos dados)
10. **Interface intuitiva** (fácil uso)

### Benefícios Práticos
- ❌ Fim dos cadernos e papéis perdidos
- ❌ Fim de sapatos extraviados
- ❌ Fim de prazos esquecidos
- ✅ Controle total do financeiro
- ✅ Histórico completo de cada cliente
- ✅ Comunicação rápida via WhatsApp
- ✅ Relatórios para decisões estratégicas

---

---

## 23. Cronograma de Desenvolvimento (Sugestão)

### Sprint 1 (Semanas 1-2) - Base
- ✅ Estrutura do projeto
- ✅ Banco de dados
- ✅ Autenticação
- ✅ CRUD de clientes

### Sprint 2 (Semanas 3-4) - Orçamentos
- ✅ Cadastro de orçamentos
- ✅ Cadastro de sapatos
- ✅ Upload de imagens
- ✅ Cálculos automáticos

### Sprint 3 (Semanas 5-6) - Ordens de Serviço
- ✅ Conversão orçamento → OS
- ✅ Controle de status
- ✅ Sistema de etiquetas
- ✅ Controle de prazos

### Sprint 4 (Semanas 7-8) - Financeiro
- ✅ Pagamentos parciais
- ✅ Despesas
- ✅ Fechamento de caixa
- ✅ Relatório de lucro/prejuízo

### Sprint 5 (Semanas 9-10) - Complementos
- ✅ Emissão de recibos
- ✅ Compartilhamento WhatsApp
- ✅ Dashboard completo
- ✅ Relatórios

### Sprint 6 (Semanas 11-12) - Refinamento
- ✅ Testes completos
- ✅ Ajustes de UX
- ✅ Documentação
- ✅ Deploy

**Tempo Total Estimado:** 12 semanas (3 meses)

---

## 24. Considerações Finais

Este sistema foi projetado pensando na **realidade da sapataria tradicional brasileira**, com foco em:

1. **Simplicidade:** Qualquer pessoa consegue usar
2. **Completude:** Cobre todas as necessidades do negócio
3. **Segurança:** Proteção de dados e backup automático
4. **Praticidade:** Poucos cliques, máxima eficiência
5. **Modernidade:** Tecnologias atuais, mas acessíveis

O sistema elimina os principais problemas das sapatarias:
- ❌ Perda de sapatos
- ❌ Esquecimento de prazos
- ❌ Descontrole financeiro
- ❌ Falta de histórico
- ❌ Comunicação falha com clientes

E entrega uma gestão **profissional, organizada e lucrativa**.

---

**SPEC v2.0 - Sistema de Controle de Sapataria**  
**Data:** 09/02/2026  
**Stack:** PHP 8.x + MySQL + Bootstrap 5.3  
**Cor Primária:** #008bcd  
**Notificações:** Bootstrap Toast
