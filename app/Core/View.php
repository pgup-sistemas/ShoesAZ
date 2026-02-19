<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function url(string $path): string
    {
        $basePath = (string) App::config('base_path', '/ShoesAZ');
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && $basePath[0] !== '/') {
            $basePath = '/' . $basePath;
        }

        return $basePath . $path;
    }

    public static function render(string $view, array $data = [], bool $useLayout = true, ?string $layout = null): void
    {
        if ($useLayout) {
            Auth::enforceTimeout();
        }

        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        $flashes = Flash::pull();
        $breadcrumbs = Breadcrumb::getItems();

        extract($data, EXTR_SKIP);

        if (!$useLayout) {
            require $viewFile;
            return;
        }

        if ($layout === 'print' || $layout === 'public') {
            require $viewFile;
            return;
        }

        require __DIR__ . '/../Views/layouts/header.php';
        require $viewFile;
        require __DIR__ . '/../Views/layouts/footer.php';
    }
}
