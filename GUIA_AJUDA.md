# ShoesAZ — Guia de Ajuda (Novos Usuários + Pesquisas)

Este guia explica **como o sistema funciona**, como navegar e principalmente **como pesquisar/achar informações** (clientes, OS, orçamentos e financeiro) de forma rápida.

---

## 1) Visão rápida do sistema

O ShoesAZ é organizado em módulos principais:

- **Dashboard**: visão geral do dia (OS em atraso, entregas, caixa, contas a receber).
- **Clientes**: cadastro e consulta de clientes.
- **Orçamentos**: criação e aprovação; pode **converter em OS**.
- **Ordens de Serviço (OS)**: acompanhamento do serviço (prazo, status, localização, sapateiro).
- **Financeiro** (perfis Gerente/Administrador): **Caixa**, **Contas a Receber**, **Pagamentos**, **Despesas**, **Recibos**.
- **Relatórios** (perfis Gerente/Administrador): relatórios gerenciais.
- **Configurações/Usuários/Backup** (Administrador): cadastros e manutenção.

---

## 2) Perfis e permissões (quem vê o quê)

A navegação (menu) muda conforme o seu perfil:

- **Administrador**
  - Acesso total.
  - Vê: Financeiro, Relatórios, Usuários, Dados da Empresa, Backup.

- **Gerente**
  - Acesso a operação + financeiro.
  - Vê: Financeiro e Relatórios.

- **Atendente**
  - Acesso a Clientes, Orçamentos e OS.
  - Normalmente não vê o Financeiro no menu.

- **Sapateiro**
  - Vê apenas **Minhas OS**.

---

## 3) Como navegar (padrão do sistema)

- **Menu lateral**
  - Fica à esquerda (desktop) e pode ser recolhido.
  - No celular, abre como menu lateral (offcanvas).

- **Barra superior (topo)**
  - Mostra seu nome e botão **Sair**.
  - Possui a **Busca Global** (campo “Buscar...”).

- **Padrão de telas**
  - Título + descrição curta.
  - Filtros no topo.
  - Listagem em tabela.
  - Botões de ação à direita (ex: “Abrir”, “Editar”, “Etiqueta”).

---

# 4) Pesquisas — o jeito mais rápido de achar tudo

O sistema tem **dois níveis** de pesquisa:

- **Busca Global**: pesquisa “geral” e rápida, no topo.
- **Filtros por módulo**: pesquisa “mais precisa”, dentro de cada tela.

---

## 4.1) Busca Global (topo) — `/busca`

Use quando você não sabe “em qual tela está” a informação.

### O que ela encontra

Digite um termo e o sistema retorna:

- **Clientes**
  - Nome
  - Telefone
  - CPF

- **Ordens de Serviço (OS)**
  - Número da OS
  - Nome do cliente
  - **Localização** (ex: prateleira/caixa)

- **Orçamentos**
  - Número do orçamento
  - Nome do cliente

### Boas práticas (como digitar)

- Digite **parte do nome**: `joao`.
- Digite **telefone** (com ou sem formatação): `6999`.
- Digite o **número** do documento:
  - OS: `2026-...` (se aplicável) ou o número exibido.
  - Orçamento: `2026-...`.
- Para achar rápido uma OS física na loja, use a **localização**: `Prateleira A`.

### O que aparece no resultado

- Cards separados por tipo:
  - **Clientes**
  - **Ordens de Serviço**
  - **Orçamentos**
- Cada item tem um link direto para abrir:
  - Cliente → editar
  - OS → abrir
  - Orçamento → abrir

---

## 4.2) Pesquisa por módulo (mais precisa)

Abaixo estão as pesquisas “oficiais” de cada tela (as mesmas que o sistema mostra na interface).

### A) Clientes — `/clientes`

**Campo de busca**: `q`

- Busca por:
  - Nome
  - Telefone
  - CPF

**Dicas**

- Se não achar pelo nome completo, tente só o primeiro nome.
- Se o cliente não aparece, clique em **Limpar** e tente novamente.

---

### B) Orçamentos — `/orcamentos`

**Filtros**

- `q`: busca por número do orçamento, nome do cliente ou telefone
- `status`: filtra por status
  - Aguardando
  - Aprovado
  - Reprovado
  - Expirado
  - Convertido

**Dicas**

- Para localizar rapidamente um orçamento, use:
  - o **número** do orçamento; ou
  - parte do nome do cliente.

---

### C) Ordens de Serviço (OS) — `/os`

**Filtros**

- `q`: busca por número da OS, nome do cliente ou telefone
- `status`: Recebido / Em reparo / Aguardando retirada / Entregue / Cancelado
- `atrasados=1`: (para perfis não-sapateiro) mostra apenas OS com prazo vencido

**Como interpretar as cores**

- **Badge do prazo**
  - Verde: dentro do prazo
  - Amarelo: próximo do prazo
  - Vermelho: atraso

- **Badge do status**
  - Ajuda a ver rapidamente em que etapa a OS está.

**Atalho importante**

- Botão **Etiqueta**: abre a etiqueta para impressão.

---

### D) Pagamentos — `/pagamentos`

**Filtros**

- `os_id`: seleciona uma OS específica
- `status`: Pendente / Pago / Atrasado

**Dica operacional (muito importante)**

- Para registrar ou quitar pagamento como **Pago**, normalmente o sistema exige **Caixa aberto** no dia.

---

### E) Contas a Receber — `/contas-receber`

Tela focada em **parcelas pendentes**.

**Filtros**

- `q`: busca por OS, cliente ou telefone
- `atrasados=1`: somente vencidas

**Dica**

- Use essa tela quando o cliente pergunta “o que falta pagar?”.

---

### F) Despesas — `/despesas`

**Filtros**

- `categoria`
- `data_inicio` e `data_fim` (por data de criação da despesa)

**Dica operacional**

- Se cadastrar despesa já como paga (`data_pagamento` preenchida), ela impacta o caixa do dia (se o caixa estiver aberto).

---

### G) Caixa — `/caixa`

O caixa é o “resumo do dia” de entradas e saídas.

**Ações comuns**

- **Abrir Caixa**: define saldo inicial.
- **Fechar Caixa**: informa saldo real e o sistema calcula diferença.
- **Retirada**: registra sangria com motivo.
- **Importar Pagamentos**: puxa pagamentos do dia que estão como “Pago” e ainda não entraram no caixa.

---

# 5) Template de Pesquisa (para treinar equipe)

Copie e use este roteiro quando alguém pedir ajuda para “achar” algo no sistema.

## 5.1) Perguntas rápidas (30 segundos)

1. **O que você quer achar?**
   - Cliente
   - OS
   - Orçamento
   - Parcela/conta a receber
   - Pagamento
   - Despesa

2. **Você tem algum identificador?**
   - Nome (mesmo incompleto)
   - Telefone
   - CPF
   - Número da OS / Orçamento
   - Localização (prateleira/caixa)

3. **É algo do dia/urgente?**
   - Sim → use telas do **Dashboard** e filtros rápidos.
   - Não → use listagens e filtros por período/status.

## 5.2) Caminho recomendado (ordem de tentativa)

- **1ª tentativa (mais rápida)**: **Busca Global** (topo) digitando nome/telefone/número/localização.
- **2ª tentativa**: Entrar no módulo certo e usar filtro:
  - Cliente → `/clientes` (campo `q`)
  - OS → `/os` (campo `q` + status + atrasados)
  - Orçamento → `/orcamentos` (campo `q` + status)
  - Parcelas pendentes → `/contas-receber`

## 5.3) Exemplos prontos

- “Quero achar a OS do João”
  - Busca Global: `joao`
  - Se não vier: OS (`/os`) → `q=joao`

- “O cliente ligou, disse que o telefone termina em 8899”
  - Busca Global: `8899`
  - Clientes (`/clientes`): `q=8899`

- “Cadê o sapato que está na Prateleira B?”
  - Busca Global: `Prateleira B` (procura em **localização** da OS)

- “Quais parcelas estão vencidas?”
  - Contas a Receber (`/contas-receber`) → marcar **Somente atrasados**

---

# 6) Problemas comuns (e o que fazer)

- **“Não aparece nada na busca”**
  - Verifique se digitou algo (algumas telas aceitam vazio, mas não retornam resultados úteis).
  - Tente com menos informação (apenas parte do nome ou parte do telefone).
  - Clique em **Limpar** e tente de novo.

- **“Não consigo quitar pagamento / registrar como Pago”**
  - Verifique se o **Caixa do dia está aberto**.

- **“Não vejo o menu Financeiro”**
  - Provavelmente seu perfil é **Atendente** ou **Sapateiro**.
  - Peça ao Administrador para ajustar permissões, se necessário.

---

## 7) Atalhos (URLs úteis)

- Dashboard: `/`
- Busca Global: `/busca`
- Clientes: `/clientes`
- Orçamentos: `/orcamentos`
- OS: `/os`
- Caixa: `/caixa`
- Contas a Receber: `/contas-receber`
- Pagamentos: `/pagamentos`
- Despesas: `/despesas`
- Recibos: `/recibos`
- Relatórios: `/relatorios`

---

## 8) Observação técnica (para quem administra)

- O sistema usa o caminho base configurado como **`/ShoesAZ`** e monta URLs internamente.
- As rotas principais estão registradas no arquivo `index.php` (na raiz do projeto).
- Existe também um `public/index.php`, mas o roteamento completo está no `index.php` da raiz.
