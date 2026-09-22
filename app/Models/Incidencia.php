<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class Incidencia
{
    private const REQUEST_CODES = [
        'VACACIONES',
        'LICENCIA_CON_GOCE',
        'LICENCIA_SIN_GOCE',
        'DESCANSO_MEDICO',
        'PERMISO',
        'FALTA_JUSTIFICADA',
    ];

    private const FULL_DAY_BLOCK_CODES = [
        'VACACIONES',
        'LICENCIA_CON_GOCE',
        'LICENCIA_SIN_GOCE',
        'DESCANSO_MEDICO',
        'FALTA_JUSTIFICADA',
        'DESCANSO_PROGRAMADO',
        'COMPENSACION',
        'SUSPENSION',
    ];

    public static function requestTypes(): array
    {
        $placeholders = implode(',', array_fill(0, count(self::REQUEST_CODES), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id_tipo_incidencia, codigo, nombre, es_ausencia, es_justificada, requiere_aprobacion
             FROM tipos_incidencia
             WHERE activo = 1 AND codigo IN ({$placeholders})
             ORDER BY FIELD(codigo, 'VACACIONES','LICENCIA_CON_GOCE','LICENCIA_SIN_GOCE','DESCANSO_MEDICO','PERMISO','FALTA_JUSTIFICADA')"
        );
        $stmt->execute(self::REQUEST_CODES);
        return $stmt->fetchAll();
    }

    public static function typeByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM tipos_incidencia WHERE codigo = :codigo AND activo = 1 LIMIT 1'
        );
        $stmt->execute(['codigo' => $code]);
        return $stmt->fetch() ?: null;
    }

    public static function collaboratorsForSelector(?int $supervisorId = null): array
    {
        $sql = "SELECT c.id_colaborador, c.codigo_trabajador, c.nombres, c.apellidos, c.cargo,
                       a.nombre AS area_nombre
                FROM colaboradores c
                LEFT JOIN areas a ON a.id_area = c.id_area
                WHERE c.estado <> 'CESADO'";
        $params = [];
        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }
        $sql .= ' ORDER BY c.apellidos, c.nombres';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO incidencias
             (id_colaborador, id_tipo_incidencia, fecha_inicio, fecha_fin, motivo, comentario, documento_path, estado, registrado_por)
             VALUES
             (:colaborador, :tipo, :inicio, :fin, :motivo, :comentario, :documento, 'PENDIENTE', :usuario)"
        );
        $stmt->execute([
            'colaborador' => $data['id_colaborador'],
            'tipo' => $data['id_tipo_incidencia'],
            'inicio' => $data['fecha_inicio'],
            'fin' => $data['fecha_fin'],
            'motivo' => $data['motivo'],
            'comentario' => $data['comentario'],
            'documento' => $data['documento_path'],
            'usuario' => $data['registrado_por'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT i.*, t.codigo AS tipo_codigo, t.nombre AS tipo_nombre, t.es_ausencia, t.es_justificada, t.requiere_aprobacion,
                    c.codigo_trabajador, c.nombres, c.apellidos, c.id_supervisor, c.cargo,
                    a.nombre AS area_nombre,
                    ur.username AS registrado_username,
                    uv.username AS revisado_username
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
             LEFT JOIN areas a ON a.id_area = c.id_area
             LEFT JOIN usuarios ur ON ur.id_usuario = i.registrado_por
             LEFT JOIN usuarios uv ON uv.id_usuario = i.revisado_por
             WHERE i.id_incidencia = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function own(int $collaboratorId, string $status = '', int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $sql = "SELECT i.*, t.codigo AS tipo_codigo, t.nombre AS tipo_nombre,
                       uv.username AS revisado_username
                FROM incidencias i
                INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
                LEFT JOIN usuarios uv ON uv.id_usuario = i.revisado_por
                WHERE i.id_colaborador = :colaborador
                  AND t.codigo IN ('VACACIONES','LICENCIA_CON_GOCE','LICENCIA_SIN_GOCE','DESCANSO_MEDICO','PERMISO','FALTA_JUSTIFICADA')";
        $params = ['colaborador' => $collaboratorId];
        if ($status !== '') {
            $sql .= ' AND i.estado = :estado';
            $params['estado'] = $status;
        }
        $sql .= " ORDER BY i.creado_en DESC, i.id_incidencia DESC LIMIT {$limit}";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function listForManagement(string $status = '', string $query = '', ?int $supervisorId = null): array
    {
        $sql = "SELECT i.*, t.codigo AS tipo_codigo, t.nombre AS tipo_nombre, t.es_ausencia, t.requiere_aprobacion,
                       c.codigo_trabajador, c.nombres, c.apellidos, c.cargo,
                       a.nombre AS area_nombre, ur.username AS registrado_username,
                       uv.username AS revisado_username
                FROM incidencias i
                INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
                INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN usuarios ur ON ur.id_usuario = i.registrado_por
                LEFT JOIN usuarios uv ON uv.id_usuario = i.revisado_por
                WHERE 1 = 1";
        $params = [];
        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }
        if ($status !== '') {
            $sql .= ' AND i.estado = :estado';
            $params['estado'] = $status;
        }
        if ($query !== '') {
            $term = '%' . $query . '%';
            $sql .= " AND (c.codigo_trabajador LIKE :q_codigo
                       OR c.numero_documento LIKE :q_documento
                       OR c.nombres LIKE :q_nombres
                       OR c.apellidos LIKE :q_apellidos
                       OR t.nombre LIKE :q_tipo
                       OR a.nombre LIKE :q_area)";
            $params['q_codigo'] = $term;
            $params['q_documento'] = $term;
            $params['q_nombres'] = $term;
            $params['q_apellidos'] = $term;
            $params['q_tipo'] = $term;
            $params['q_area'] = $term;
        }
        $sql .= ' ORDER BY CASE i.estado WHEN \'PENDIENTE\' THEN 0 WHEN \'REGISTRADA\' THEN 1 ELSE 2 END, i.fecha_inicio DESC, i.id_incidencia DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function summaryForManagement(?int $supervisorId = null): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN i.estado = 'PENDIENTE' OR (i.estado = 'REGISTRADA' AND t.requiere_aprobacion = 1) THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN i.estado = 'APROBADA' THEN 1 ELSE 0 END) AS aprobadas,
                    SUM(CASE WHEN i.estado = 'RECHAZADA' THEN 1 ELSE 0 END) AS rechazadas
                FROM incidencias i
                INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
                INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
                WHERE 1=1";
        $params = [];
        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map(static fn($v) => (int)($v ?? 0), $row);
    }

    public static function pendingCountForCollaborator(int $collaboratorId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*)
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
               AND i.estado IN ('PENDIENTE','REGISTRADA')
               AND t.codigo IN ('VACACIONES','LICENCIA_CON_GOCE','LICENCIA_SIN_GOCE','DESCANSO_MEDICO','PERMISO','FALTA_JUSTIFICADA')"
        );
        $stmt->execute(['colaborador' => $collaboratorId]);
        return (int) $stmt->fetchColumn();
    }

    public static function review(int $id, string $state, int $reviewerId, ?string $observation = null): array
    {
        if (!in_array($state, ['APROBADA', 'RECHAZADA'], true)) {
            throw new RuntimeException('Estado de revisión inválido.');
        }
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $incident = self::find($id);
            if (!$incident) {
                throw new RuntimeException('La incidencia no existe.');
            }
            if (!in_array($incident['estado'], ['PENDIENTE', 'REGISTRADA'], true)) {
                throw new RuntimeException('La incidencia ya fue revisada.');
            }
            if ($incident['estado'] === 'REGISTRADA' && (int)($incident['requiere_aprobacion'] ?? 0) !== 1) {
                throw new RuntimeException('Esta incidencia automática no requiere aprobación manual.');
            }

            $stmt = $db->prepare(
                'UPDATE incidencias
                 SET estado = :estado, revisado_por = :revisor, revisado_en = NOW(), observacion_revision = :observacion
                 WHERE id_incidencia = :id'
            );
            $stmt->execute([
                'estado' => $state,
                'revisor' => $reviewerId,
                'observacion' => $observation,
                'id' => $id,
            ]);

            $updated = self::find($id);
            if ($state === 'APROBADA' && $updated) {
                self::syncApprovedRecord($updated);
            }
            $db->commit();
            return $updated ?: $incident;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function annul(int $id, int $reviewerId, ?string $observation = null): array
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $incident = self::find($id);
            if (!$incident) {
                throw new RuntimeException('La incidencia no existe.');
            }
            if ($incident['estado'] === 'ANULADA') {
                throw new RuntimeException('La incidencia ya se encuentra anulada.');
            }
            $stmt = $db->prepare(
                "UPDATE incidencias
                 SET estado = 'ANULADA', revisado_por = :revisor, revisado_en = NOW(), observacion_revision = :observacion
                 WHERE id_incidencia = :id"
            );
            $stmt->execute(['revisor' => $reviewerId, 'observacion' => $observation, 'id' => $id]);
            self::annulSpecializedRecord($id);
            $updated = self::find($id) ?: $incident;
            $db->commit();
            return $updated;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function blockingForDate(int $collaboratorId, string $date, ?array $schedule = null): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT i.*, t.codigo AS tipo_codigo, t.nombre AS tipo_nombre
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
               AND i.estado = 'APROBADA'
               AND DATE(i.fecha_inicio) <= :fecha_inicio
               AND DATE(COALESCE(i.fecha_fin, i.fecha_inicio)) >= :fecha_fin
             ORDER BY i.fecha_inicio DESC"
        );
        $stmt->execute([
            'colaborador' => $collaboratorId,
            'fecha_inicio' => $date,
            'fecha_fin' => $date,
        ]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if (in_array($row['tipo_codigo'], self::FULL_DAY_BLOCK_CODES, true)) {
                return $row;
            }
            if ($row['tipo_codigo'] === 'PERMISO' && $schedule) {
                $start = new DateTimeImmutable((string)$row['fecha_inicio']);
                $end = new DateTimeImmutable((string)($row['fecha_fin'] ?: $row['fecha_inicio']));
                $scheduledStart = new DateTimeImmutable($date . ' ' . $schedule['hora_inicio']);
                $endDate = ((int)($schedule['cruza_medianoche'] ?? 0) === 1 || $schedule['hora_fin'] <= $schedule['hora_inicio'])
                    ? (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d')
                    : $date;
                $scheduledEnd = new DateTimeImmutable($endDate . ' ' . $schedule['hora_fin']);
                if ($start <= $scheduledStart && $end >= $scheduledEnd) {
                    return $row;
                }
            }
        }
        return null;
    }

    private static function syncApprovedRecord(array $incident): void
    {
        $db = Database::connection();
        $code = (string)$incident['tipo_codigo'];
        $startDate = substr((string)$incident['fecha_inicio'], 0, 10);
        $endDate = substr((string)($incident['fecha_fin'] ?: $incident['fecha_inicio']), 0, 10);
        $userId = $incident['registrado_por'] ? (int)$incident['registrado_por'] : null;

        if ($code === 'VACACIONES') {
            $stmt = $db->prepare(
                "INSERT INTO vacaciones (id_incidencia, id_colaborador, fecha_inicio, fecha_fin, estado, observacion, registrado_por)
                 VALUES (:incidencia,:colaborador,:inicio,:fin,'APROBADA',:obs,:usuario)
                 ON DUPLICATE KEY UPDATE fecha_inicio=VALUES(fecha_inicio), fecha_fin=VALUES(fecha_fin), estado='APROBADA', observacion=VALUES(observacion)"
            );
            $stmt->execute(['incidencia'=>$incident['id_incidencia'],'colaborador'=>$incident['id_colaborador'],'inicio'=>$startDate,'fin'=>$endDate,'obs'=>$incident['motivo'],'usuario'=>$userId]);
            return;
        }

        if (in_array($code, ['LICENCIA_CON_GOCE','LICENCIA_SIN_GOCE'], true)) {
            $stmt = $db->prepare(
                "INSERT INTO licencias (id_incidencia, id_colaborador, tipo, fecha_inicio, fecha_fin, motivo, estado, registrado_por)
                 VALUES (:incidencia,:colaborador,:tipo,:inicio,:fin,:motivo,'APROBADA',:usuario)
                 ON DUPLICATE KEY UPDATE tipo=VALUES(tipo), fecha_inicio=VALUES(fecha_inicio), fecha_fin=VALUES(fecha_fin), motivo=VALUES(motivo), estado='APROBADA'"
            );
            $stmt->execute([
                'incidencia'=>$incident['id_incidencia'], 'colaborador'=>$incident['id_colaborador'],
                'tipo'=>$code === 'LICENCIA_CON_GOCE' ? 'CON_GOCE' : 'SIN_GOCE',
                'inicio'=>$startDate, 'fin'=>$endDate, 'motivo'=>$incident['motivo'], 'usuario'=>$userId,
            ]);
            return;
        }

        if ($code === 'DESCANSO_MEDICO') {
            $stmt = $db->prepare(
                "INSERT INTO descansos_medicos (id_incidencia, id_colaborador, fecha_inicio, fecha_fin, documento_path, observacion, registrado_por)
                 VALUES (:incidencia,:colaborador,:inicio,:fin,:documento,:obs,:usuario)
                 ON DUPLICATE KEY UPDATE fecha_inicio=VALUES(fecha_inicio), fecha_fin=VALUES(fecha_fin), documento_path=VALUES(documento_path), observacion=VALUES(observacion)"
            );
            $stmt->execute(['incidencia'=>$incident['id_incidencia'],'colaborador'=>$incident['id_colaborador'],'inicio'=>$startDate,'fin'=>$endDate,'documento'=>$incident['documento_path'],'obs'=>$incident['motivo'],'usuario'=>$userId]);
            return;
        }

        if ($code === 'PERMISO') {
            $stmt = $db->prepare(
                "INSERT INTO permisos (id_incidencia, id_colaborador, fecha_inicio, fecha_fin, motivo, con_goce, estado, registrado_por)
                 VALUES (:incidencia,:colaborador,:inicio,:fin,:motivo,1,'APROBADO',:usuario)
                 ON DUPLICATE KEY UPDATE fecha_inicio=VALUES(fecha_inicio), fecha_fin=VALUES(fecha_fin), motivo=VALUES(motivo), estado='APROBADO'"
            );
            $stmt->execute(['incidencia'=>$incident['id_incidencia'],'colaborador'=>$incident['id_colaborador'],'inicio'=>$incident['fecha_inicio'],'fin'=>$incident['fecha_fin'] ?: $incident['fecha_inicio'],'motivo'=>$incident['motivo'],'usuario'=>$userId]);
        }
    }

    private static function annulSpecializedRecord(int $incidentId): void
    {
        $db = Database::connection();
        foreach (['vacaciones' => 'ANULADA', 'licencias' => 'ANULADA'] as $table => $status) {
            $stmt = $db->prepare("UPDATE {$table} SET estado = :estado WHERE id_incidencia = :incidencia");
            $stmt->execute(['estado' => $status, 'incidencia' => $incidentId]);
        }
        $stmt = $db->prepare("UPDATE permisos SET estado = 'ANULADO' WHERE id_incidencia = :incidencia");
        $stmt->execute(['incidencia' => $incidentId]);
        $stmt = $db->prepare('DELETE FROM descansos_medicos WHERE id_incidencia = :incidencia');
        $stmt->execute(['incidencia' => $incidentId]);
    }
}
