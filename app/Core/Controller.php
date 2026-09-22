<?php
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!file_exists($file)) {
            http_response_code(500);
            echo 'Vista no encontrada.';
            return;
        }

        require $file;
    }

    protected function redirect(string $route, array $params = []): never
    {
        header('Location: ' . route_url($route, $params));
        exit;
    }

    protected function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
