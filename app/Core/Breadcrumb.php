<?php

declare(strict_types=1);

namespace App\Core;

final class Breadcrumb
{
    private static array $items = [];

    public static function add(string $label, ?string $url = null): void
    {
        self::$items[] = [
            'label' => $label,
            'url' => $url,
        ];
    }

    public static function reset(): void
    {
        self::$items = [];
    }

    public static function getItems(): array
    {
        return self::$items;
    }

    public static function render(): string
    {
        if (empty(self::$items)) {
            return '';
        }

        $html = '<nav aria-label="breadcrumb" class="mb-3">';
        $html .= '<ol class="breadcrumb">';

        foreach (self::$items as $index => $item) {
            $isLast = ($index === count(self::$items) - 1);
            
            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">';
                $html .= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8');
                $html .= '</li>';
            } else {
                $html .= '<li class="breadcrumb-item">';
                if ($item['url']) {
                    $html .= '<a href="' . htmlspecialchars((string) $item['url'], ENT_QUOTES, 'UTF-8') . '">';
                    $html .= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8');
                    $html .= '</a>';
                } else {
                    $html .= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8');
                }
                $html .= '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }
}
