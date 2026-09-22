<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Horario;
use Throwable;

class HorarioController extends Controller
{
    private function guard(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR', 'RRHH']);
    }

    public function index(): void
    {
        $this->guard();
        $this->view('horarios/index', ['horarios' => Horario::all()]);
    }

    public function create(): void
    {
        $this->guard();
        $this->view('horarios/form', ['horario' => null]);
    }

    public function edit(): void
    {
        $this->guard();
        $id = (int) ($_GET['id'] ?? 0);
        $horario = Horario::find($id);
        if (!$horario) {
            flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }
        $this->view('horarios/form', ['horario' => $horario]);
    }

    public function store(): void
    {
        $this->guard();
        $this->validateCsrf('horarios/nuevo');
        $data = $this->payload();
        $_SESSION['_old'] = $_POST;
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            $this->redirect('horarios/nuevo');
        }
        try {
            $id = Horario::create($data);
            Audit::log('CREAR', 'horarios', $id, null, $data, null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Horario registrado correctamente.');
            $this->redirect('horarios');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar el horario.');
            $this->redirect('horarios/nuevo');
        }
    }

    public function update(): void
    {
        $this->guard();
        $id = (int) ($_POST['id_horario'] ?? 0);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró.');
            $this->redirect('horarios/editar', ['id' => $id]);
        }
        $horario = Horario::find($id);
        if (!$horario) {
            flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }
        $data = $this->payload();
        $_SESSION['_old'] = $_POST;
        $error = $this->validate($data, $id);
        if ($error) {
            flash('error', $error);
            $this->redirect('horarios/editar', ['id' => $id]);
        }
        try {
            Horario::update($id, $data);
            Audit::log('ACTUALIZAR', 'horarios', $id, $horario, $data, null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Horario actualizado correctamente.');
            $this->redirect('horarios');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo actualizar el horario.');
            $this->redirect('horarios/editar', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $this->guard();
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Solicitud inválida.');
            $this->redirect('horarios');
        }
        $id = (int) ($_POST['id_horario'] ?? 0);
        $horario = Horario::find($id);
        if (!$horario) {
            flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }
        if (Horario::usageCount($id) > 0) {
            flash('error', 'No se puede eliminar el horario porque está asignado a colaboradores. Puedes desactivarlo.');
            $this->redirect('horarios');
        }
        Horario::delete($id);
        Audit::log('ELIMINAR', 'horarios', $id, $horario, null, null, Auth::id());
        flash('success', 'Horario eliminado correctamente.');
        $this->redirect('horarios');
    }

    private function payload(): array
    {
        $inicio = trim((string) ($_POST['hora_inicio'] ?? ''));
        $fin = trim((string) ($_POST['hora_fin'] ?? ''));
        $days = array_values(array_unique(array_map('intval', (array) ($_POST['dias_semana'] ?? []))));
        $days = array_values(array_filter($days, fn ($d) => $d >= 1 && $d <= 7));
        sort($days);

        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'minutos_tolerancia' => max(0, (int) ($_POST['minutos_tolerancia'] ?? 0)),
            'horas_descanso' => max(0, (float) ($_POST['horas_descanso'] ?? 0)),
            'cruza_medianoche' => ($inicio !== '' && $fin !== '' && $fin <= $inicio) ? 1 : 0,
            'dias_semana' => json_encode($days, JSON_UNESCAPED_UNICODE),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $excludeId = null): ?string
    {
        if (mb_strlen($data['nombre']) < 2 || mb_strlen($data['nombre']) > 100) {
            return 'El nombre del horario debe tener entre 2 y 100 caracteres.';
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $data['hora_inicio']) || !preg_match('/^\d{2}:\d{2}$/', $data['hora_fin'])) {
            return 'Ingresa una hora de inicio y fin válidas.';
        }
        if ($data['hora_inicio'] === $data['hora_fin']) {
            return 'La hora de inicio y fin no pueden ser iguales.';
        }
        if ($data['minutos_tolerancia'] > 120) {
            return 'La tolerancia no puede superar 120 minutos.';
        }
        if ($data['horas_descanso'] > 12) {
            return 'Las horas de descanso no pueden superar 12 horas.';
        }
        $days = json_decode($data['dias_semana'], true) ?: [];
        if (!$days) {
            return 'Selecciona por lo menos un día de trabajo.';
        }
        if (Horario::nameExists($data['nombre'], $excludeId)) {
            return 'Ya existe un horario con ese nombre.';
        }
        return null;
    }

    private function validateCsrf(string $fallback): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró.');
            $this->redirect($fallback);
        }
    }
}
