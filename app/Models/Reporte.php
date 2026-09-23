<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Reporte
{
    public static function attendance(array $filters = [], ?int $supervisorId = null): array
    {
        $sql = "SELECT
                    m.id_marcacion,
                    m.id_colaborador,
                    m.fecha,
                    m.hora_entrada,
                    m.hora_salida,
                    m.resultado_entrada,
                    m.resultado_salida,
                    m.estado,
                    m.minutos_tardanza,
                    m.minutos_salida_anticipada,
                    m.minutos_trabajados,
                    m.entrada_programada,
                    m.salida_programada,
                    m.horas_descanso_programadas,
                    c.codigo_trabajador,
                    c.numero_documento,
                    c.nombres,
                    c.apellidos,
                    c.cargo,
                    c.sede,
                    a.nombre AS area_nombre,
                    h.nombre AS horario_nombre
                FROM marcaciones m
                INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN horarios h ON h.id_horario = m.id_horario
                WHERE 1 = 1";

        $params = [];
        self::appendAttendanceFilters($sql, $params, $filters, $supervisorId);

        $sql .= ' ORDER BY m.fecha DESC, c.apellidos ASC, c.nombres ASC, m.hora_entrada ASC LIMIT 5000';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function lateness(array $filters = [], ?int $supervisorId = null): array
    {
        $filters['estado'] = 'TARDANZA';
        return self::attendance($filters, $supervisorId);
    }

    public static function earlyDepartures(array $filters = [], ?int $supervisorId = null): array
    {
        $sql = "SELECT
                    m.id_marcacion,
                    m.id_colaborador,
                    m.fecha,
                    m.hora_entrada,
                    m.hora_salida,
                    m.resultado_entrada,
                    m.resultado_salida,
                    m.estado,
                    m.minutos_tardanza,
                    m.minutos_salida_anticipada,
                    m.minutos_trabajados,
                    m.entrada_programada,
                    m.salida_programada,
                    m.horas_descanso_programadas,
                    c.codigo_trabajador,
                    c.numero_documento,
                    c.nombres,
                    c.apellidos,
                    c.cargo,
                    c.sede,
                    a.nombre AS area_nombre,
                    h.nombre AS horario_nombre
                FROM marcaciones m
                INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN horarios h ON h.id_horario = m.id_horario
                WHERE m.resultado_salida = 'ANTICIPADA'";

        $params = [];
        self::appendCommonMarkFilters($sql, $params, $filters, $supervisorId);
        $sql .= ' ORDER BY m.fecha DESC, m.minutos_salida_anticipada DESC, c.apellidos ASC LIMIT 5000';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function incidents(array $filters = [], ?int $supervisorId = null): array
    {
        $sql = "SELECT
                    i.id_incidencia,
                    i.id_colaborador,
                    i.fecha_inicio,
                    i.fecha_fin,
                    i.motivo,
                    i.comentario,
                    i.estado,
                    i.documento_path,
                    i.observacion_revision,
                    i.revisado_en,
                    t.id_tipo_incidencia,
                    t.codigo AS tipo_codigo,
                    t.nombre AS tipo_nombre,
                    t.es_ausencia,
                    t.es_justificada,
                    c.codigo_trabajador,
                    c.numero_documento,
                    c.nombres,
                    c.apellidos,
                    c.cargo,
                    c.sede,
                    a.nombre AS area_nombre,
                    ur.username AS registrado_por_username,
                    uv.username AS revisado_por_username
                FROM incidencias i
                INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
                INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
                LEFT JOIN areas a ON a.id_area = c.id_area
                LEFT JOIN usuarios ur ON ur.id_usuario = i.registrado_por
                LEFT JOIN usuarios uv ON uv.id_usuario = i.revisado_por
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['desde'])) {
            $sql .= ' AND DATE(i.fecha_inicio) >= :inc_desde';
            $params['inc_desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $sql .= ' AND DATE(i.fecha_inicio) <= :inc_hasta';
            $params['inc_hasta'] = $filters['hasta'];
        }
        if (!empty($filters['colaborador'])) {
            $sql .= ' AND i.id_colaborador = :inc_colaborador';
            $params['inc_colaborador'] = (int) $filters['colaborador'];
        }
        if (!empty($filters['area'])) {
            $sql .= ' AND c.id_area = :inc_area';
            $params['inc_area'] = (int) $filters['area'];
        }
        if (!empty($filters['tipo'])) {
            $sql .= ' AND i.id_tipo_incidencia = :inc_tipo';
            $params['inc_tipo'] = (int) $filters['tipo'];
        }
        if (!empty($filters['estado'])) {
            $sql .= ' AND i.estado = :inc_estado';
            $params['inc_estado'] = (string) $filters['estado'];
        }
        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :inc_supervisor';
            $params['inc_supervisor'] = $supervisorId;
        }

        $sql .= ' ORDER BY i.fecha_inicio DESC, c.apellidos ASC, c.nombres ASC LIMIT 5000';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function workedHours(array $filters = [], ?int $supervisorId = null): array
    {
        $rows = self::attendance($filters, $supervisorId);

        foreach ($rows as &$row) {
            $scheduled = self::scheduledMinutesForRow($row);
            $worked = (int) ($row['minutos_trabajados'] ?? 0);
            $row['minutos_programados'] = $scheduled;
            $row['diferencia_minutos'] = $worked - $scheduled;
            $row['cumplimiento_porcentaje'] = $scheduled > 0
                ? round(($worked / $scheduled) * 100, 1)
                : 0.0;
        }
        unset($row);

        return $rows;
    }

    public static function attendanceSummary(array $rows): array
    {
        $summary = [
            'total' => 0,
            'puntuales' => 0,
            'tardanzas' => 0,
            'salidas_anticipadas' => 0,
            'minutos_tardanza' => 0,
            'minutos_salida_anticipada' => 0,
            'minutos_trabajados' => 0,
            'indice_puntualidad' => 0.0,
        ];

        foreach ($rows as $row) {
            $summary['total']++;

            if (($row['resultado_entrada'] ?? '') === 'PUNTUAL') {
                $summary['puntuales']++;
            }
            if (($row['resultado_entrada'] ?? '') === 'TARDANZA') {
                $summary['tardanzas']++;
            }
            if (($row['resultado_salida'] ?? '') === 'ANTICIPADA') {
                $summary['salidas_anticipadas']++;
            }

            $summary['minutos_tardanza'] += (int) ($row['minutos_tardanza'] ?? 0);
            $summary['minutos_salida_anticipada'] += (int) ($row['minutos_salida_anticipada'] ?? 0);
            $summary['minutos_trabajados'] += (int) ($row['minutos_trabajados'] ?? 0);
        }

        if ($summary['total'] > 0) {
            $summary['indice_puntualidad'] = round(($summary['puntuales'] / $summary['total']) * 100, 1);
        }

        return $summary;
    }

    public static function latenessSummary(array $rows): array
    {
        $minutes = array_map(static fn(array $row): int => (int) ($row['minutos_tardanza'] ?? 0), $rows);
        $totalMinutes = array_sum($minutes);
        $count = count($rows);

        return [
            'total' => $count,
            'minutos_total' => $totalMinutes,
            'promedio_minutos' => $count > 0 ? round($totalMinutes / $count, 1) : 0.0,
            'maximo_minutos' => $minutes ? max($minutes) : 0,
        ];
    }

    public static function earlyDepartureSummary(array $rows): array
    {
        $minutes = array_map(static fn(array $row): int => (int) ($row['minutos_salida_anticipada'] ?? 0), $rows);
        $totalMinutes = array_sum($minutes);
        $count = count($rows);

        return [
            'total' => $count,
            'minutos_total' => $totalMinutes,
            'promedio_minutos' => $count > 0 ? round($totalMinutes / $count, 1) : 0.0,
            'maximo_minutos' => $minutes ? max($minutes) : 0,
        ];
    }

    public static function incidentSummary(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'pendientes' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
            'anuladas' => 0,
        ];

        foreach ($rows as $row) {
            $state = (string) ($row['estado'] ?? '');
            if ($state === 'PENDIENTE') $summary['pendientes']++;
            if ($state === 'APROBADA') $summary['aprobadas']++;
            if ($state === 'RECHAZADA') $summary['rechazadas']++;
            if ($state === 'ANULADA') $summary['anuladas']++;
        }

        return $summary;
    }

    public static function workedHoursSummary(array $rows): array
    {
        $scheduled = 0;
        $worked = 0;

        foreach ($rows as $row) {
            $scheduled += (int) ($row['minutos_programados'] ?? 0);
            $worked += (int) ($row['minutos_trabajados'] ?? 0);
        }

        return [
            'jornadas' => count($rows),
            'minutos_programados' => $scheduled,
            'minutos_trabajados' => $worked,
            'diferencia_minutos' => $worked - $scheduled,
            'cumplimiento' => $scheduled > 0 ? round(($worked / $scheduled) * 100, 1) : 0.0,
        ];
    }

    public static function collaborators(?int $supervisorId = null): array
    {
        $sql = "SELECT id_colaborador, codigo_trabajador, nombres, apellidos
                FROM colaboradores
                WHERE estado <> 'CESADO'";
        $params = [];

        if ($supervisorId !== null) {
            $sql .= ' AND id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }

        $sql .= ' ORDER BY apellidos, nombres';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function areas(?int $supervisorId = null): array
    {
        if ($supervisorId === null) {
            return Database::connection()
                ->query('SELECT id_area, nombre FROM areas WHERE activo = 1 ORDER BY nombre')
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT a.id_area, a.nombre
             FROM areas a
             INNER JOIN colaboradores c ON c.id_area = a.id_area
             WHERE a.activo = 1 AND c.id_supervisor = :supervisor
             ORDER BY a.nombre"
        );
        $stmt->execute(['supervisor' => $supervisorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function incidentTypes(): array
    {
        return Database::connection()
            ->query("SELECT id_tipo_incidencia, codigo, nombre
                     FROM tipos_incidencia
                     WHERE activo = 1
                     ORDER BY nombre")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function appendAttendanceFilters(string &$sql, array &$params, array $filters, ?int $supervisorId): void
    {
        self::appendCommonMarkFilters($sql, $params, $filters, $supervisorId);

        $status = (string) ($filters['estado'] ?? '');
        if (in_array($status, ['PUNTUAL', 'TARDANZA'], true)) {
            $sql .= ' AND m.resultado_entrada = :estado_entrada';
            $params['estado_entrada'] = $status;
        } elseif (in_array($status, ['COMPLETA', 'PARCIAL'], true)) {
            $sql .= ' AND m.estado = :estado_jornada';
            $params['estado_jornada'] = $status;
        }
    }

    private static function appendCommonMarkFilters(string &$sql, array &$params, array $filters, ?int $supervisorId): void
    {
        if (!empty($filters['desde'])) {
            $sql .= ' AND m.fecha >= :marca_desde';
            $params['marca_desde'] = $filters['desde'];
        }

        if (!empty($filters['hasta'])) {
            $sql .= ' AND m.fecha <= :marca_hasta';
            $params['marca_hasta'] = $filters['hasta'];
        }

        if (!empty($filters['colaborador'])) {
            $sql .= ' AND m.id_colaborador = :marca_colaborador';
            $params['marca_colaborador'] = (int) $filters['colaborador'];
        }

        if (!empty($filters['area'])) {
            $sql .= ' AND c.id_area = :marca_area';
            $params['marca_area'] = (int) $filters['area'];
        }

        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :marca_supervisor';
            $params['marca_supervisor'] = $supervisorId;
        }
    }

    private static function scheduledMinutesForRow(array $row): int
    {
        if (empty($row['entrada_programada']) || empty($row['salida_programada'])) {
            return 0;
        }

        $start = strtotime((string) $row['entrada_programada']);
        $end = strtotime((string) $row['salida_programada']);

        if ($start === false || $end === false || $end <= $start) {
            return 0;
        }

        $gross = intdiv($end - $start, 60);
        $breakMinutes = (int) round(((float) ($row['horas_descanso_programadas'] ?? 0)) * 60);
        return max(0, $gross - $breakMinutes);
    }
}
