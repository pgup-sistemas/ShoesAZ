# 🔧 GUIA DO INSTALADOR DO SHOESAZ

## 📋 O que foi Implementado

Um **sistema de instalação automático** que:

✅ Detecta se o sistema já está instalado  
✅ Redireciona automaticamente para o instalador na primeira vez  
✅ Cria tabelas do banco de dados  
✅ Cria usuário administrador  
✅ Interface moderna e responsiva  
✅ Proteção contra reinstalação acidental  

---

## 🚀 Como Usar

### Primeira Vez (Sistema Novo)

1. **Subir arquivo no servidor:**
   ```bash
   git clone <repo> /home/usuario/shoesaz
   # ou
   rsync -av . /home/usuario/shoesaz/
   ```

2. **Acessar no navegador:**
   ```
   https://shoesaz.pageup.net.br/
   ```
   
   ✨ Sistema detecta que não está instalado e redireciona para:
   ```
   https://shoesaz.pageup.net.br/install.php
   ```

3. **Preencher formulário:**
   - 👤 Nome do Administrador (Ex: João Silva)
   - 🔑 Login do Administrador (Ex: admin)
   - 🔐 Senha do Administrador (use senha forte!)

4. **Clicar "Instalar Agora"**

5. **Sucesso!** ✅
   - Banco de dados criado
   - Tabelas criadas
   - Admin criado
   - Sistema pronto para uso

---

## 🔒 Proteção de Segurança

### Arquivos de Lock

Após instalação bem-sucedida, 2 arquivos são criados:

```
database/install.lock     ← Arquivo de lock tradicional
.installed                ← Flag de instalação
```

**Por quê 2 arquivos?**
- Redundância: garante que o instalador não rode novamente
- Compatibilidade: alguns sistemas limpam cache e perdem um arquivo

### Proteção Contra Reinstalação

Se tentar acessar `install.php` após instalação:

```
❌ Erro 403 (Forbidden)
✅ Página amigável indicando que já está instalado
```

---

## 🔄 Se Precisar Reinstalar

### Opção 1: Via SSH (Recomendado)

```bash
ssh user@shoesaz.pageup.net.br

cd /home/usuario/shoesaz

# Remover arquivos de lock
rm database/install.lock
rm .installed

# Agora pode rodar instalador novamente
```

### Opção 2: Via Painel de Controle

Se tiver acesso ao cPanel/Plesk:
1. File Manager
2. Navegue até `database/` e `raiz`
3. Delete `install.lock` e `.installed`
4. Acesse `/install.php` novamente

### Opção 3: Via FTP

Use cliente FTP para deletar os arquivos

---

## 📁 Arquivos do Sistema

### Criados/Modificados

```
app/Core/Installer.php           ✨ NOVO - Helper de instalação
install.php                       ✏️ MODIFICADO - Interface melhorada
index.php                         ✏️ MODIFICADO - Redirecionamento automático
database/install.lock             📌 CRIADO AUTOMATICAMENTE
.installed                        📌 CRIADO AUTOMATICAMENTE
```

### Não Modificados (Existentes)

```
database/schema.sql               ✅ Schema do BD (intacto)
database/seed.sql                 ✅ Seed inicial (se houver)
config/database.php               ✅ Config BD (intacto)
```

---

## 🎯 Fluxo de Instalação

### Primeira Vez

```
Acesso a https://shoesaz.pageup.net.br
         ↓
index.php detecta: install.lock NÃO existe
         ↓
Redireciona para: /install.php
         ↓
install.php carrega (sem lock)
         ↓
Formulário de instalação
         ↓
Usuário preenche dados
         ↓
POST para install.php
         ↓
Criar BD (se não existir)
Criar tabelas (schema.sql)
Criar admin
         ↓
Criar install.lock + .installed
         ↓
Exibir sucesso ✅
         ↓
Usuário acessa dashboard (/) →  Login
```

### Próximas Vezes

```
Acesso a https://shoesaz.pageup.net.br
         ↓
index.php detecta: install.lock EXISTE
         ↓
Continua normalmente
         ↓
Se não autenticado → Redireciona para /login
Se autenticado → Carrega dashboard
```

### Tentativa de Acessar install.php Novamente

```
Acesso a https://shoesaz.pageup.net.br/install.php
         ↓
install.php detecta: install.lock EXISTE
         ↓
Retorna erro 403
         ↓
Exibe: "Sistema Já Instalado"
```

---

## 🔑 Dados Padrão Após Instalação

### Usuário Admin Criado

| Campo | Valor |
|-------|-------|
| Nome | Conforme preenchimento |
| Login | Conforme preenchimento |
| Senha | Hash seguro (bcrypt) |
| Perfil | Administrador |
| Ativo | Sim |

### Banco de Dados

| Tabela | Status |
|--------|--------|
| usuarios | ✅ Criada com admin |
| clientes | ✅ Criada vazia |
| ordens_servico | ✅ Criada vazia |
| pagamentos | ✅ Criada vazia |
| orcamentos | ✅ Criada vazia |
| caixa | ✅ Criada vazia |
| ... (todas) | ✅ Criadas |

---

## ⚠️ Troubleshooting

### Problema: "Erro de conexão com banco"

**Causa:** Credenciais de BD incorretas  
**Solução:**
```php
// Verificar config/database.php
$host = 'shoesaz.mysql.dbaas.com.br'
$name = 'shoesaz'
$username = 'shoesaz'
$password = 'Shoesaz#2026'
```

### Problema: "Permissão negada ao criar lock"

**Causa:** Permissões insuficientes no servidor  
**Solução:**
```bash
chmod 755 database/
chmod 755 .  # raiz do projeto
```

### Problema: "Instalador não carrega"

**Causa:** Arquivo `install.php` não está acessível  
**Solução:**
```bash
# Verificar se arquivo existe
ls -la install.php

# Verificar permissões
chmod 644 install.php

# Verificar acesso via URL
curl https://shoesaz.pageup.net.br/install.php
```

### Problema: "Tela branca após instalação"

**Causa:** Erro no PHP  
**Solução:**
```bash
# Verificar error log
tail -f /home/usuario/logs/php_errors.log

# Se houver erro, informar suporte
```

---

## 🔐 Segurança

### O que o Instalador Protege

✅ Bloqueia reinstalação (lock files)  
✅ Valida dados de entrada  
✅ Hash de senha com bcrypt  
✅ Transação de BD (rollback se falhar)  
✅ Mensagens de erro seguras (sem stack trace)  
✅ Verifica se usuários já existem  

### O que Você Deve Fazer

1. **Após instalação:**
   - ✅ Alterar senha do admin (no painel)
   - ✅ Configurar empresa (Configurações → Empresa)
   - ✅ Criar backup do BD

2. **Segurança de acesso:**
   - ✅ Usar HTTPS sempre
   - ✅ Senhas fortes
   - ✅ 2FA se disponível

3. **Manutenção:**
   - ✅ Backups regulares
   - ✅ Atualizar sistema
   - ✅ Monitorar logs

---

## 📊 Arquitetura

### Componentes

```
install.php
├─ parseDsn() → Fazer parse da conexão
├─ buildMysqlDsn() → Construir DSN MySQL
├─ sqlStatementsFromFile() → Ler schema.sql
└─ Lógica de instalação
    ├─ Validar dados
    ├─ Conectar ao BD
    ├─ Criar BD
    ├─ Executar schema
    ├─ Criar admin
    └─ Criar lock files

app/Core/Installer.php (Helper)
├─ isInstalled() → Verificar se instalado
├─ markAsInstalled() → Marcar como instalado
├─ uninstall() → Remover marca
└─ getInfo() → Obter info de instalação

index.php (Entry Point)
└─ Verificar Installer::isInstalled()
   ├─ Se não → Redirecionar para install.php
   └─ Se sim → Continuar normalmente
```

---

## 🎓 Fluxo Técnico Detalhado

### 1. Acesso Inicial

```php
// index.php (linhas iniciais)
if (!is_file($lockFile) && !is_file($installedFlag)) {
    header('Location: /install.php', true, 302);
    exit;
}
```

### 2. Instalador Carrega

```php
// install.php (no topo)
if (is_file($lockFile) || is_file($installedFlag)) {
    // Já instalado - erro 403
    http_response_code(403);
    exit;
}
```

### 3. Processamento de Formulário

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    // Conectar BD
    // Criar BD + tabelas
    // Criar admin
    // Criar locks
}
```

### 4. Redirecionamento

```php
// Após instalação bem-sucedida
file_put_contents($lockFile, 'installed_at=' . date('c'));
file_put_contents($installedFlag, 'installed_at=' . date('c'));

// Próxima vez que entrar em install.php
// → Erro 403 (já instalado)

// Próxima vez que entrar em /
// → Passa da verificação
// → index.php continua normalmente
```

---

## 📞 Suporte

**Problemas com instalação?**

1. Verifique error_log
2. Consulte DIAGNOSTICO_ROTAS.md
3. Veja permissões de arquivo
4. Valide credenciais de BD

---

**Gerado em:** 2026-02-18  
**Versão:** 1.0  
**Sistema:** ShoesAZ
