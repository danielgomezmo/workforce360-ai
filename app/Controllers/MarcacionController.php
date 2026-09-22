<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Marcacion;
use App\Models\Incidencia;
use App\Services\AttendanceRuleEngine;
use DateTimeImmutable;
use Throwable;

class MarcacionController extends Controller
{
    public function mine(): void
    {
        Auth::requireLogin();
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            $this->view('marcaciones/mine', [
                'colaborador' => null,
                'horario' => null,
                'marcacion' => null,
                'historial' => [],
                'canMark' => false,
                'blockReason' => 'Tu cuenta inició sesión, pero todavía no está vinculada a un colaborador. Un Administrador o RRHH debe abrir tu registro en Colaboradores y completar la sección “Acceso para marcar asistencia”.',
            ]);
            return;
        }

        $today = date('Y-m-d');
        $open = Marcacion::openForCollaborator((int) $collaborator['id_colaborador']);
        $mark = $open ?: Marcacion::findByCollaboratorAndDate((int) $collaborator['id_colaborador'], $today);
        $workDate = $open ? (string) $open['fecha'] : $today;
        $schedule = Marcacion::scheduleForDate((int) $collaborator['id_colaborador'], $workDate);
        [$canMark, $reason] = $this->eligibility($collaborator, $schedule, $workDate, $open !== null);

        $this->view('marcaciones/mine', [
            'colaborador' => $collaborator,
            'horario' => $schedule,
            'marcacion' => $mark,
            'historial' => Marcacion::recentForCollaborator((int) $collaborator['id_colaborador']),
            'canMark' => $canMark,
            'blockReason' => $reason,
        ]);
    }


    public function history(): void
    {
        Auth::requireLogin();
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }

        $from = trim((string) ($_GET['desde'] ?? date('Y-m-01')));
        $to = trim((string) ($_GET['hasta'] ?? date('Y-m-d')));
        $status = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $allowed = ['', 'PUNTUAL', 'TARDANZA', 'PARCIAL', 'COMPLETA', 'PENDIENTE'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $id = (int) $collaborator['id_colaborador'];
        $this->view('marcaciones/history', [
            'colaborador' => $collaborator,
            'desde' => $from,
            'hasta' => $to,
            'estadoFiltro' => $status,
            'historial' => Marcacion::historyForCollaborator($id, $from, $to, $status),
            'resumen' => Marcacion::historySummaryForCollaborator($id, $from, $to),
        ]);
    }

    public function entry(): void
    {
        Auth::requireLogin();
        $this->validateCsrf('mi-asistencia');
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            flash('error', 'Tu usuario no está vinculado a un colaborador.');
            $this->redirect('mi-asistencia');
        }

        $now = new DateTimeImmutable('now');
        $date = $now->format('Y-m-d');
        $schedule = Marcacion::scheduleForDate((int) $collaborator['id_colaborador'], $date);
        [$canMark, $reason] = $this->eligibility($collaborator, $schedule, $date, false);
        if (!$canMark || !$schedule) {
            flash('error', $reason ?: 'No existe un horario disponible para hoy.');
            $this->redirect('mi-asistencia');
        }
        if (Marcacion::openForCollaborator((int) $collaborator['id_colaborador'])) {
            flash('error', 'Ya tienes una jornada abierta. Registra primero tu salida.');
            $this->redirect('mi-asistencia');
        }
        if (Marcacion::findByCollaboratorAndDate((int) $collaborator['id_colaborador'], $date)) {
            flash('error', 'Ya existe una marcación registrada para la jornada de hoy.');
            $this->redirect('mi-asistencia');
        }

        try {
            $evaluation = AttendanceRuleEngine::evaluateEntry($schedule, $date, $now);
            $ip = $this->clientIp();
            $id = Marcacion::createEntry(
                (int) $collaborator['id_colaborador'],
                (int) $schedule['id_horario'],
                $date,
                $now->format('Y-m-d H:i:s'),
                $ip,
                $evaluation,
                (float) ($schedule['horas_descanso'] ?? 0)
            );
            if ($evaluation['resultado'] === 'TARDANZA') {
                Marcacion::createAutomaticIncident(
                    (int) $collaborator['id_colaborador'],
                    'TARDANZA',
                    $now->format('Y-m-d H:i:s'),
                    null,
                    'Tardanza automática de ' . $evaluation['minutos_tardanza'] . ' minuto(s).',
                    (int) Auth::id()
                );
            }
            Audit::log('MARCAR_INGRESO', 'marcaciones', $id, null, [
                'id_colaborador' => (int) $collaborator['id_colaborador'],
                'hora_entrada' => $now->format('Y-m-d H:i:s'),
                'resultado_entrada' => $evaluation['resultado'],
                'minutos_tardanza' => $evaluation['minutos_tardanza'],
            ], null, Auth::id());

            $message = $evaluation['resultado'] === 'TARDANZA'
                ? 'Ingreso registrado. Se detectó una tardanza de ' . $evaluation['minutos_tardanza'] . ' minuto(s).'
                : 'Ingreso registrado correctamente. Marcación puntual.';
            flash('success', $message);
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar el ingreso.');
        }
        $this->redirect('mi-asistencia');
    }

    public function exit(): void
    {
        Auth::requireLogin();
        $this->validateCsrf('mi-asistencia');
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            flash('error', 'Tu usuario no está vinculado a un colaborador.');
            $this->redirect('mi-asistencia');
        }

        $mark = Marcacion::openForCollaborator((int) $collaborator['id_colaborador']);
        if (!$mark) {
            flash('error', 'No existe un ingreso abierto para registrar la salida.');
            $this->redirect('mi-asistencia');
        }

        try {
            $now = new DateTimeImmutable('now');
            $evaluation = AttendanceRuleEngine::evaluateExit($mark, $now);
            $before = $mark;
            Marcacion::finishExit((int) $mark['id_marcacion'], $now->format('Y-m-d H:i:s'), $this->clientIp(), $evaluation);
            if ($evaluation['resultado'] === 'ANTICIPADA') {
                Marcacion::createAutomaticIncident(
                    (int) $collaborator['id_colaborador'],
                    'SALIDA_ANTICIPADA',
                    $now->format('Y-m-d H:i:s'),
                    null,
                    'Salida anticipada automática de ' . $evaluation['minutos_salida_anticipada'] . ' minuto(s).',
                    (int) Auth::id()
                );
            }
            Audit::log('MARCAR_SALIDA', 'marcaciones', (int) $mark['id_marcacion'], $before, [
                'hora_salida' => $now->format('Y-m-d H:i:s'),
                'resultado_salida' => $evaluation['resultado'],
                'minutos_salida_anticipada' => $evaluation['minutos_salida_anticipada'],
                'minutos_trabajados' => $evaluation['minutos_trabajados'],
            ], null, Auth::id());

            $message = $evaluation['resultado'] === 'ANTICIPADA'
                ? 'Salida registrada. Se detectó salida anticipada de ' . $evaluation['minutos_salida_anticipada'] . ' minuto(s).'
                : 'Salida registrada correctamente. Jornada cerrada.';
            flash('success', $message);
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar la salida.');
        }
        $this->redirect('mi-asistencia');
    }

    public function index(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR']);
        $date = trim((string) ($_GET['fecha'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $supervisorId = null;
        if (Auth::hasRole('SUPERVISOR') && !Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH'])) {
            $supervisorId = Auth::collaboratorId();
            if (!$supervisorId) {
                http_response_code(403);
                require BASE_PATH . '/app/Views/errors/403.php';
                return;
            }
        }

        $this->view('marcaciones/index', [
            'fecha' => $date,
            'q' => $q,
            'marcaciones' => Marcacion::listForManagement($date, $q, $supervisorId),
            'resumen' => Marcacion::dailySummary($date, $supervisorId),
        ]);
    }

    private function linkedCollaborator(): ?array
    {
        $id = Auth::id();
        return $id ? Marcacion::collaboratorForUser((int) $id) : null;
    }

    private function eligibility(array $collaborator, ?array $schedule, string $date, bool $hasOpenShift): array
    {
        if ((string) $collaborator['estado'] === 'CESADO' || (!empty($collaborator['fecha_cese']) && $date > $collaborator['fecha_cese'])) {
            return [false, 'El colaborador está cesado y no puede registrar nuevas marcaciones.'];
        }
        if ((string) $collaborator['estado'] !== 'ACTIVO' && !$hasOpenShift) {
            return [false, 'Solo los colaboradores en estado ACTIVO pueden iniciar una nueva jornada.'];
        }
        if ($date < (string) $collaborator['fecha_ingreso']) {
            return [false, 'La fecha de marcación es anterior a la fecha de ingreso del colaborador.'];
        }
        if (!$schedule) {
            return [false, 'No existe un horario vigente asignado para esta fecha.'];
        }
        if (!$hasOpenShift && !Marcacion::isScheduledDay($schedule, $date)) {
            return [false, 'Hoy no corresponde a un día laborable según tu horario asignado.'];
        }
        if (!$hasOpenShift) {
            $incident = Incidencia::blockingForDate((int) $collaborator['id_colaborador'], $date, $schedule);
            if ($incident) {
                return [false, 'No corresponde registrar asistencia: tienes ' . strtolower((string) $incident['tipo_nombre']) . ' aprobada para esta fecha.'];
            }
        }
        return [true, null];
    }

    private function validateCsrf(string $redirect): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró. Intenta nuevamente.');
            $this->redirect($redirect);
        }
    }

    private function clientIp(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}
