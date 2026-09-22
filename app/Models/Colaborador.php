<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use App\Models\User;

class Colaborador
{
    public static function all(string $search = '', string $estado = ''): array
    {
        $sql = "SELECT
                    c.*,
                    a.nombre AS area_nombre,
                    e.nombre AS equipo_nombre,
                    CONCAT(s.nombres, ' ', s.apellidos) AS supervisor_nombre,
                    h.nombre AS horario_nombre,
                    h.hora_inicio AS horario_inicio,
                    h.hora_fin AS horario_fin,
                    u.username AS acceso_username,
                    u.activo AS acceso_activo
                FROM colaboradores c
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN equipos e ON e.id_equipo = c.id_equipo
                LEFT JOIN colaboradores s ON s.id_colaborador = c.id_supervisor
                LEFT JOIN asignacion_horarios ah ON ah.id_asignacion = (
                    SELECT ah2.id_asignacion
                    FROM asignacion_horarios ah2
                    WHERE ah2.id_colaborador = c.id_colaborador
                    ORDER BY ah2.es_temporal ASC, ah2.fecha_inicio DESC, ah2.id_asignacion DESC
                    LIMIT 1
                )
                LEFT JOIN horarios h ON h.id_horario = ah.id_horario
                LEFT JOIN usuarios u ON u.id_colaborador = c.id_colaborador
                WHERE 1 = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                c.codigo_trabajador LIKE :q_codigo OR c.numero_documento LIKE :q_documento OR
                c.nombres LIKE :q_nombres OR c.apellidos LIKE :q_apellidos OR
                CONCAT(c.nombres, ' ', c.apellidos) LIKE :q_nombre_completo
            )";
            $term = '%' . $search . '%';
            $params['q_codigo'] = $term;
            $params['q_documento'] = $term;
            $params['q_nombres'] = $term;
            $params['q_apellidos'] = $term;
            $params['q_nombre_completo'] = $term;
        }

        if ($estado !== '') {
            $sql .= ' AND c.estado = :estado';
            $params['estado'] = $estado;
        }

        $sql .= ' ORDER BY c.apellidos, c.nombres';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, u.id_usuario AS acceso_id_usuario, u.username AS acceso_username, u.email AS acceso_email, u.activo AS acceso_activo,
                (SELECT ah.id_horario
                 FROM asignacion_horarios ah
                 WHERE ah.id_colaborador = c.id_colaborador
                 ORDER BY ah.es_temporal ASC, ah.fecha_inicio DESC, ah.id_asignacion DESC
                 LIMIT 1) AS id_horario
             FROM colaboradores c
             LEFT JOIN usuarios u ON u.id_colaborador = c.id_colaborador
             WHERE c.id_colaborador = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function supervisors(?int $excludeId = null): array
    {
        $sql = "SELECT id_colaborador, codigo_trabajador, nombres, apellidos
                FROM colaboradores
                WHERE estado IN ('HABILITADO','ACTIVO')";
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND id_colaborador <> :id';
            $params['id'] = $excludeId;
        }
        $sql .= ' ORDER BY apellidos, nombres';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function uniqueExists(string $field, string $value, ?int $excludeId = null): bool
    {
        if (!in_array($field, ['codigo_trabajador', 'numero_documento', 'email_corporativo'], true)) {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM colaboradores WHERE {$field} = :value";
        $params = ['value' => $value];
        if ($excludeId !== null) {
            $sql .= ' AND id_colaborador <> :id';
            $params['id'] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data, int $horarioId, array $access): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO colaboradores
                (codigo_trabajador, tipo_documento, numero_documento, nombres, apellidos,
                 id_area, id_equipo, id_supervisor, cargo, sede, fecha_ingreso, fecha_cese,
                 jornada_horas, modalidad, estado, email_corporativo, telefono)
                VALUES
                (:codigo_trabajador, :tipo_documento, :numero_documento, :nombres, :apellidos,
                 :id_area, :id_equipo, :id_supervisor, :cargo, :sede, :fecha_ingreso, :fecha_cese,
                 :jornada_horas, :modalidad, :estado, :email_corporativo, :telefono)'
            );
            $stmt->execute($data);
            $id = (int) $db->lastInsertId();

            $schedule = $db->prepare(
                'INSERT INTO asignacion_horarios
                 (id_colaborador, id_horario, fecha_inicio, es_temporal, motivo)
                 VALUES (:colaborador, :horario, :fecha_inicio, 0, :motivo)'
            );
            $schedule->execute([
                'colaborador' => $id,
                'horario' => $horarioId,
                'fecha_inicio' => $data['fecha_ingreso'],
                'motivo' => 'Horario base registrado en Fase 2',
            ]);

            User::saveCollaboratorAccess($id, $access);

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function update(int $id, array $data, int $horarioId, array $access): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $data['id'] = $id;
            $stmt = $db->prepare(
                'UPDATE colaboradores SET
                 codigo_trabajador = :codigo_trabajador, tipo_documento = :tipo_documento,
                 numero_documento = :numero_documento, nombres = :nombres, apellidos = :apellidos,
                 id_area = :id_area, id_equipo = :id_equipo, id_supervisor = :id_supervisor,
                 cargo = :cargo, sede = :sede, fecha_ingreso = :fecha_ingreso, fecha_cese = :fecha_cese,
                 jornada_horas = :jornada_horas, modalidad = :modalidad, estado = :estado,
                 email_corporativo = :email_corporativo, telefono = :telefono
                 WHERE id_colaborador = :id'
            );
            $stmt->execute($data);

            $baseStmt = $db->prepare(
                'SELECT id_asignacion FROM asignacion_horarios
                 WHERE id_colaborador = :id AND es_temporal = 0
                 ORDER BY id_asignacion ASC LIMIT 1'
            );
            $baseStmt->execute(['id' => $id]);
            $assignmentId = $baseStmt->fetchColumn();

            if ($assignmentId) {
                $schedule = $db->prepare(
                    'UPDATE asignacion_horarios
                     SET id_horario = :horario, fecha_inicio = :fecha_inicio, fecha_fin = NULL
                     WHERE id_asignacion = :id_asignacion'
                );
                $schedule->execute([
                    'horario' => $horarioId,
                    'fecha_inicio' => $data['fecha_ingreso'],
                    'id_asignacion' => $assignmentId,
                ]);
            } else {
                $schedule = $db->prepare(
                    'INSERT INTO asignacion_horarios
                     (id_colaborador, id_horario, fecha_inicio, es_temporal, motivo)
                     VALUES (:colaborador, :horario, :fecha_inicio, 0, :motivo)'
                );
                $schedule->execute([
                    'colaborador' => $id,
                    'horario' => $horarioId,
                    'fecha_inicio' => $data['fecha_ingreso'],
                    'motivo' => 'Horario base registrado en Fase 2',
                ]);
            }

            User::saveCollaboratorAccess($id, $access);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function usageCount(int $id): int
    {
        $db = Database::connection();
        $checks = [
            'SELECT COUNT(*) FROM colaboradores WHERE id_supervisor = :id',
            'SELECT COUNT(*) FROM marcaciones WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM incidencias WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM vacaciones WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM licencias WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM descansos_medicos WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM permisos WHERE id_colaborador = :id',
            'SELECT COUNT(*) FROM ceses WHERE id_colaborador = :id',
        ];
        $total = 0;
        foreach ($checks as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $total += (int) $stmt->fetchColumn();
        }
        return $total;
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('DELETE FROM usuarios WHERE id_colaborador = :id');
            $stmt->execute(['id' => $id]);
            $stmt = $db->prepare('DELETE FROM asignacion_horarios WHERE id_colaborador = :id');
            $stmt->execute(['id' => $id]);
            $stmt = $db->prepare('DELETE FROM colaboradores WHERE id_colaborador = :id');
            $stmt->execute(['id' => $id]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
