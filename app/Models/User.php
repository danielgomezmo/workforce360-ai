<?php
namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByUsername(string $username): ?array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.username,
                    u.password_hash,
                    u.activo,
                    u.id_colaborador,
                    COALESCE(CONCAT(c.nombres, ' ', c.apellidos), u.username) AS nombre_mostrar,
                    GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
                FROM usuarios u
                LEFT JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
                LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                LEFT JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
                WHERE u.username = :username
                GROUP BY u.id_usuario
                LIMIT 1";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByCollaborator(int $collaboratorId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT u.id_usuario, u.id_colaborador, u.username, u.email, u.activo, u.ultimo_acceso,
                    GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
             FROM usuarios u
             LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
             LEFT JOIN roles r ON r.id_rol = ur.id_rol
             WHERE u.id_colaborador = :id
             GROUP BY u.id_usuario
             LIMIT 1"
        );
        $stmt->execute(['id' => $collaboratorId]);
        return $stmt->fetch() ?: null;
    }

    public static function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE username = :username';
        $params = ['username' => $username];
        if ($excludeUserId !== null) {
            $sql .= ' AND id_usuario <> :id';
            $params['id'] = $excludeUserId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = ['email' => $email];
        if ($excludeUserId !== null) {
            $sql .= ' AND id_usuario <> :id';
            $params['id'] = $excludeUserId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea o actualiza el acceso de portal de un colaborador y garantiza el rol COLABORADOR.
     * Debe invocarse dentro de la misma transacción que crea/actualiza al colaborador.
     */
    public static function saveCollaboratorAccess(int $collaboratorId, array $access): int
    {
        $db = Database::connection();
        $existing = self::findByCollaborator($collaboratorId);
        $username = strtolower(trim((string) ($access['username'] ?? '')));
        $email = trim((string) ($access['email'] ?? ''));
        $email = $email !== '' ? strtolower($email) : null;
        $active = !empty($access['activo']) ? 1 : 0;
        $password = (string) ($access['password'] ?? '');

        if ($existing) {
            $sql = 'UPDATE usuarios SET username = :username, email = :email, activo = :activo';
            $params = [
                'username' => $username,
                'email' => $email,
                'activo' => $active,
                'id' => (int) $existing['id_usuario'],
            ];
            if ($password !== '') {
                $sql .= ', password_hash = :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id_usuario = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $userId = (int) $existing['id_usuario'];
        } else {
            $stmt = $db->prepare(
                'INSERT INTO usuarios (id_colaborador, username, email, password_hash, activo)
                 VALUES (:colaborador, :username, :email, :password_hash, :activo)'
            );
            $stmt->execute([
                'colaborador' => $collaboratorId,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'activo' => $active,
            ]);
            $userId = (int) $db->lastInsertId();
        }

        $roleStmt = $db->prepare("SELECT id_rol FROM roles WHERE nombre = 'COLABORADOR' AND activo = 1 LIMIT 1");
        $roleStmt->execute();
        $roleId = (int) $roleStmt->fetchColumn();
        if ($roleId <= 0) {
            throw new \RuntimeException('No existe el rol COLABORADOR activo.');
        }

        $link = $db->prepare('INSERT IGNORE INTO usuario_rol (id_usuario, id_rol) VALUES (:usuario, :rol)');
        $link->execute(['usuario' => $userId, 'rol' => $roleId]);
        return $userId;
    }

    public static function touchLastLogin(int $idUsuario): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id'
        );
        $stmt->execute(['id' => $idUsuario]);
    }
}
