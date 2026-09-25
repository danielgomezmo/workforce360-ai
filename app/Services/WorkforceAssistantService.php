<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Dashboard;
use DateTimeImmutable;
use PDO;

final class WorkforceAssistantService
{
    public function answer(string $message, bool $isAdmin, ?int $collaboratorId): array
    {
        $text = $this->normalize($message);

        if ($this->containsAny($text, ['hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'ayuda', 'que puedes hacer', 'que sabes'])) {
            return $isAdmin ? $this->adminHelp() : $this->personalHelp();
        }

        if ($isAdmin) {
            return $this->answerAdmin($text, $message);
        }

        return $this->answerPersonal($text, $collaboratorId ?? 0);
    }

    private function answerAdmin(string $text, string $original): array
    {
        if ($this->containsAny($text, ['activar ai', 'activar ia', 'iniciar ai', 'iniciar ia', 'encender ai', 'encender ia', 'activar workforce ai', 'iniciar workforce ai'])) {
            return $this->aiActivationHelp();
        }

        if ($this->containsAny($text, ['estado de fastapi', 'fastapi activo', 'fastapi esta activo', 'servicio de ia activo', 'ia esta activa'])) {
            return $this->adminAiHealth();
        }

        if ($this->containsAny($text, ['modelo predictivo', 'estado del modelo', 'modelo de ia', 'modelo ia'])) {
            return $this->adminModelStatus();
        }

        if ($this->containsAny($text, ['historial de predicciones', 'historial de pronosticos', 'predicciones guardadas', 'seguimiento de predicciones', 'seguimiento de pronosticos'])) {
            return $this->adminPredictionHistorySummary();
        }

        if ($this->containsAny($text, ['prediccion de ayer', 'pronostico de ayer', 'fue correcta la prediccion', 'fue correcto el pronostico', 'comparar prediccion', 'comparar pronostico', 'error de la prediccion', 'error del pronostico'])) {
            return $this->adminPredictionComparison($text);
        }

        if ($this->containsAny($text, ['lista de asistencia', 'lista de los que asistieron', 'quienes asistieron', 'quienes vinieron', 'asistieron hoy', 'asistencia sede'])) {
            return $this->adminAttendanceList($this->resolveDate($text));
        }

        if ($this->containsAny($text, ['pronostico', 'prediccion', 'predecir', 'predice'])) {
            if ($this->containsAny($text, ['semanal', 'semana', '7 dias', 'proximos dias'])) {
                return $this->adminWeeklyForecast();
            }
            return $this->adminDailyForecast($text);
        }

        if ($this->containsAny($text, ['top tardanzas', 'mas tardanzas', 'quien llega tarde', 'quienes llegan tarde'])) {
            return $this->adminTopLateness($this->resolveMonth($text));
        }

        if ($this->containsAny($text, ['incidencia pendiente', 'incidencias pendientes', 'solicitudes pendientes'])) {
            return $this->adminPendingIncidents();
        }

        if ($this->containsAny($text, ['incidencia', 'incidencias', 'solicitud', 'solicitudes'])) {
            return $this->adminIncidentSummary();
        }

        if ($this->containsAny($text, ['colaboradores activos', 'personal activo', 'cuantos colaboradores', 'cantidad de colaboradores'])) {
            return $this->adminCollaboratorSummary();
        }

        if ($this->containsAny($text, ['areas', 'por area', 'personal por area'])) {
            return $this->adminAreaSummary();
        }

        if ($this->containsAny($text, ['tardanza', 'tardanzas', 'llegaron tarde', 'llegadas tarde'])) {
            return $this->adminDailyMetric($this->resolveDate($text), 'tardanzas');
        }

        if ($this->containsAny($text, ['falta', 'faltas', 'ausencia', 'ausencias', 'ausentes'])) {
            return $this->adminDailyMetric($this->resolveDate($text), 'ausencias');
        }

        if ($this->containsAny($text, ['puntualidad', 'puntuales'])) {
            return $this->adminDailyMetric($this->resolveDate($text), 'puntualidad');
        }

        if ($this->containsAny($text, ['horas trabajadas', 'jornada', 'cumplimiento'])) {
            return $this->adminDailyMetric($this->resolveDate($text), 'jornada');
        }

        if ($this->containsAny($text, ['asistencia', 'resumen', 'como estamos', 'que paso hoy', 'estado de hoy'])) {
            return $this->adminDailyMetric($this->resolveDate($text), 'resumen');
        }

        if ($this->containsAny($text, ['modulos', 'modulo', 'que hace el sistema', 'workforce360', 'workforce 360', 'sistema'])) {
            return $this->systemOverview();
        }

        return [
            'intent' => 'admin_unknown',
            'message' => "No identifiqué esa consulta todavía. Puedo responder con datos reales del sistema.\n\nPrueba con:\n• Resumen de asistencia de hoy\n• ¿Cuántas tardanzas hubo hoy?\n• ¿Cuántos ausentes hay hoy?\n• Incidencias pendientes\n• Top tardanzas del mes\n• Predicción de asistencia\n• Predicción semanal\n• Estado del modelo predictivo",
        ];
    }

    private function answerPersonal(string $text, int $collaboratorId): array
    {
        if ($this->containsAny($text, ['pronostico', 'prediccion', 'modelo de ia', 'modelo ia'])) {
            return [
                'intent' => 'personal_prediction_blocked',
                'message' => 'Las predicciones globales están disponibles únicamente para el Administrador. Puedo ayudarte con tus propias asistencias, tardanzas, faltas, incidencias y marcaciones.',
            ];
        }

        if ($this->containsAny($text, ['ultima marcacion', 'mi ultima entrada', 'mi ultima salida'])) {
            return $this->personalLastMark($collaboratorId);
        }

        if ($this->containsAny($text, ['hoy', 'mi estado', 'marque hoy', 'marcacion de hoy'])) {
            return $this->personalToday($collaboratorId);
        }

        if ($this->containsAny($text, ['tardanza', 'tardanzas', 'llegue tarde'])) {
            return $this->personalLateness($collaboratorId, $this->resolveMonth($text));
        }

        if ($this->containsAny($text, ['falta', 'faltas', 'ausencia', 'ausencias'])) {
            return $this->personalAbsences($collaboratorId, $this->resolveMonth($text));
        }

        if ($this->containsAny($text, ['incidencia', 'incidencias', 'solicitud', 'solicitudes', 'vacaciones', 'permiso', 'licencia'])) {
            return $this->personalIncidents($collaboratorId);
        }

        if ($this->containsAny($text, ['horas', 'trabajadas', 'jornada'])) {
            return $this->personalMonthlySummary($collaboratorId, $this->resolveMonth($text), true);
        }

        if ($this->containsAny($text, ['asistencia', 'asistencias', 'resumen', 'este mes', 'mi mes'])) {
            return $this->personalMonthlySummary($collaboratorId, $this->resolveMonth($text));
        }

        return [
            'intent' => 'personal_unknown',
            'message' => "Solo puedo consultar información vinculada a tu propia cuenta.\n\nPrueba con:\n• Mi asistencia este mes\n• ¿Cuántas tardanzas tengo?\n• Mis faltas del mes\n• Mis incidencias\n• Mi estado de hoy\n• Mi última marcación\n• ¿Cuántas horas trabajé este mes?",
        ];
    }

    private function adminDailyMetric(string $date, string $mode): array
    {
        if ($date > date('Y-m-d')) {
            return [
                'intent' => 'admin_future_data',
                'message' => 'Esa fecha todavía no tiene datos reales. Para fechas futuras pregúntame por la predicción de asistencia.',
            ];
        }

        $m = Dashboard::operationalMetrics($date);
        $label = $this->humanDate($date);

        if ($mode === 'tardanzas') {
            return [
                'intent' => 'admin_lateness',
                'message' => "Tardanzas del {$label}: {$m['tardanzas']} colaboradores.\nMinutos acumulados de tardanza: {$m['minutos_tardanza']} min.\nÍndice de tardanza: {$m['indice_tardanza']}%.",
            ];
        }

        if ($mode === 'ausencias') {
            return [
                'intent' => 'admin_absences',
                'message' => "Ausencias del {$label}: {$m['ausentes']} de {$m['programados']} programados.\nFaltas por validar: {$m['faltas_por_validar']}.\nVacaciones: {$m['vacaciones']} · Descansos médicos: {$m['descansos_medicos']} · Licencias: {$m['licencias']} · Permisos: {$m['permisos']}.\nAbsentismo estimado con jornada perdida: {$m['absentismo']}%.",
            ];
        }

        if ($mode === 'puntualidad') {
            return [
                'intent' => 'admin_punctuality',
                'message' => "Puntualidad del {$label}: {$m['puntuales']} puntuales de {$m['presentes']} presentes.\nÍndice de puntualidad: {$m['indice_puntualidad']}%.",
            ];
        }

        if ($mode === 'jornada') {
            $workedHours = round(((int) $m['minutos_trabajados']) / 60, 1);
            $scheduledHours = round(((int) $m['minutos_programados']) / 60, 1);
            return [
                'intent' => 'admin_journey',
                'message' => "Jornada del {$label}: {$workedHours} h trabajadas de {$scheduledHours} h programadas.\nCumplimiento de jornada: {$m['cumplimiento_jornada']}%.\nSalidas anticipadas: {$m['salidas_anticipadas']}.",
            ];
        }

        return [
            'intent' => 'admin_daily_summary',
            'message' => "Resumen del {$label}:\n• Programados: {$m['programados']}\n• Presentes: {$m['presentes']}\n• Ausentes: {$m['ausentes']}\n• Puntuales: {$m['puntuales']}\n• Tardanzas: {$m['tardanzas']}\n• Salidas anticipadas: {$m['salidas_anticipadas']}\n• Asistencia: {$m['indice_asistencia']}%\n• Puntualidad: {$m['indice_puntualidad']}%\n• Absentismo: {$m['absentismo']}%",
        ];
    }

    private function adminAttendanceList(string $date): array
    {
        if ($date > date('Y-m-d')) {
            return [
                'intent' => 'admin_attendance_list_future',
                'message' => 'Todavía no existe una lista real de asistencia para una fecha futura. Para fechas futuras puedes consultar la predicción de asistencia.',
            ];
        }

        $stmt = Database::connection()->prepare(
            "SELECT
                c.id_colaborador,
                c.nombres,
                c.apellidos,
                COALESCE(NULLIF(TRIM(c.sede), ''), 'Sin sede') AS sede,
                MIN(m.hora_entrada) AS primera_entrada
             FROM marcaciones m
             INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
             WHERE m.fecha = :fecha
               AND m.hora_entrada IS NOT NULL
             GROUP BY c.id_colaborador, c.nombres, c.apellidos, c.sede
             ORDER BY sede ASC, c.apellidos ASC, c.nombres ASC"
        );
        $stmt->execute(['fecha' => $date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [
                'intent' => 'admin_attendance_list',
                'message' => "SISTEMA DEVIOZ\nAsistencia Sede \"Sin registros\"\n" . $this->humanLongDate($date)
                    . "\n\nNo hay colaboradores con marcación de ingreso registrada para esta fecha.\n\nGenerado automáticamente · Sistema Devioz",
            ];
        }

        $bySite = [];
        foreach ($rows as $row) {
            $site = trim((string) ($row['sede'] ?? '')) ?: 'Sin sede';
            $bySite[$site][] = $row;
        }

        $blocks = [];
        foreach ($bySite as $site => $attendees) {
            $lines = [
                'SISTEMA DEVIOZ',
                'Asistencia Sede "' . $site . '"',
                $this->humanLongDate($date),
                '',
            ];

            foreach ($attendees as $index => $row) {
                $name = trim((string) $row['nombres'] . ' ' . (string) $row['apellidos']);
                $lines[] = ($index + 1) . '. ' . $name;
            }

            $lines[] = '';
            $lines[] = 'Generado automáticamente · Sistema Devioz';
            $blocks[] = implode("\n", $lines);
        }

        return [
            'intent' => 'admin_attendance_list',
            'message' => implode("\n\n────────────────────\n\n", $blocks),
        ];
    }

    private function adminAiHealth(): array
    {
        $service = new AiPredictionService();
        $response = $service->health();

        if (!($response['ok'] ?? false)) {
            return [
                'intent' => 'admin_ai_health_error',
                'message' => "Workforce AI no está disponible en este momento.\n\nPara activarlo, ejecuta INICIAR_AI_WORKFORCE.bat desde la raíz del proyecto y deja esa ventana abierta. Después prueba nuevamente.",
            ];
        }

        return [
            'intent' => 'admin_ai_health',
            'message' => "Workforce AI está activo y FastAPI está respondiendo correctamente.\n\nPuedes consultar:\n• Predicción de asistencia\n• Predicción semanal\n• Estado del modelo predictivo",
        ];
    }

    private function aiActivationHelp(): array
    {
        return [
            'intent' => 'admin_ai_activation_help',
            'message' => "Para activar Workforce AI en tu entorno local:\n\n1. Enciende Apache y MySQL desde XAMPP.\n2. En la raíz de Workforce360AI haz doble clic en INICIAR_AI_WORKFORCE.bat.\n3. No cierres la ventana de Uvicorn mientras uses las predicciones.\n4. Comprueba el servicio en http://127.0.0.1:8000/health o abre http://127.0.0.1:8000/docs.\n\nTambién puedes preguntarme: ¿FastAPI está activo?",
        ];
    }

    private function adminIncidentSummary(): array
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado IN ('PENDIENTE','REGISTRADA') THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN estado = 'APROBADA' THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'RECHAZADA' THEN 1 ELSE 0 END) AS rechazadas
             FROM incidencias"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'intent' => 'admin_incidents',
            'message' => 'Incidencias registradas: ' . (int) ($row['total'] ?? 0)
                . ".\nAbiertas/Pendientes: " . (int) ($row['abiertas'] ?? 0)
                . " · Aprobadas: " . (int) ($row['aprobadas'] ?? 0)
                . ' · Rechazadas: ' . (int) ($row['rechazadas'] ?? 0) . '.',
        ];
    }

    private function adminPendingIncidents(): array
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT i.id_incidencia, t.nombre AS tipo_nombre, c.nombres, c.apellidos, i.fecha_inicio, i.estado
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             INNER JOIN colaboradores c ON c.id_colaborador = i.id_colaborador
             WHERE i.estado IN ('PENDIENTE','REGISTRADA')
             ORDER BY i.fecha_inicio DESC, i.id_incidencia DESC
             LIMIT 5"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $db->query("SELECT COUNT(*) FROM incidencias WHERE estado IN ('PENDIENTE','REGISTRADA')");
        $count = (int) $countStmt->fetchColumn();

        if ($count === 0) {
            return [
                'intent' => 'admin_pending_incidents',
                'message' => 'No hay incidencias pendientes o registradas por revisar en este momento.',
            ];
        }

        $lines = ["Hay {$count} incidencias abiertas. Las más recientes:"];
        foreach ($rows as $row) {
            $date = date('d/m/Y', strtotime((string) $row['fecha_inicio']));
            $lines[] = '• ' . trim($row['nombres'] . ' ' . $row['apellidos']) . ' — ' . $row['tipo_nombre'] . " ({$date})";
        }

        return [
            'intent' => 'admin_pending_incidents',
            'message' => implode("\n", $lines),
        ];
    }

    private function adminCollaboratorSummary(): array
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado IN ('ACTIVO','HABILITADO') THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN estado = 'VACACIONES' THEN 1 ELSE 0 END) AS vacaciones,
                SUM(CASE WHEN estado = 'LICENCIA' THEN 1 ELSE 0 END) AS licencia,
                SUM(CASE WHEN estado = 'CESADO' THEN 1 ELSE 0 END) AS cesados
             FROM colaboradores"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'intent' => 'admin_collaborators',
            'message' => 'Colaboradores registrados: ' . (int) ($row['total'] ?? 0)
                . ".\nActivos/Habilitados: " . (int) ($row['activos'] ?? 0)
                . ' · Vacaciones: ' . (int) ($row['vacaciones'] ?? 0)
                . ' · Licencia: ' . (int) ($row['licencia'] ?? 0)
                . ' · Cesados: ' . (int) ($row['cesados'] ?? 0) . '.',
        ];
    }

    private function adminAreaSummary(): array
    {
        $stmt = Database::connection()->query(
            "SELECT COALESCE(a.nombre, 'Sin área') AS area, COUNT(*) AS total
             FROM colaboradores c
             LEFT JOIN areas a ON a.id_area = c.id_area
             WHERE c.estado IN ('ACTIVO','HABILITADO','VACACIONES','LICENCIA')
             GROUP BY a.id_area, a.nombre
             ORDER BY total DESC, area ASC
             LIMIT 8"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return ['intent' => 'admin_areas', 'message' => 'No encontré colaboradores asignados a áreas.'];
        }

        $lines = ['Personal vigente por área:'];
        foreach ($rows as $row) {
            $lines[] = '• ' . $row['area'] . ': ' . (int) $row['total'];
        }

        return ['intent' => 'admin_areas', 'message' => implode("\n", $lines)];
    }

    private function adminTopLateness(string $month): array
    {
        [$from, $to] = $this->monthRange($month);
        $stmt = Database::connection()->prepare(
            "SELECT c.nombres, c.apellidos, COUNT(*) AS tardanzas, COALESCE(SUM(m.minutos_tardanza),0) AS minutos
             FROM marcaciones m
             INNER JOIN colaboradores c ON c.id_colaborador = m.id_colaborador
             WHERE m.fecha BETWEEN :desde AND :hasta
               AND m.resultado_entrada = 'TARDANZA'
             GROUP BY c.id_colaborador, c.nombres, c.apellidos
             ORDER BY tardanzas DESC, minutos DESC
             LIMIT 5"
        );
        $stmt->execute(['desde' => $from, 'hasta' => $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [
                'intent' => 'admin_top_lateness',
                'message' => 'No hay tardanzas registradas para ' . $this->humanMonth($month) . '.',
            ];
        }

        $lines = ['Top de tardanzas de ' . $this->humanMonth($month) . ':'];
        foreach ($rows as $index => $row) {
            $lines[] = ($index + 1) . '. ' . trim($row['nombres'] . ' ' . $row['apellidos'])
                . ' — ' . (int) $row['tardanzas'] . ' tardanzas (' . (int) $row['minutos'] . ' min)';
        }

        return ['intent' => 'admin_top_lateness', 'message' => implode("\n", $lines)];
    }

    private function adminDailyForecast(string $text): array
    {
        $date = $this->explicitOrRelativeFutureDate($text);
        $service = new AiPredictionService();
        $response = $service->daily($date);

        if (!($response['ok'] ?? false)) {
            return [
                'intent' => 'admin_daily_forecast_error',
                'message' => 'No pude consultar la predicción: ' . ($response['error'] ?? 'servicio no disponible') . '.',
            ];
        }

        $d = $response['data'] ?? [];
        if (($d['dia_operativo'] ?? true) === false) {
            return [
                'intent' => 'admin_daily_forecast',
                'message' => 'La fecha ' . ($d['fecha'] ?? $date ?? '') . ' no tiene personal programado, por lo que no se genera una predicción operativa.',
            ];
        }

        try {
            (new PredictionHistoryService())->saveDaily($d);
        } catch (\Throwable) {
            // La predicción puede mostrarse aunque el historial no pueda guardarse.
        }

        $status = (string) ($d['estado_modelo'] ?? 'EXPERIMENTAL');
        return [
            'intent' => 'admin_daily_forecast',
            'message' => 'Predicción para ' . $this->humanDate((string) ($d['fecha'] ?? date('Y-m-d')))
                . ":\n• Programados: " . (int) ($d['programados'] ?? 0)
                . "\n• Presentes estimados: " . (int) ($d['presentes_estimados'] ?? 0)
                . "\n• Ausentes estimados: " . (int) ($d['ausentes_estimados'] ?? 0)
                . "\n• Asistencia estimada: " . number_format((float) ($d['indice_asistencia_estimado'] ?? 0), 2) . '%'
                . "\n• Modelo: " . (string) ($d['modelo'] ?? 'desconocido') . " ({$status})"
                . "\n\nNota: es una estimación del modelo, no un resultado real.",
        ];
    }

    private function adminWeeklyForecast(): array
    {
        $service = new AiPredictionService();
        $response = $service->weekly(7);

        if (!($response['ok'] ?? false)) {
            return [
                'intent' => 'admin_weekly_forecast_error',
                'message' => 'No pude consultar la predicción semanal: ' . ($response['error'] ?? 'servicio no disponible') . '.',
            ];
        }

        $d = $response['data'] ?? [];
        try {
            (new PredictionHistoryService())->saveWeekly($d);
        } catch (\Throwable) {
            // La predicción puede mostrarse aunque el historial no pueda guardarse.
        }

        return [
            'intent' => 'admin_weekly_forecast',
            'message' => 'Predicción de los próximos ' . (int) ($d['dias_operativos'] ?? 0) . " días operativos:\n"
                . '• Asistencia promedio estimada: ' . number_format((float) ($d['asistencia_promedio_estimada'] ?? 0), 2) . "%\n"
                . '• Programados acumulados: ' . (int) ($d['programados_acumulados'] ?? 0) . "\n"
                . '• Presentes estimados: ' . (int) ($d['presentes_estimados_acumulados'] ?? 0) . "\n"
                . '• Ausentes estimados: ' . (int) ($d['ausentes_estimados_acumulados'] ?? 0)
                . "\n\nLa consulta semanal puede tardar algunos segundos porque el servicio calcula varios días.",
        ];
    }

    private function adminModelStatus(): array
    {
        $service = new AiPredictionService();
        $response = $service->modelStatus();

        if (!($response['ok'] ?? false)) {
            return [
                'intent' => 'admin_model_status_error',
                'message' => 'No pude consultar el estado del modelo: ' . ($response['error'] ?? 'servicio no disponible') . '.',
            ];
        }

        $d = $response['data'] ?? [];
        return [
            'intent' => 'admin_model_status',
            'message' => 'Modelo predictivo: ' . (string) ($d['modelo'] ?? 'desconocido')
                . "\nEstado: " . (string) ($d['estado_modelo'] ?? 'EXPERIMENTAL')
                . "\nVersión: " . (string) ($d['version_modelo'] ?? 'desconocida')
                . "\nMAE holdout: " . number_format((float) ($d['mae_holdout'] ?? 0), 3)
                . "\nR² holdout: " . number_format((float) ($d['r2_holdout'] ?? 0), 3)
                . "\nValidación temporal supera baseline: " . (($d['validacion_temporal_supera_baseline'] ?? false) ? 'Sí' : 'No')
                . "\n\nEl estado EXPERIMENTAL indica que el resultado debe interpretarse como apoyo técnico y no como certeza.",
        ];
    }

    private function adminPredictionHistorySummary(): array
    {
        $historyService = new PredictionHistoryService();
        $history = $historyService->dailyHistory(10);
        $summary = $historyService->summary($history);

        if ($history === []) {
            return [
                'intent' => 'admin_prediction_history',
                'message' => 'Todavía no hay predicciones diarias guardadas. Abre Workforce AI o solicita una predicción para comenzar el historial.',
            ];
        }

        $errorText = $summary['error_medio_pp'] === null
            ? 'Aún no hay predicciones pasadas suficientes para calcular el error real.'
            : 'Error medio observado en predicciones ya cerradas: ' . number_format((float) $summary['error_medio_pp'], 2) . ' puntos porcentuales.';

        $lines = [
            'Seguimiento de predicciones:',
            '• Pronósticos visibles: ' . (int) $summary['guardados'],
            '• Evaluados contra asistencia real: ' . (int) $summary['evaluados'],
            '• Pendientes/en curso: ' . (int) $summary['pendientes'],
            '• ' . $errorText,
            '',
            'Últimos registros:',
        ];

        foreach (array_slice($history, 0, 5) as $row) {
            $line = '• ' . $this->humanDate((string) $row['fecha'])
                . ' — estimado ' . number_format((float) ($row['prediccion'] ?? 0), 2) . '%';

            if (($row['estado_comparacion'] ?? '') === 'EVALUADO') {
                $line .= ' · real ' . number_format((float) ($row['real'] ?? 0), 2) . '%'
                    . ' · error ' . number_format((float) ($row['error_absoluto_pp'] ?? 0), 2) . ' pp';
            } elseif (($row['estado_comparacion'] ?? '') === 'EN_CURSO') {
                $line .= ' · jornada en curso';
            } else {
                $line .= ' · pendiente';
            }
            $lines[] = $line;
        }

        return [
            'intent' => 'admin_prediction_history',
            'message' => implode("\n", $lines),
        ];
    }

    private function adminPredictionComparison(string $text): array
    {
        $date = $this->resolveDate($text);
        $row = (new PredictionHistoryService())->comparisonForDate($date);

        if ($row === null) {
            return [
                'intent' => 'admin_prediction_comparison',
                'message' => 'No encuentro una predicción diaria guardada para ' . $this->humanDate($date) . '.',
            ];
        }

        $estimated = number_format((float) ($row['prediccion'] ?? 0), 2) . '%';
        $state = (string) ($row['estado_comparacion'] ?? 'FUTURO');

        if ($state === 'EVALUADO') {
            return [
                'intent' => 'admin_prediction_comparison',
                'message' => 'Comparación del ' . $this->humanDate($date)
                    . ":\n• Predicción: {$estimated}"
                    . "\n• Asistencia real: " . number_format((float) ($row['real'] ?? 0), 2) . '%'
                    . "\n• Diferencia absoluta: " . number_format((float) ($row['error_absoluto_pp'] ?? 0), 2) . ' puntos porcentuales.'
                    . "\n\nLa diferencia sirve para evaluar el modelo; no significa que la predicción deba coincidir exactamente con la realidad.",
            ];
        }

        if ($state === 'EN_CURSO') {
            return [
                'intent' => 'admin_prediction_comparison',
                'message' => 'La predicción para hoy es ' . $estimated . ', pero la jornada todavía está en curso. La comparación se cerrará cuando la fecha sea histórica.',
            ];
        }

        if ($state === 'SIN_DATOS') {
            return [
                'intent' => 'admin_prediction_comparison',
                'message' => 'Existe una predicción de ' . $estimated . ' para ' . $this->humanDate($date) . ', pero no hay datos reales suficientes para compararla.',
            ];
        }

        return [
            'intent' => 'admin_prediction_comparison',
            'message' => 'La predicción guardada para ' . $this->humanDate($date) . ' es ' . $estimated . '. Todavía es una fecha futura, así que no existe un resultado real para compararla.',
        ];
    }

    private function personalMonthlySummary(int $collaboratorId, string $month, bool $hoursOnly = false): array
    {
        [$from, $to] = $this->monthRange($month);
        $stmt = Database::connection()->prepare(
            "SELECT
                COUNT(*) AS registros,
                SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) AS asistencias,
                SUM(CASE WHEN resultado_entrada = 'PUNTUAL' THEN 1 ELSE 0 END) AS puntuales,
                SUM(CASE WHEN resultado_entrada = 'TARDANZA' THEN 1 ELSE 0 END) AS tardanzas,
                COALESCE(SUM(minutos_tardanza),0) AS minutos_tardanza,
                COALESCE(SUM(minutos_trabajados),0) AS minutos_trabajados
             FROM marcaciones
             WHERE id_colaborador = :colaborador
               AND fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $hours = round(((int) ($row['minutos_trabajados'] ?? 0)) / 60, 1);

        if ($hoursOnly) {
            return [
                'intent' => 'personal_hours',
                'message' => 'En ' . $this->humanMonth($month) . " tienes {$hours} horas trabajadas registradas.\nTardanza acumulada: " . (int) ($row['minutos_tardanza'] ?? 0) . ' minutos.',
            ];
        }

        return [
            'intent' => 'personal_month_summary',
            'message' => 'Tu resumen de ' . $this->humanMonth($month) . ":\n"
                . '• Días con asistencia registrada: ' . (int) ($row['asistencias'] ?? 0) . "\n"
                . '• Puntuales: ' . (int) ($row['puntuales'] ?? 0) . "\n"
                . '• Tardanzas: ' . (int) ($row['tardanzas'] ?? 0) . "\n"
                . '• Minutos de tardanza: ' . (int) ($row['minutos_tardanza'] ?? 0) . "\n"
                . "• Horas trabajadas registradas: {$hours}",
        ];
    }

    private function personalLateness(int $collaboratorId, string $month): array
    {
        [$from, $to] = $this->monthRange($month);
        $stmt = Database::connection()->prepare(
            "SELECT fecha, minutos_tardanza
             FROM marcaciones
             WHERE id_colaborador = :colaborador
               AND fecha BETWEEN :desde AND :hasta
               AND resultado_entrada = 'TARDANZA'
             ORDER BY fecha DESC
             LIMIT 5"
        );
        $stmt->execute(['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(minutos_tardanza),0) AS minutos
             FROM marcaciones
             WHERE id_colaborador = :colaborador
               AND fecha BETWEEN :desde AND :hasta
               AND resultado_entrada = 'TARDANZA'"
        );
        $countStmt->execute(['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to]);
        $summary = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $lines = ['En ' . $this->humanMonth($month) . ' tienes ' . (int) ($summary['total'] ?? 0) . ' tardanzas (' . (int) ($summary['minutos'] ?? 0) . ' min acumulados).'];
        if ($rows) {
            $lines[] = 'Últimas tardanzas:';
            foreach ($rows as $row) {
                $lines[] = '• ' . date('d/m/Y', strtotime((string) $row['fecha'])) . ' — ' . (int) $row['minutos_tardanza'] . ' min';
            }
        }

        return ['intent' => 'personal_lateness', 'message' => implode("\n", $lines)];
    }

    private function personalAbsences(int $collaboratorId, string $month): array
    {
        [$from, $to] = $this->monthRange($month);
        $stmt = Database::connection()->prepare(
            "SELECT i.fecha_inicio, t.nombre AS tipo_nombre, i.estado
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
               AND DATE(i.fecha_inicio) BETWEEN :desde AND :hasta
               AND t.codigo IN ('FALTA_INJUSTIFICADA','FALTA_JUSTIFICADA')
               AND i.estado <> 'ANULADA'
             ORDER BY i.fecha_inicio DESC
             LIMIT 5"
        );
        $stmt->execute(['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = Database::connection()->prepare(
            "SELECT COUNT(*)
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
               AND DATE(i.fecha_inicio) BETWEEN :desde AND :hasta
               AND t.codigo IN ('FALTA_INJUSTIFICADA','FALTA_JUSTIFICADA')
               AND i.estado <> 'ANULADA'"
        );
        $countStmt->execute(['colaborador' => $collaboratorId, 'desde' => $from, 'hasta' => $to]);
        $count = (int) $countStmt->fetchColumn();

        $lines = ['En ' . $this->humanMonth($month) . " tienes {$count} faltas registradas."];
        foreach ($rows as $row) {
            $lines[] = '• ' . date('d/m/Y', strtotime((string) $row['fecha_inicio'])) . ' — ' . $row['tipo_nombre'] . ' (' . $row['estado'] . ')';
        }

        return ['intent' => 'personal_absences', 'message' => implode("\n", $lines)];
    }

    private function personalIncidents(int $collaboratorId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT i.fecha_inicio, t.nombre AS tipo_nombre, i.estado
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
             ORDER BY i.creado_en DESC, i.id_incidencia DESC
             LIMIT 6"
        );
        $stmt->execute(['colaborador' => $collaboratorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return ['intent' => 'personal_incidents', 'message' => 'No tienes incidencias registradas.'];
        }

        $lines = ['Tus incidencias más recientes:'];
        foreach ($rows as $row) {
            $lines[] = '• ' . date('d/m/Y', strtotime((string) $row['fecha_inicio'])) . ' — ' . $row['tipo_nombre'] . ' (' . $row['estado'] . ')';
        }

        return ['intent' => 'personal_incidents', 'message' => implode("\n", $lines)];
    }

    private function personalToday(int $collaboratorId): array
    {
        $today = date('Y-m-d');
        $stmt = Database::connection()->prepare(
            "SELECT hora_entrada, hora_salida, resultado_entrada, resultado_salida, minutos_tardanza, minutos_trabajados
             FROM marcaciones
             WHERE id_colaborador = :colaborador AND fecha = :fecha
             LIMIT 1"
        );
        $stmt->execute(['colaborador' => $collaboratorId, 'fecha' => $today]);
        $mark = $stmt->fetch(PDO::FETCH_ASSOC);

        $incStmt = Database::connection()->prepare(
            "SELECT t.nombre AS tipo_nombre, i.estado
             FROM incidencias i
             INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia
             WHERE i.id_colaborador = :colaborador
               AND DATE(i.fecha_inicio) <= :fecha_inicio
               AND DATE(COALESCE(i.fecha_fin, i.fecha_inicio)) >= :fecha_fin
               AND i.estado IN ('APROBADA','REGISTRADA','PENDIENTE')
             ORDER BY i.id_incidencia DESC
             LIMIT 1"
        );
        $incStmt->execute(['colaborador' => $collaboratorId, 'fecha_inicio' => $today, 'fecha_fin' => $today]);
        $incident = $incStmt->fetch(PDO::FETCH_ASSOC);

        if (!$mark && !$incident) {
            return [
                'intent' => 'personal_today',
                'message' => 'Hoy todavía no tienes una marcación ni una incidencia registrada en el sistema.',
            ];
        }

        $lines = ['Tu estado de hoy:'];
        if ($mark) {
            $entry = $mark['hora_entrada'] ? date('H:i', strtotime((string) $mark['hora_entrada'])) : 'Sin entrada';
            $exit = $mark['hora_salida'] ? date('H:i', strtotime((string) $mark['hora_salida'])) : 'Sin salida';
            $lines[] = "• Entrada: {$entry} ({$mark['resultado_entrada']})";
            $lines[] = "• Salida: {$exit}";
            $lines[] = '• Tardanza: ' . (int) ($mark['minutos_tardanza'] ?? 0) . ' min';
        }
        if ($incident) {
            $lines[] = '• Incidencia: ' . $incident['tipo_nombre'] . ' (' . $incident['estado'] . ')';
        }

        return ['intent' => 'personal_today', 'message' => implode("\n", $lines)];
    }

    private function personalLastMark(int $collaboratorId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT fecha, hora_entrada, hora_salida, resultado_entrada, minutos_tardanza
             FROM marcaciones
             WHERE id_colaborador = :colaborador
             ORDER BY fecha DESC, id_marcacion DESC
             LIMIT 1"
        );
        $stmt->execute(['colaborador' => $collaboratorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['intent' => 'personal_last_mark', 'message' => 'Todavía no tienes marcaciones registradas.'];
        }

        $entry = $row['hora_entrada'] ? date('H:i', strtotime((string) $row['hora_entrada'])) : 'Sin entrada';
        $exit = $row['hora_salida'] ? date('H:i', strtotime((string) $row['hora_salida'])) : 'Sin salida';

        return [
            'intent' => 'personal_last_mark',
            'message' => 'Tu última marcación fue el ' . date('d/m/Y', strtotime((string) $row['fecha']))
                . ".\nEntrada: {$entry} · Salida: {$exit}.\nResultado de entrada: " . (string) $row['resultado_entrada']
                . ' · Tardanza: ' . (int) $row['minutos_tardanza'] . ' min.',
        ];
    }

    private function adminHelp(): array
    {
        return [
            'intent' => 'admin_help',
            'message' => "Hola. Soy el Asistente Workforce. Como Administrador puedo consultar datos globales en vivo y el módulo predictivo.\n\nPuedes preguntarme, por ejemplo:\n• Resumen de asistencia de hoy\n• Lista de los que asistieron hoy\n• ¿Cuántas tardanzas hubo ayer?\n• ¿Cuántos ausentes hay hoy?\n• Incidencias pendientes\n• Colaboradores activos\n• Personal por área\n• Top tardanzas del mes\n• Predicción de asistencia\n• Predicción semanal\n• Historial de predicciones\n• ¿Fue correcta la predicción de ayer?\n• Estado del modelo predictivo\n• ¿Cómo activo Workforce AI?",
        ];
    }

    private function personalHelp(): array
    {
        return [
            'intent' => 'personal_help',
            'message' => "Hola. Soy tu Asistente Workforce. Por seguridad solo puedo consultar información vinculada a tu propio colaborador.\n\nPuedes preguntarme:\n• Mi asistencia este mes\n• Mis tardanzas\n• Mis faltas\n• Mis incidencias\n• Mi estado de hoy\n• Mi última marcación\n• Mis horas trabajadas",
        ];
    }

    private function systemOverview(): array
    {
        return [
            'intent' => 'system_overview',
            'message' => "Workforce360 AI integra gestión de colaboradores, áreas, horarios, marcaciones, tardanzas, salidas anticipadas, incidencias, solicitudes, dashboard, KPIs, reportes, auditoría y analítica predictiva.\n\nEl sistema PHP gestiona la operación y MySQL almacena los datos. FastAPI expone los pronósticos generados por el modelo de Machine Learning.",
        ];
    }

    private function resolveDate(string $text): string
    {
        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $text, $m)) {
            return $this->safeDate("{$m[1]}-{$m[2]}-{$m[3]}");
        }

        if (preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](20\d{2})\b/', $text, $m)) {
            return $this->safeDate(sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]));
        }

        if (str_contains($text, 'ayer')) {
            return date('Y-m-d', strtotime('-1 day'));
        }

        if (str_contains($text, 'manana')) {
            return date('Y-m-d', strtotime('+1 day'));
        }

        return date('Y-m-d');
    }

    private function explicitOrRelativeFutureDate(string $text): ?string
    {
        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $text, $m)) {
            return $this->safeDate("{$m[1]}-{$m[2]}-{$m[3]}");
        }

        if (preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](20\d{2})\b/', $text, $m)) {
            return $this->safeDate(sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]));
        }

        if (str_contains($text, 'manana')) {
            return date('Y-m-d', strtotime('+1 day'));
        }

        return null;
    }

    private function resolveMonth(string $text): string
    {
        if (preg_match('/\b(20\d{2})-(0[1-9]|1[0-2])\b/', $text, $m)) {
            return $m[1] . '-' . $m[2];
        }

        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
            'noviembre' => 11, 'diciembre' => 12,
        ];

        foreach ($months as $name => $number) {
            if (str_contains($text, $name)) {
                $year = date('Y');
                if (preg_match('/\b(20\d{2})\b/', $text, $yearMatch)) {
                    $year = $yearMatch[1];
                }
                return sprintf('%04d-%02d', (int) $year, $number);
            }
        }

        return date('Y-m');
    }

    private function monthRange(string $month): array
    {
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        return [$from, $to];
    }

    private function safeDate(string $date): string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : date('Y-m-d');
    }

    private function humanDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }

    private function humanLongDate(string $date): string
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: new DateTimeImmutable($date);
        $days = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
        ];
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'setiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $dayName = $days[(int) $dt->format('N')] ?? '';
        $monthName = $months[(int) $dt->format('n')] ?? '';

        return $dayName . ', ' . (int) $dt->format('j') . ' de ' . $monthName . ' de ' . $dt->format('Y');
    }

    private function humanMonth(string $month): string
    {
        $names = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        return ($names[$monthNumber] ?? $month) . ' de ' . $year;
    }

    private function normalize(string $text): string
    {
        $text = trim($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', '¿' => '', '?' => '', '¡' => '', '!' => '',
        ]);
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }
        return false;
    }
}
