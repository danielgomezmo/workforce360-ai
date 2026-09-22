<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

class Marcacion
{
    public static function collaboratorForUser(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, a.nombre AS area_nombre, e.nombre AS equipo_nombre
             FROM usuarios u
             INNER JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
             LEFT JOIN areas a ON a.id_area = c.id_area
             LEFT JOIN equipos e ON e.id_equipo = c.id_equipo
             WHERE u.id_usuario = :id AND u.activo = 1
             LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function scheduleForDate(int $collaboratorId, string $date): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ah.id_asignacion, ah.es_temporal, h.*
             FROM asignacion_horarios ah
             INNER JOIN horarios h ON h.id_horario = ah.id_horario
             WHERE ah.id_colaborador = :colaborador
               AND ah.fecha_inicio <= :fecha_inicio
               AND (ah.fecha_fin IS NULL OR ah.fecha_fin >= :fecha_fin)
               AND h.activo = 1
             ORDER BY ah.es_temporal DESC, ah.fecha_inicio DESC, ah.id_asignacion DESC
             LIMIT 1"
        );
        $stmt->execute([
            'colaborador' => $collaboratorId,
            'fecha_inicio' => $date,
            'fecha_fin' => $date,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isScheduledDay(array $schedule, string $date): bool
    {
        $days = json_decode((string) ($schedule['dias_semana'] ?? '[]'), true);
        if (!is_array($days) || $days === []) {
            return true;
        }
        $isoDay = (int) (new DateTimeImmutable($date))->format('N');
        return in_array($isoDay, array_map('intval', $days), true);
    }

    public static function findByCollaboratorAndDate(int $collaboratorId, string $date): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, h.nombre AS horario_nombre
             FROM marcaciones m
             LEFT JOIN horarios h ON h.id_horario = m.id_horario
             WHERE m.id_colaborador = :colaborador AND m.fecha = :fecha
             LIMIT 1'
        );
        $stmt->execute(['colaborador' => $collaboratorId, 'fecha' => $date]);
        return $stmt->fetch() ?: null;
    }

    public static function openForCollaborator(int $collaboratorId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.*, h.nombre AS horario_nombre
             FROM marcaciones m
             LEFT JOIN horarios h ON h.id_horario = m.id_horario
             WHERE m.id_colaborador = :colaborador
               AND m.hora_entrada IS NOT NULL
               AND m.hora_salida IS NULL
               AND m.fecha >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
             ORDER BY m.hora_entrada DESC
             LIMIT 1"
        );
        $stmt->execute(['colaborador' => $collaboratorId]);
        return $stmt->fetch() ?: null;
    }

    public static function createEntry(int $collaboratorId, int $scheduleId, string $date, string $entry, string $ip, array $evaluation, float $breakHours): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO marcaciones
             (id_colaborador, id_horario, fecha, hora_entrada, ip_entrada,
              entrada_programada, salida_programada, tolerancia_aplicada,
              horas_descanso_programadas, minutos_tardanza, resultado_entrada, estado, origen)
             VALUES
             (:colaborador, :horario, :fecha, :entrada, :ip,
              :entrada_programada, :salida_programada, :tolerancia,
              :descanso, :tardanza, :resultado, :estado, 'WEB')"
        );
        $stmt->execute([
            'colaborador' => $collaboratorId,
            'horario' => $scheduleId,
            'fecha' => $date,
            'entrada' => $entry,
            'ip' => $ip,
            'entrada_programada' => $evaluation['inicio_programado'],
            'salida_programada' => $evaluation['fin_programado'],
            'tolerancia' => $evaluation['tolerancia_minutos'],
            'descanso' => $breakHours,
            'tardanza' => $evaluation['minutos_tardanza'],
            'resultado' => $evaluation['resultado'],
            'estado' => $evaluation['estado'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function finishExit(int $markId, string $exit, string $ip, array $evaluation): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE marcaciones SET
             hora_salida = :salida,
             ip_salida = :ip,
             minutos_salida_anticipada = :anticipada,
             minutos_trabajados = :trabajados,
             resultado_salida = :resultado,
             estado = :estado
             WHERE id_marcacion = :id AND hora_salida IS NULL'
        );
        $stmt->execute([
            'salida' => $exit,
            'ip' => $ip,
            'anticipada' => $evaluation['minutos_salida_anticipada'],
            'trabajados' => $evaluation['minutos_trabajados'],
            'resultado' => $evaluation['resultado'],
            'estado' => $evaluation['estado'],
            'id' => $markId,
        ]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM marcaciones WHERE id_marcacion = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function recentForCollaborator(int $collaboratorId, int $limit = 12): array
    {
        $limit = max(1, min(60, $limit));
        $stmt = Database::connection()->prepare(
            "SELECT m.*, h.nombre AS horario_nombre
             FROM marcaciones m
             LEFT JOIN horarios h ON h.id_horario = m.id_horario
             WHERE m.id_colaborador = :colaborador
             ORDER BY m.fecha DESC, m.hora_entrada DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['colaborador' => $collaboratorId]);
        return $stmt->fetchAll();
    }

    public static function historyForCollaborator(int $collaboratorId, string $from, string $to, string $status = ''): array
    {
        $sql = "SELECT m.*, h.nombre AS horario_nombre
                FROM marcaciones m
                LEFT JOIN horarios h ON h.id_horario = m.id_horario
                WHERE m.id_colaborador = :colaborador
                  AND m.fecha BETWEEN :desde AND :hasta";
        $params = ['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to];
        if ($status !== '') {
            $sql .= ' AND (m.estado = :estado OR m.resultado_entrada = :resultado_entrada)';
            $params['estado'] = $status;
            $params['resultado_entrada'] = $status;
        }
        $sql .= ' ORDER BY m.fecha DESC, m.hora_entrada DESC LIMIT 500';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function historySummaryForCollaborator(int $collaboratorId, string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN resultado_entrada = 'PUNTUAL' THEN 1 ELSE 0 END) AS puntuales,
                SUM(CASE WHEN resultado_entrada = 'TARDANZA' THEN 1 ELSE 0 END) AS tardanzas,
                COALESCE(SUM(minutos_tardanza),0) AS minutos_tardanza,
                COALESCE(SUM(minutos_trabajados),0) AS minutos_trabajados
             FROM marcaciones
             WHERE id_colaborador = :colaborador AND fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['colaborador'=>$collaboratorId,'desde'=>$from,'hasta'=>$to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map(static fn($value) => (int)($value ?? 0), $row);
    }

    public static function monthlyStatsForCollaborator(int $collaboratorId, ?string $month = null): array
    {
        $month = $month ?: date('Y-m');
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        return self::historySummaryForCollaborator($collaboratorId, $from, $to);
    }

    public static function listForManagement(string $date, string $query = '', ?int $supervisorId = null): array
    {
        $sql = "SELECT m.*, c.codigo_trabajador, c.nombres, c.apellidos, c.cargo,
                       a.nombre AS area_nombre, h.nombre AS horario_nombre
                FROM marcaciones m
                INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN horarios h ON h.id_horario = m.id_horario
                WHERE m.fecha = :fecha";
        $params = ['fecha' => $date];

        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }
        if ($query !== '') {
            $sql .= " AND (c.codigo_trabajador LIKE :q_codigo OR c.numero_documento LIKE :q_documento
                      OR c.nombres LIKE :q_nombres OR c.apellidos LIKE :q_apellidos OR a.nombre LIKE :q_area)";
            $search = '%' . $query . '%';
            $params['q_codigo'] = $search;
            $params['q_documento'] = $search;
            $params['q_nombres'] = $search;
            $params['q_apellidos'] = $search;
            $params['q_area'] = $search;
        }
        $sql .= ' ORDER BY m.hora_entrada ASC, c.apellidos, c.nombres';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function dailySummary(string $date, ?int $supervisorId = null): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN m.resultado_entrada = 'PUNTUAL' THEN 1 ELSE 0 END) AS puntuales,
                    SUM(CASE WHEN m.resultado_entrada = 'TARDANZA' THEN 1 ELSE 0 END) AS tardanzas,
                    SUM(CASE WHEN m.resultado_salida = 'ANTICIPADA' THEN 1 ELSE 0 END) AS salidas_anticipadas,
                    SUM(CASE WHEN m.hora_salida IS NULL THEN 1 ELSE 0 END) AS jornadas_abiertas,
                    COALESCE(SUM(m.minutos_trabajados), 0) AS minutos_trabajados
                FROM marcaciones m
                INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
                WHERE m.fecha = :fecha";
        $params = ['fecha' => $date];
        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map(static fn ($value) => (int) ($value ?? 0), $row);
    }

    public static function createAutomaticIncident(int $collaboratorId, string $code, string $start, ?string $end, string $reason, int $userId): void
    {
        $db = Database::connection();
        $type = $db->prepare('SELECT id_tipo_incidencia FROM tipos_incidencia WHERE codigo = :codigo AND activo = 1 LIMIT 1');
        $type->execute(['codigo' => $code]);
        $typeId = $type->fetchColumn();
        if (!$typeId) {
            return;
        }

        $exists = $db->prepare(
            'SELECT COUNT(*) FROM incidencias
             WHERE id_colaborador = :colaborador AND id_tipo_incidencia = :tipo AND DATE(fecha_inicio) = DATE(:inicio)
               AND estado <> \'ANULADA\''
        );
        $exists->execute(['colaborador' => $collaboratorId, 'tipo' => $typeId, 'inicio' => $start]);
        if ((int) $exists->fetchColumn() > 0) {
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO incidencias
             (id_colaborador, id_tipo_incidencia, fecha_inicio, fecha_fin, motivo, comentario, estado, registrado_por)
             VALUES (:colaborador, :tipo, :inicio, :fin, :motivo, :comentario, 'REGISTRADA', :usuario)"
        );
        $stmt->execute([
            'colaborador' => $collaboratorId,
            'tipo' => $typeId,
            'inicio' => $start,
            'fin' => $end,
            'motivo' => $reason,
            'comentario' => 'Generada automáticamente por el motor de reglas de asistencia.',
            'usuario' => $userId,
        ]);
    }
}
