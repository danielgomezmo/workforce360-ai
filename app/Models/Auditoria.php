<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Auditoria
{
    private const SENSITIVE_ACTIONS = [
        'LOGIN_FALLIDO',
        'ELIMINAR',
        'ANULAR_INCIDENCIA',
        'REVISAR_INCIDENCIA',
    ];

    public static function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$where, $params] = self::whereClause($filters);
        $from = self::fromClause();

        $countSql = "SELECT COUNT(*) {$from} {$where}";
        $countStmt = Database::connection()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT
                    a.id_auditoria,
                    a.id_usuario,
                    a.accion,
                    a.entidad,
                    a.entidad_id,
                    a.datos_anteriores,
                    a.datos_nuevos,
                    a.motivo,
                    a.ip_origen,
                    a.user_agent,
                    a.fecha_evento,
                    u.username,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(c.nombres, ''), ' ', COALESCE(c.apellidos, ''))), ''),
                        u.username,
                        JSON_UNQUOTE(JSON_EXTRACT(a.datos_nuevos, '$.username')),
                        'Sistema'
                    ) AS usuario_nombre,
                    COALESCE(urx.roles, 'SIN ROL') AS roles
                {$from}
                {$where}
                ORDER BY a.fecha_evento DESC, a.id_auditoria DESC
                LIMIT :audit_limit OFFSET :audit_offset";

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':audit_limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':audit_offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pages = max(1, (int) ceil($total / $perPage));

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => min($page, $pages),
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public static function allForExport(array $filters, int $limit = 10000): array
    {
        $limit = max(1, min(20000, $limit));
        [$where, $params] = self::whereClause($filters);

        $sql = "SELECT
                    a.id_auditoria,
                    a.fecha_evento,
                    a.accion,
                    a.entidad,
                    a.entidad_id,
                    a.motivo,
                    a.ip_origen,
                    u.username,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(c.nombres, ''), ' ', COALESCE(c.apellidos, ''))), ''),
                        u.username,
                        JSON_UNQUOTE(JSON_EXTRACT(a.datos_nuevos, '$.username')),
                        'Sistema'
                    ) AS usuario_nombre,
                    COALESCE(urx.roles, 'SIN ROL') AS roles
                " . self::fromClause() . "
                {$where}
                ORDER BY a.fecha_evento DESC, a.id_auditoria DESC
                LIMIT :audit_export_limit";

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':audit_export_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT
                    a.*,
                    u.username,
                    u.email AS usuario_email,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(c.nombres, ''), ' ', COALESCE(c.apellidos, ''))), ''),
                        u.username,
                        JSON_UNQUOTE(JSON_EXTRACT(a.datos_nuevos, '$.username')),
                        'Sistema'
                    ) AS usuario_nombre,
                    COALESCE(urx.roles, 'SIN ROL') AS roles
                " . self::fromClause() . "
                WHERE a.id_auditoria = :audit_id
                LIMIT 1";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['audit_id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function summary(): array
    {
        $db = Database::connection();

        $total = (int) $db->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();
        $today = (int) $db->query("SELECT COUNT(*) FROM auditoria WHERE DATE(fecha_evento) = CURDATE()")
            ->fetchColumn();
        $users = (int) $db->query("SELECT COUNT(DISTINCT id_usuario) FROM auditoria WHERE id_usuario IS NOT NULL")
            ->fetchColumn();

        $placeholders = [];
        $params = [];
        foreach (self::SENSITIVE_ACTIONS as $index => $action) {
            $key = 'action_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $action;
        }

        $sensitive = 0;
        if ($placeholders) {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM auditoria WHERE accion IN (' . implode(', ', $placeholders) . ')'
            );
            $stmt->execute($params);
            $sensitive = (int) $stmt->fetchColumn();
        }

        return [
            'total' => $total,
            'hoy' => $today,
            'usuarios' => $users,
            'sensibles' => $sensitive,
        ];
    }

    public static function catalogs(): array
    {
        $db = Database::connection();

        $actions = $db->query(
            "SELECT DISTINCT accion FROM auditoria WHERE accion <> '' ORDER BY accion ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        $entities = $db->query(
            "SELECT DISTINCT entidad FROM auditoria WHERE entidad <> '' ORDER BY entidad ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        $users = $db->query(
            "SELECT
                u.id_usuario,
                u.username,
                COALESCE(
                    NULLIF(TRIM(CONCAT(COALESCE(c.nombres, ''), ' ', COALESCE(c.apellidos, ''))), ''),
                    u.username
                ) AS nombre
             FROM usuarios u
             LEFT JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
             WHERE EXISTS (
                SELECT 1 FROM auditoria a WHERE a.id_usuario = u.id_usuario
             )
             ORDER BY nombre ASC, u.username ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'acciones' => $actions,
            'entidades' => $entities,
            'usuarios' => $users,
        ];
    }

    public static function decodeJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function fromClause(): string
    {
        return "FROM auditoria a
                LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
                LEFT JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
                LEFT JOIN (
                    SELECT
                        ur.id_usuario,
                        GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol
                    GROUP BY ur.id_usuario
                ) urx ON urx.id_usuario = a.id_usuario";
    }

    private static function whereClause(array $filters): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filters['desde'])) {
            $conditions[] = 'DATE(a.fecha_evento) >= :audit_desde';
            $params['audit_desde'] = $filters['desde'];
        }

        if (!empty($filters['hasta'])) {
            $conditions[] = 'DATE(a.fecha_evento) <= :audit_hasta';
            $params['audit_hasta'] = $filters['hasta'];
        }

        if (!empty($filters['accion'])) {
            $conditions[] = 'a.accion = :audit_accion';
            $params['audit_accion'] = $filters['accion'];
        }

        if (!empty($filters['entidad'])) {
            $conditions[] = 'a.entidad = :audit_entidad';
            $params['audit_entidad'] = $filters['entidad'];
        }

        if (!empty($filters['usuario'])) {
            $conditions[] = 'a.id_usuario = :audit_usuario';
            $params['audit_usuario'] = (int) $filters['usuario'];
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $conditions[] = '(
                u.username LIKE :audit_q_username
                OR CONCAT(COALESCE(c.nombres, \'\'), \' \', COALESCE(c.apellidos, \'\')) LIKE :audit_q_nombre
                OR a.accion LIKE :audit_q_accion
                OR a.entidad LIKE :audit_q_entidad
                OR COALESCE(a.motivo, \'\') LIKE :audit_q_motivo
                OR COALESCE(a.ip_origen, \'\') LIKE :audit_q_ip
                OR CAST(COALESCE(a.entidad_id, 0) AS CHAR) LIKE :audit_q_registro
            )';
            $params['audit_q_username'] = $like;
            $params['audit_q_nombre'] = $like;
            $params['audit_q_accion'] = $like;
            $params['audit_q_entidad'] = $like;
            $params['audit_q_motivo'] = $like;
            $params['audit_q_ip'] = $like;
            $params['audit_q_registro'] = $like;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}
