# 🚀 GUIA DE DEPLOY - CORREÇÃO DE ROTAS

## 📋 Checklist pré-deploy

- [ ] Backup completo do servidor (banco de dados + código)
- [ ] Testes locais executados com sucesso
- [ ] Arquivos de configuração verificados
- [ ] Equipe notificada

---

## 🔄 PASSOS DE DEPLOY

### 1️⃣ Fazer Backup (CRUCIAL!)
```bash
# No servidor de produção
cd /home/shoesaz/

# Backup do banco de dados
mysqldump -u shoesaz -p shoesaz > backup_rotas_$(date +%Y%m%d_%H%M%S).sql

# Backup do código
tar -czf backup_code_$(date +%Y%m%d_%H%M%S).tar.gz public_html/
```

### 2️⃣ Upload dos Arquivos Novos
```bash
# Copiar para servidor via SFTP ou Git
# Arquivos a fazer upload:

# Modificados:
- app/Core/Router.php
- app/Core/DB.php
- index.php

# Novos:
- app/Core/Middleware.php
- app/Services/DashboardService.php
- DIAGNOSTICO_ROTAS.md
- RESUMO_MUDANCAS.md
- tests/route_test.php
```

### 3️⃣ Limpar Cache (se APCu estiver ativo)
```bash
# SSH no servidor
ssh user@shoesaz.pageup.net.br

# Criar script PHP para limpar cache
cat > /home/shoesaz/clear_cache.php << 'EOF'
<?php
if (extension_loaded('apcu')) {
    apcu_clear_cache();
    echo "Cache APCu limpo com sucesso!";
} else {
    echo "APCu não está instalado ou desabilitado";
}
?>
EOF

# Executar
php /home/shoesaz/clear_cache.php

# Deletar script
rm /home/shoesaz/clear_cache.php
```

### 4️⃣ Testar Rotas
```bash
# No servidor, dentro do diretório do projeto
cd /home/shoesaz/public_html/

# Executar teste
php tests/route_test.php
```

**Esperado:**
```
✅ Conectado ao banco de dados
✅ Tabelas verificadas
✅ DashboardService carregado
✅ Dashboard carregado em ~300-500ms (primeira vez)
```

### 5️⃣ Verificar Error Log
```bash
# Monitorar por 5 minutos
tail -f /home/shoesaz/logs/php_errors.log

# Procurar por erros (não deve haver "Router Error")
# Se houver, pode ser um erro específico para investigar
```

---

## ✅ TESTES PÓS-DEPLOY

### Via Browser
1. Abrir `shoesaz.pageup.net.br`
2. Fazer login com credenciais de teste
3. Ir para Dashboard (`/`)
4. Abrir DevTools (F12) → Network tab
5. Observar tempo de carregamento
   - Esperado: 500ms-1s (primeira vez)
   - Esperado: 50-200ms (próximas vezes)

### Via Teste Automated
```bash
# Criar script de teste
cat > /home/shoesaz/test_dashboard.php << 'EOF'
<?php
require __DIR__ . '/app/bootstrap.php';
$_SESSION['user'] = ['id' => 1, 'nome' => 'Test', 'login' => 'test', 'perfil' => 'Administrador'];

$start = microtime(true);
$stats = \App\Services\DashboardService::getStats();
$time = (microtime(true) - $start) * 1000;

echo "Dashboard loaded in " . number_format($time, 2) . "ms\n";
echo "Stats loaded: " . count($stats) . " metrics\n";

if ($time < 1000) {
    echo "✅ PASS\n";
    exit(0);
} else {
    echo "⚠️ SLOW\n";
    exit(1);
}
?>
EOF

# Executar
php /home/shoesaz/test_dashboard.php
```

---

## 🔍 TROUBLESHOOTING

### Erro: "Middleware not found"
**Solução:** Verificar se `app/Core/Middleware.php` foi enviado
```bash
ls -la /home/shoesaz/public_html/app/Core/Middleware.php
```

### Erro: "DashboardService not found"
**Solução:** Verificar se `app/Services/DashboardService.php` foi enviado
```bash
ls -la /home/shoesaz/public_html/app/Services/DashboardService.php
```

### Erro: "Call to undefined function apcu_fetch"
**Solução:** APCu não instalado (não é crítico, sistema funciona sem cache)
```bash
# Verificar se APCu está instalado
php -i | grep -i apcu

# Se não estiver, fazer upgrade é recomendado mas opcional
# Sistema funciona com fallback
```

### Slow Performance ainda presente
**Causas possíveis:**
1. Índices não criados no BD
2. Muitos registros na tabela (100k+)
3. Servidor sobrecarregado

**Soluções:**
```sql
-- Criar índices (ver DIAGNOSTICO_ROTAS.md)
ALTER TABLE ordens_servico ADD INDEX idx_status (status);
ALTER TABLE ordens_servico ADD INDEX idx_prazo_entrega (prazo_entrega);
-- etc...
```

---

## 📊 ROLLBACK (Se necessário)

Se algo der errado:

```bash
# 1. Restaurar banco de dados
mysql -u shoesaz -p shoesaz < backup_rotas_20260218_120000.sql

# 2. Restaurar código
tar -xzf backup_code_20260218_120000.tar.gz
```

---

## 📈 MONITORAMENTO PÓS-DEPLOY

### Primeiras 24 Horas
```bash
# Monitorar logs de erro
tail -f /home/shoesaz/logs/php_errors.log

# Verificar performance
# Ir para shoesaz.pageup.net.br e observar tempos de carregamento

# Procurar por erros específicos
grep "Router Error" /home/shoesaz/logs/php_errors.log
```

### Métricas a Observar
- ✅ HTTP 500 errors (deve ser zero)
- ✅ Tempo de carregamento do dashboard (deve ser < 1s)
- ✅ Cache hit rate (se APCu ativo)

---

## 🎁 OTIMIZAÇÕES ADICIONAIS (Opcional)

### Instalar APCu (Aumenta Performance 5x)
```bash
# SSH para o servidor
ssh user@shoesaz.pageup.net.br

# Instalar extensão
sudo pecl install apcu

# Ou (depende do provedor)
sudo apt-get install php8.0-apcu

# Habilitar no php.ini
echo "extension=apcu.so" >> /etc/php/8.0/cli/php.ini
echo "apc.enabled=1" >> /etc/php/8.0/cli/php.ini

# Reiniciar Apache/PHP
sudo systemctl restart apache2
# ou
sudo systemctl restart php-fpm
```

### Criar Índices no BD
```bash
# Conectar ao MySQL
mysql -u shoesaz -p shoesaz

# Executar SQL (do DIAGNOSTICO_ROTAS.md)
ALTER TABLE ordens_servico ADD INDEX idx_status (status);
ALTER TABLE ordens_servico ADD INDEX idx_prazo_entrega (prazo_entrega);
-- etc...
```

---

## 📞 SUPORTE

Se houver problemas:

1. **Verificar Error Log:**
   ```bash
   tail -100 /home/shoesaz/logs/php_errors.log
   ```

2. **Executar Teste:**
   ```bash
   php /home/shoesaz/public_html/tests/route_test.php
   ```

3. **Contatar provedor de hosting** com screenshot do erro e conteúdo do error log

---

## ✅ CHECKLIST PÓS-DEPLOY

- [ ] Backup feito com sucesso
- [ ] Arquivos enviados
- [ ] `tests/route_test.php` passou
- [ ] Dashboard carrega < 1s
- [ ] Sem erro 500 em 30 minutos de teste
- [ ] Error log sem erros críticos
- [ ] Todas as rotas funcionando
- [ ] Cache APCu instalado (opcional)
- [ ] Índices criados no BD (recomendado)

---

**Deploy Time:** ~15 minutos  
**Risk Level:** ⚠️ Baixo (mudanças isoladas, sem alterações em banco)  
**Rollback Time:** ~5 minutos (se necessário)

---

**Gerado em:** 2026-02-18
