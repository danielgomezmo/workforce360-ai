<?php

namespace App\Models;

use App\Core\Database;
use App\Services\AttendanceRuleEngine;
use DateTimeImmutable;
use PDO;

class Dashboard
{
    public static function operationalMetrics(string $date, ?int $supervisorId = null): array
    {
        $scheduled = self::scheduledForDate($date, $supervisorId);
        $marks = self::marksForDate($date, $supervisorId);
        $absences = self::approvedAbsencesForDate($date, $supervisorId);

        $scheduledById = [];
        $scheduledMinutes = 0;

        foreach ($scheduled as $row) {
            if (!Marcacion::isScheduledDay($row, $date)) {
                continue;
            }

            $id = (int) $row['id_colaborador'];
            $scheduledById[$id] = $row;

            $window = AttendanceRuleEngine::scheduleWindow($row, $date);
            $grossMinutes = max(
                0,
                intdiv($window['fin']->getTimestamp() - $window['inicio']->getTimestamp(), 60)
            );
            $breakMinutes = max(0, (int) round(((float) ($row['horas_descanso'] ?? 0)) * 60));
            $scheduledMinutes += max(0, $grossMinutes - $breakMinutes);
        }

        $marksById = [];
        foreach ($marks as $mark) {
            $id = (int) $mark['id_colaborador'];
            if (isset($scheduledById[$id])) {
                $marksById[$id] = $mark;
            }
        }

        $absenceByCollaborator = [];
        $absenceCounts = [];
        foreach ($absences as $absence) {
            $id = (int) $absence['id_colaborador'];
            if (!isset($scheduledById[$id])) {
                continue;
            }

            $code = (string) $absence['codigo'];
            $absenceByCollaborator[$id] = true;
            $absenceCounts[$code] = ($absenceCounts[$code] ?? 0) + 1;
        }

        $programmed = count($scheduledById);
        $present = 0;
        $punctual = 0;
        $late = 0;
        $early = 0;
        $lateMinutes = 0;
        $earlyMinutes = 0;
        $workedMinutes = 0;

        foreach ($marksById as $mark) {
            if (!empty($mark['hora_entrada'])) {
                $present++;
            }

            if (($mark['resultado_entrada'] ?? '') === 'PUNTUAL') {
                $punctual++;
            }

            if (($mark['resultado_entrada'] ?? '') === 'TARDANZA') {
                $late++;
            }

            if (($mark['resultado_salida'] ?? '') === 'ANTICIPADA') {
                $early++;
            }

            $lateMinutes += (int) ($mark['minutos_tardanza'] ?? 0);
            $earlyMinutes += (int) ($mark['minutos_salida_anticipada'] ?? 0);
            $workedMinutes += self::workedMinutes($mark, $date);
        }

        $absent = max(0, $programmed - $present);
        $justifiedAbsences = count($absenceByCollaborator);
        $unvalidatedAbsences = max(0, $absent - $justifiedAbsences);

        $attendanceRate = self::percent($present, $programmed);
        $punctualityRate = self::percent($punctual, $present);
        $latenessRate = self::percent($late, $programmed);
        $journeyCompliance = self::percent($workedMinutes, $scheduledMinutes);

        $absenceMinutes = 0;
        foreach ($scheduledById as $id => $schedule) {
            if (isset($marksById[$id])) {
                $absenceMinutes += (int) ($marksById[$id]['minutos_tardanza'] ?? 0);
                $absenceMinutes += (int) ($marksById[$id]['minutos_salida_anticipada'] ?? 0);
                continue;
            }

            if ($date < date('Y-m-d') || isset($absenceByCollaborator[$id])) {
                $window = AttendanceRuleEngine::scheduleWindow($schedule, $date);
                $grossMinutes = max(
                    0,
                    intdiv($window['fin']->getTimestamp() - $window['inicio']->getTimestamp(), 60)
                );
                $breakMinutes = max(0, (int) round(((float) ($schedule['horas_descanso'] ?? 0)) * 60));
                $absenceMinutes += max(0, $grossMinutes - $breakMinutes);
            }
        }

        $absenteeismRate = self::percent($absenceMinutes, $scheduledMinutes);

        return [
            'fecha' => $date,
            'programados' => $programmed,
            'presentes' => $present,
            'ausentes' => $absent,
            'faltas_por_validar' => $unvalidatedAbsences,
            'puntuales' => $punctual,
            'tardanzas' => $late,
            'salidas_anticipadas' => $early,
            'vacaciones' => (int) ($absenceCounts['VACACIONES'] ?? 0),
            'descansos_medicos' => (int) ($absenceCounts['DESCANSO_MEDICO'] ?? 0),
            'licencias' => (int) (($absenceCounts['LICENCIA_CON_GOCE'] ?? 0) + ($absenceCounts['LICENCIA_SIN_GOCE'] ?? 0)),
            'permisos' => (int) ($absenceCounts['PERMISO'] ?? 0),
            'minutos_tardanza' => $lateMinutes,
            'minutos_salida_anticipada' => $earlyMinutes,
            'minutos_programados' => $scheduledMinutes,
            'minutos_trabajados' => $workedMinutes,
            'indice_asistencia' => $attendanceRate,
            'indice_puntualidad' => $punctualityRate,
            'indice_tardanza' => $latenessRate,
            'cumplimiento_jornada' => $journeyCompliance,
            'absentismo' => $absenteeismRate,
        ];
    }

    private static function scheduledForDate(string $date, ?int $supervisorId): array
    {
        $sql = "SELECT
                    c.id_colaborador,
                    c.nombres,
                    c.apellidos,
                    c.id_supervisor,
                    h.id_horario,
                    h.nombre,
                    h.hora_inicio,
                    h.hora_fin,
                    h.minutos_tolerancia,
                    h.horas_descanso,
                    h.cruza_medianoche,
                    h.dias_semana
                FROM colaboradores c
                INNER JOIN asignacion_horarios ah ON ah.id_colaborador = c.id_colaborador
                INNER JOIN horarios h ON h.id_horario = ah.id_horario
                WHERE c.fecha_ingreso <= :fecha_ingreso
                  AND (c.fecha_cese IS NULL OR c.fecha_cese >= :fecha_cese)
                  AND c.estado IN ('HABILITADO','ACTIVO','LICENCIA','VACACIONES')
                  AND h.activo = 1
                  AND ah.fecha_inicio <= :fecha_asignacion_inicio
                  AND (ah.fecha_fin IS NULL OR ah.fecha_fin >= :fecha_asignacion_fin)
                  AND ah.id_asignacion = (
                        SELECT ah2.id_asignacion
                        FROM asignacion_horarios ah2
                        WHERE ah2.id_colaborador = c.id_colaborador
                          AND ah2.fecha_inicio <= :fecha_sub_inicio
                          AND (ah2.fecha_fin IS NULL OR ah2.fecha_fin >= :fecha_sub_fin)
                        ORDER BY ah2.es_temporal DESC, ah2.fecha_inicio DESC, ah2.id_asignacion DESC
                        LIMIT 1
                  )";

        $params = [
            'fecha_ingreso' => $date,
            'fecha_cese' => $date,
            'fecha_asignacion_inicio' => $date,
            'fecha_asignacion_fin' => $date,
            'fecha_sub_inicio' => $date,
            'fecha_sub_fin' => $date,
        ];

        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor';
            $params['supervisor'] = $supervisorId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function marksForDate(string $date, ?int $supervisorId): array
    {
        $sql = "SELECT m.*, c.id_supervisor
                FROM marcaciones m
                INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
                WHERE m.fecha = :fecha_marcacion";
        $params = ['fecha_marcacion' => $date];

        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor_marcacion';
            $params['supervisor_marcacion'] = $supervisorId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function approvedAbsencesForDate(string $date, ?int $supervisorId): array
    {
        $sql = "SELECT DISTINCT i.id_colaborador, t.codigo
                FROM incidencias i
                INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
                INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
                WHERE i.estado = 'APROBADA'
                  AND t.es_ausencia = 1
                  AND DATE(i.fecha_inicio) <= :fecha_ausencia_inicio
                  AND DATE(COALESCE(i.fecha_fin, i.fecha_inicio)) >= :fecha_ausencia_fin";
        $params = [
            'fecha_ausencia_inicio' => $date,
            'fecha_ausencia_fin' => $date,
        ];

        if ($supervisorId !== null) {
            $sql .= ' AND c.id_supervisor = :supervisor_ausencia';
            $params['supervisor_ausencia'] = $supervisorId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function workedMinutes(array $mark, string $date): int
    {
        if (!empty($mark['hora_salida'])) {
            return max(0, (int) ($mark['minutos_trabajados'] ?? 0));
        }

        if ($date !== date('Y-m-d') || empty($mark['hora_entrada'])) {
            return 0;
        }

        $entry = new DateTimeImmutable((string) $mark['hora_entrada']);
        $now = new DateTimeImmutable('now');
        $end = !empty($mark['salida_programada'])
            ? new DateTimeImmutable((string) $mark['salida_programada'])
            : $now;
        $effectiveEnd = $now < $end ? $now : $end;

        if ($effectiveEnd <= $entry) {
            return 0;
        }

        $grossMinutes = intdiv($effectiveEnd->getTimestamp() - $entry->getTimestamp(), 60);
        $breakMinutes = max(0, (int) round(((float) ($mark['horas_descanso_programadas'] ?? 0)) * 60));
        return max(0, $grossMinutes - $breakMinutes);
    }

    private static function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }
}
