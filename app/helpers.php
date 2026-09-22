<?php

function config(string $key, mixed $default = null): mixed
{
    static $configs = [];
    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($configs[$file])) {
        $path = BASE_PATH . '/config/' . $file . '.php';
        $configs[$file] = file_exists($path) ? require $path : [];
    }

    return $item === null ? $configs[$file] : ($configs[$file][$item] ?? $default);
}

function base_url(string $path = ''): string
{
    return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
}

function route_url(string $route, array $params = []): string
{
    $query = array_merge(['route' => $route], $params);
    return base_url('public/index.php?' . http_build_query($query));
}

function asset(string $path): string
{
    return base_url('public/assets/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}
