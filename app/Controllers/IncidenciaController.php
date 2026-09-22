<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Incidencia;
use App\Models\Marcacion;
use DateTimeImmutable;
use Throwable;

class IncidenciaController extends Controller
{
    public function mine(): void
    {
        Auth::requireLogin();
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $status = strtoupper(trim((string)($_GET['estado'] ?? '')));
        if (!in_array($status, ['', 'PENDIENTE','REGISTRADA','APROBADA','RECHAZADA','ANULADA'], true)) {
            $status = '';
        }
        $this->view('incidencias/mine', [
            'colaborador' => $collaborator,
            'tipos' => Incidencia::requestTypes(),
            'solicitudes' => Incidencia::own((int)$collaborator['id_colaborador'], $status),
            'estadoFiltro' => $status,
        ]);
    }

    public function storeMine(): void
    {
        Auth::requireLogin();
        $this->validateCsrf('mis-solicitudes');
        $collaborator = $this->linkedCollaborator();
        if (!$collaborator) {
            flash('error', 'Tu cuenta no está vinculada a un colaborador.');
            $this->redirect('mis-solicitudes');
        }
        try {
            $data = $this->payload((int)$collaborator['id_colaborador']);
            $id = Incidencia::create($data);
            Audit::log('SOLICITAR_INCIDENCIA', 'incidencias', $id, null, $data, null, Auth::id());
            flash('success', 'Solicitud registrada correctamente. Quedó pendiente de revisión.');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar la solicitud.');
        }
        $this->redirect('mis-solicitudes');
    }

    public function index(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR','RRHH','SUPERVISOR']);
        $status = strtoupper(trim((string)($_GET['estado'] ?? '')));
        if (!in_array($status, ['', 'PENDIENTE','REGISTRADA','APROBADA','RECHAZADA','ANULADA'], true)) {
            $status = '';
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $supervisorId = $this->supervisorScope();
        $this->view('incidencias/index', [
            'incidencias' => Incidencia::listForManagement($status, $q, $supervisorId),
            'resumen' => Incidencia::summaryForManagement($supervisorId),
            'estadoFiltro' => $status,
            'q' => $q,
        ]);
    }

    public function create(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR','RRHH','SUPERVISOR']);
        $supervisorId = $this->supervisorScope();
        $this->view('incidencias/form', [
            'tipos' => Incidencia::requestTypes(),
            'colaboradores' => Incidencia::collaboratorsForSelector($supervisorId),
        ]);
    }

    public function store(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR','RRHH','SUPERVISOR']);
        $this->validateCsrf('incidencias/nueva');
        $collaboratorId = (int)($_POST['id_colaborador'] ?? 0);
        if ($collaboratorId <= 0) {
            flash('error', 'Selecciona un colaborador.');
            $this->redirect('incidencias/nueva');
        }
        $this->assertManagementAccessToCollaborator($collaboratorId);
        try {
            $data = $this->payload($collaboratorId);
            $id = Incidencia::create($data);
            Audit::log('REGISTRAR_INCIDENCIA', 'incidencias', $id, null, $data, null, Auth::id());
            flash('success', 'Incidencia registrada y enviada a revisión.');
            $this->redirect('incidencias');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar la incidencia.');
            $this->redirect('incidencias/nueva');
        }
    }

    public function review(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR','RRHH','SUPERVISOR']);
        $this->validateCsrf('incidencias');
        $id = (int)($_POST['id_incidencia'] ?? 0);
        $state = strtoupper(trim((string)($_POST['decision'] ?? '')));
        $observation = trim((string)($_POST['observacion_revision'] ?? '')) ?: null;
        $incident = Incidencia::find($id);
        if (!$incident) {
            flash('error', 'Incidencia no encontrada.');
            $this->redirect('incidencias');
        }
        $this->assertManagementAccessToCollaborator((int)$incident['id_colaborador']);
        try {
            $before = $incident;
            $updated = Incidencia::review($id, $state, (int)Auth::id(), $observation);
            Audit::log('REVISAR_INCIDENCIA', 'incidencias', $id, $before, $updated, $observation, Auth::id());
            flash('success', $state === 'APROBADA' ? 'Incidencia aprobada correctamente.' : 'Incidencia rechazada correctamente.');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo revisar la incidencia.');
        }
        $this->redirect('incidencias');
    }

    public function annul(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR','RRHH']);
        $this->validateCsrf('incidencias');
        $id = (int)($_POST['id_incidencia'] ?? 0);
        $observation = trim((string)($_POST['observacion_revision'] ?? '')) ?: 'Anulada desde gestión de incidencias.';
        $incident = Incidencia::find($id);
        if (!$incident) {
            flash('error', 'Incidencia no encontrada.');
            $this->redirect('incidencias');
        }
        try {
            $updated = Incidencia::annul($id, (int)Auth::id(), $observation);
            Audit::log('ANULAR_INCIDENCIA', 'incidencias', $id, $incident, $updated, $observation, Auth::id());
            flash('success', 'Incidencia anulada correctamente.');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo anular la incidencia.');
        }
        $this->redirect('incidencias');
    }

    private function payload(int $collaboratorId): array
    {
        $code = strtoupper(trim((string)($_POST['tipo_codigo'] ?? '')));
        $type = Incidencia::typeByCode($code);
        $allowedCodes = array_column(Incidencia::requestTypes(), 'codigo');
        if (!$type || !in_array($code, $allowedCodes, true)) {
            throw new \RuntimeException('Selecciona un tipo de solicitud válido.');
        }

        $startRaw = trim((string)($_POST['fecha_inicio'] ?? ''));
        $endRaw = trim((string)($_POST['fecha_fin'] ?? ''));
        if ($startRaw === '') {
            throw new \RuntimeException('Indica la fecha de inicio.');
        }
        $withTime = $code === 'PERMISO';
        $start = $this->normalizeDate($startRaw, $withTime, false);
        $end = $endRaw !== '' ? $this->normalizeDate($endRaw, $withTime, true) : $start;
        if (new DateTimeImmutable($end) < new DateTimeImmutable($start)) {
            throw new \RuntimeException('La fecha final no puede ser anterior a la fecha inicial.');
        }

        $reason = trim((string)($_POST['motivo'] ?? ''));
        if ($reason === '' || mb_strlen($reason) < 4) {
            throw new \RuntimeException('Escribe un motivo de al menos 4 caracteres.');
        }
        if (mb_strlen($reason) > 255) {
            throw new \RuntimeException('El motivo no puede superar 255 caracteres.');
        }
        $comment = trim((string)($_POST['comentario'] ?? '')) ?: null;
        $document = $this->storeDocument($_FILES['documento'] ?? null);

        return [
            'id_colaborador' => $collaboratorId,
            'id_tipo_incidencia' => (int)$type['id_tipo_incidencia'],
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
            'motivo' => $reason,
            'comentario' => $comment,
            'documento_path' => $document,
            'registrado_por' => (int)Auth::id(),
        ];
    }

    private function normalizeDate(string $value, bool $withTime, bool $end): string
    {
        if ($withTime) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value);
            if (!$dt || $dt->format('Y-m-d\\TH:i') !== $value) {
                throw new \RuntimeException('Fecha u hora inválida.');
            }
            return $dt->format('Y-m-d H:i:s');
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            throw new \RuntimeException('Fecha inválida.');
        }
        return $dt->format('Y-m-d') . ($end ? ' 23:59:59' : ' 00:00:00');
    }

    private function storeDocument(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No se pudo cargar el documento adjunto.');
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException('El documento no puede superar 5 MB.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Solo se permiten documentos PDF, JPG o PNG.');
        }
        $relativeDir = 'uploads/incidencias/' . date('Y/m');
        $absoluteDir = BASE_PATH . '/public/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new \RuntimeException('No se pudo preparar la carpeta de documentos.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($tmp, $absoluteDir . '/' . $filename)) {
            throw new \RuntimeException('No se pudo guardar el documento adjunto.');
        }
        return $relativeDir . '/' . $filename;
    }

    private function linkedCollaborator(): ?array
    {
        $id = Auth::id();
        return $id ? Marcacion::collaboratorForUser((int)$id) : null;
    }

    private function supervisorScope(): ?int
    {
        if (Auth::hasRole('SUPERVISOR') && !Auth::hasAnyRole(['ADMINISTRADOR','RRHH'])) {
            $id = Auth::collaboratorId();
            if (!$id) {
                http_response_code(403);
                require BASE_PATH . '/app/Views/errors/403.php';
                exit;
            }
            return $id;
        }
        return null;
    }

    private function assertManagementAccessToCollaborator(int $collaboratorId): void
    {
        $supervisorId = $this->supervisorScope();
        if ($supervisorId === null) {
            return;
        }
        $allowed = array_column(Incidencia::collaboratorsForSelector($supervisorId), 'id_colaborador');
        if (!in_array($collaboratorId, array_map('intval', $allowed), true)) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }

    private function validateCsrf(string $redirect): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró. Intenta nuevamente.');
            $this->redirect($redirect);
        }
    }
}
