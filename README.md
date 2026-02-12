# ShoesAZ - Sistema de Controle de Sapataria

Sistema web para gestão completa de sapataria, com controle de orçamentos, ordens de serviço, clientes e financeiro.

## Requisitos

- PHP 8.0+
- MySQL 8.0+
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL, GD (para processamento de imagens)

## Instalação

### 1. Configurar o Banco de Dados

1. Crie o banco de dados no MySQL:
   ```sql
   CREATE DATABASE shoesaz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Importe o schema:
   ```bash
   mysql -u root -p shoesaz < database/schema.sql
   ```

3. Importe o seed (usuário admin):
   ```bash
   mysql -u root -p shoesaz < database/seed.sql
   ```

   **Usuário padrão:**
   - Login: `admin`
   - Senha: `password` (alterar no primeiro login)

### 2. Configurar a Conexão

Edite `config/database.php` com suas credenciais:

```php
return [
    'dsn' => 'mysql:host=127.0.0.1;dbname=shoesaz;charset=utf8mb4',
    'username' => 'root',
    'password' => '',  // sua senha do MySQL
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

### 3. Permissões de Pasta

Certifique-se de que a pasta de uploads tenha permissões de escrita:

```bash
chmod -R 755 public/uploads/
```

### 4. Acessar o Sistema

Abra no navegador:
```
http://localhost/ShoesAZ/public/
```

## Estrutura de Perfis

| Perfil | Permissões |
|--------|------------|
| **Administrador** | Acesso total |
| **Gerente** | Caixa, despesas, relatórios, orçamentos, OS |
| **Atendente** | Clientes, orçamentos, OS, recibos (sem financeiro) |
| **Sapateiro** | Apenas visualizar e atualizar suas OS |

## Funcionalidades Principais

### Orçamentos
- Cadastro com número sequencial por ano (ex: 2026-000001)
- Status: Aguardando, Aprovado, Reprovado, Expirado, Convertido
- Adicionar múltiplos sapatos/serviços
- Aprovação e conversão automática em OS

### Ordens de Serviço (OS)
- Controle de status: Recebido, Em reparo, Aguardando retirada, Entregue, Cancelado
- Alertas de prazo (verde, amarelo, vermelho)
- Atribuição a sapateiros
- Impressão de etiquetas

### Financeiro
- **Pagamentos**: Parcelas, controle de inadimplência
- **Caixa**: Abertura, fechamento, sangria
- **Despesas**: Categorizadas, recorrentes
- **Recibos**: Com garantia e termos personalizáveis

### Links Públicos
- Compartilhamento via WhatsApp
- Visualização externa sem login
- Válido por 30 dias (configurável)

## Segurança

- Senhas com bcrypt
- Proteção CSRF em todos os formulários
- Limite de tentativas de login (5 tentativas / 15 minutos)
- Timeout de sessão (2 horas)
- Auditoria de ações críticas

## Suporte

Para dúvidas ou problemas, consulte a documentação do SPEC em `SPEC_ShoesAZ.md`.

---
**Versão:** 2.0  
**Stack:** PHP 8.x + MySQL + Bootstrap 5.3
