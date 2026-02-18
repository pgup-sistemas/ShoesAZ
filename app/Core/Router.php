<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        try {
            $method = strtoupper($method);
            $path = parse_url($uri, PHP_URL_PATH) ?? '/';

            // Remove base path (configurable) if present
            $basePath = (string) App::config('base_path', '');
            $basePath = rtrim($basePath, '/');
            if ($basePath !== '' && $basePath[0] !== '/') {
                $basePath = '/' . $basePath;
            }

            if ($basePath !== '' && str_starts_with($path, $basePath)) {
                $path = substr($path, strlen($basePath));
            }

            $path = $this->normalize($path);

            // Verificar autenticação e session timeout
            Middleware::checkAuth($path);
            Middleware::checkSessionTimeout();

            $handler = $this->routes[$method][$path] ?? null;
            if ($handler === null) {
                http_response_code(404);
                echo '404';
                return;
            }

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->$action();
                return;
            }

            $handler();
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('Router Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo 'Erro 500: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
