# 🎨 Guia de Implementação: Status Visual para OS

## Objetivo
Implementar badges com cores e ícones para deixar os status de OS visualmente intuitivos.

## Passo 1: Criar Helper Function

Adicionar em `app/Core/View.php` ou criar novo arquivo `app/Helpers/StatusHelper.php`:

```php
<?php

namespace App\Helpers;

final class StatusHelper
{
    private static array $statusConfig = [
        'Recebido' => [
            'color' => 'info',
            'icon' => 'ℹ️',
            'label' => 'Recebido',
            'bg_class' => 'bg-info',
            'badge_class' => 'bg-info',
        ],
        'Em reparo' => [
            'color' => 'warning',
            'icon' => '⚙️',
            'label' => 'Em Reparo',
            'bg_class' => 'bg-warning text-dark',
            'badge_class' => 'bg-warning text-dark',
        ],
        'Aguardando retirada' => [
            'color' => 'success',
            'icon' => '📦',
            'label' => 'Aguardando Retirada',
            'bg_class' => 'bg-success',
            'badge_class' => 'bg-success',
        ],
        'Entregue' => [
            'color' => 'dark',
            'icon' => '✅',
            'label' => 'Entregue',
            'bg_class' => 'bg-dark',
            'badge_class' => 'bg-dark',
        ],
        'Cancelado' => [
            'color' => 'secondary',
            'icon' => '✗',
            'label' => 'Cancelado',
            'bg_class' => 'bg-secondary',
            'badge_class' => 'bg-secondary',
        ],
        'Atrasado' => [
            'color' => 'danger',
            'icon' => '⚠️',
            'label' => 'Atrasado',
            'bg_class' => 'bg-danger',
            'badge_class' => 'bg-danger',
        ],
    ];

    /**
     * Retorna configuração de status
     */
    public static function getConfig(string $status): array
    {
        return self::$statusConfig[$status] ?? [
            'color' => 'secondary',
            'icon' => '?',
            'label' => $status,
            'bg_class' => 'bg-secondary',
            'badge_class' => 'bg-secondary',
        ];
    }

    /**
     * Renderiza badge de status
     */
    public static function badge(string $status): string
    {
        $config = self::getConfig($status);
        return sprintf(
            '<span class="badge %s" title="%s" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;">%s %s</span>',
            htmlspecialchars($config['badge_class'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8'),
            $config['icon'],
            htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Renderiza card com status
     */
    public static function statusCard(string $status, string $title): string
    {
        $config = self::getConfig($status);
        return sprintf(
            '<div class="card border-left border-4 border-%s mb-3">
                <div class="card-body pb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="card-title mb-0">%s</h6>
                        <span class="badge %s">%s %s</span>
                    </div>
                </div>
            </div>',
            htmlspecialchars($config['color'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($config['badge_class'], ENT_QUOTES, 'UTF-8'),
            $config['icon'],
            htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Retorna ícone do status
     */
    public static function icon(string $status): string
    {
        return self::getConfig($status)['icon'];
    }

    /**
     * Determina se status está "crítico" (precisa atenção)
     */
    public static function isCritical(string $status): bool
    {
        return in_array($status, ['Atrasado', 'Cancelado'], true);
    }
}
```

## Passo 2: Usar em Views

### Em `/app/Views/os/index.php`:

**Antes:**
```php
<td><?= htmlspecialchars($ordem['status'], ENT_QUOTES, 'UTF-8') ?></td>
```

**Depois:**
```php
<td>
    <?= \App\Helpers\StatusHelper::badge($ordem['status']) ?>
</td>
```

### Em `/app/Views/os/form.php` (detail view):

**Antes:**
```html
<div class="form-group">
    <label>Status</label>
    <input type="text" class="form-control" value="<?= $os['status'] ?>" disabled>
</div>
```

**Depois:**
```html
<div class="form-group">
    <label>Status Atual</label>
    <div class="alert alert-light d-flex justify-content-between align-items-center p-3">
        <span>
            <strong><?= \App\Helpers\StatusHelper::icon($os['status']) ?></strong>
            <?= htmlspecialchars($os['status'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <small class="text-muted">Desde: <?= date('d/m/Y H:i', strtotime($os['updated_at'])) ?></small>
    </div>
</div>
```

## Passo 3: Adicionar CSS Customizado

Adicionar em `public/assets/css/style.css`:

```css
/* === Status Badges === */
.badge {
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 0.5rem 0.75rem !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.badge.bg-success {
    background-color: #28a745 !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-dark {
    background-color: #343a40 !important;
}

/* === Cards com Status === */
.card.border-left {
    border-left-width: 4px !important;
    border-left-style: solid !important;
}

.card.border-left:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

/* === Status Timeline === */
.status-timeline {
    display: flex;
    justify-content: space-between;
    margin: 2rem 0;
    position: relative;
}

.status-timeline::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e9ecef;
    z-index: 0;
}

.status-timeline .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 1;
}

.status-timeline .step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.status-timeline .step.completed .step-circle {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
}

.status-timeline .step.active .step-circle {
    background: #008bcd;
    border-color: #008bcd;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(0, 139, 205, 0.2);
}

.status-timeline .step-label {
    font-size: 0.85rem;
    text-align: center;
    max-width: 80px;
}

.status-timeline .step.completed .step-label {
    color: #28a745;
    font-weight: 600;
}

.status-timeline .step.active .step-label {
    color: #008bcd;
    font-weight: 600;
}
```

## Passo 4: Criar Timeline Visual (Opcional)

Criar view partial `app/Views/components/status_timeline.php`:

```php
<?php
/**
 * @var string $currentStatus
 * @var array $statusFlow Define a ordem dos status possíveis
 */

$statusFlow = $statusFlow ?? [
    'Recebido',
    'Em reparo',
    'Aguardando retirada',
    'Entregue',
];

$currentIndex = array_search($currentStatus, $statusFlow, true);
?>

<div class="status-timeline">
    <?php foreach ($statusFlow as $index => $status): ?>
        <div class="step <?= $index < $currentIndex ? 'completed' : ($index === $currentIndex ? 'active' : '') ?>">
            <div class="step-circle">
                <?= \App\Helpers\StatusHelper::icon($status) ?>
            </div>
            <div class="step-label">
                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

Usar em `/app/Views/os/form.php`:

```php
<div class="mb-4">
    <h6>Progresso da Execução</h6>
    <?php require __DIR__ . '/../components/status_timeline.php'; ?>
</div>
```

## Passo 5: Adicionar Indicador de "Crítico" no Menu

Modificar `app/Views/layouts/sidebar.php`:

```php
<?php if (\App\Core\Auth::hasRole(['Administrador', 'Gerente', 'Atendente'])): ?>
    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
       href="<?= \App\Core\View::url('/os') ?>">
        <span><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</span>
        <!-- Badge com contador de OS atrasadas (opcional, requer query no controller) -->
        <?php if (isset($osAtrasadasCount) && $osAtrasadasCount > 0): ?>
            <span class="badge bg-danger"><?= $osAtrasadasCount ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>
```

## Resultado Visual

Antes vs Depois:

**ANTES:**
```
| Número | Cliente | Status       |
|--------|---------|--------------|
| #1001  | João    | Em reparo    |
| #1002  | Maria   | Entregue     |
| #1003  | Pedro   | Atrasado     |
```

**DEPOIS:**
```
| Número | Cliente | Status                    |
|--------|---------|---------------------------|
| #1001  | João    | ⚙️ Em Reparo              |
| #1002  | Maria   | ✅ Entregue               |
| #1003  | Pedro   | ⚠️ Atrasado (RED BADGE)  |
```

## Benefícios

✅ **Visualização Instantânea:** User vê status à primeira vista
✅ **Reduz Erros:** Cores evitam confusão
✅ **Produtividade:** Status críticos (Atrasado) ficam vermelhos e óbvios
✅ **Profissional:** Aparência mais polida
✅ **Acessível:** Combina cor + ícone + texto

