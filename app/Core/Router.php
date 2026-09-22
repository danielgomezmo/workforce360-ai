<?php
namespace App\Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $route, array|callable $handler): void
    {
        $this->routes['GET'][$route] = $handler;
    }

    public function post(string $route, array|callable $handler): void
    {
        $this->routes['POST'][$route] = $handler;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $route = trim((string) ($_GET['route'] ?? 'login'), '/');
        $handler = $this->routes[$method][$route] ?? null;

        if ($handler === null) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();
        $controller->{$action}();
    }
}
