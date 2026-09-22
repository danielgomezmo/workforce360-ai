<?php
namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']['id_usuario']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id_usuario'])
            ? (int) $_SESSION['user']['id_usuario']
            : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id_usuario' => (int) $user['id_usuario'],
            'username' => $user['username'],
            'nombre_mostrar' => $user['nombre_mostrar'] ?: $user['username'],
            'roles' => $user['roles'] ?? '',
            'id_colaborador' => isset($user['id_colaborador']) ? (int) $user['id_colaborador'] : null,
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function collaboratorId(): ?int
    {
        $id = self::user()['id_colaborador'] ?? null;
        return $id ? (int) $id : null;
    }

    public static function roles(): array
    {
        $roles = (string) (self::user()['roles'] ?? '');
        if ($roles === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $roles))));
    }

    public static function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), array_map('strtoupper', self::roles()), true);
    }

    public static function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (self::hasRole((string) $role)) {
                return true;
            }
        }
        return false;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Inicia sesión para continuar.');
            header('Location: ' . route_url('login'));
            exit;
        }
    }

    public static function requireAnyRole(array $roles): void
    {
        self::requireLogin();
        if (!self::hasAnyRole($roles)) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}
