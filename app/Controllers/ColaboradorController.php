<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Area;
use App\Models\Colaborador;
use App\Models\Equipo;
use App\Models\Horario;
use App\Models\User;
use Throwable;

class ColaboradorController extends Controller
{
    private const ESTADOS = ['REGISTRADO','HABILITADO','ACTIVO','LICENCIA','VACACIONES','SUSPENDIDO','CESADO'];
    private const MODALIDADES = ['PRESENCIAL','REMOTO','HIBRIDO'];

    private function guard(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR', 'RRHH']);
    }

    public function index(): void
    {
        $this->guard();
        $q = trim((string) ($_GET['q'] ?? ''));
        $estado = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        if ($estado !== '' && !in_array($estado, self::ESTADOS, true)) {
            $estado = '';
        }
        $this->view('colaboradores/index', [
            'colaboradores' => Colaborador::all($q, $estado),
            'q' => $q,
            'estado' => $estado,
            'estados' => self::ESTADOS,
        ]);
    }

    public function create(): void
    {
        $this->guard();
        $this->view('colaboradores/form', $this->catalogs(null));
    }

    public function edit(): void
    {
        $this->guard();
        $id = (int) ($_GET['id'] ?? 0);
        $colaborador = Colaborador::find($id);
        if (!$colaborador) {
            flash('error', 'Colaborador no encontrado.');
            $this->redirect('colaboradores');
        }
        $this->view('colaboradores/form', $this->catalogs($colaborador));
    }

    public function store(): void
    {
        $this->guard();
        $this->validateCsrf('colaboradores/nuevo');
        [$data, $horarioId, $access] = $this->payload();
        $_SESSION['_old'] = $_POST;
        $error = $this->validate($data, $horarioId, $access);
        if ($error) {
            flash('error', $error);
            $this->redirect('colaboradores/nuevo');
        }
        try {
            $id = Colaborador::create($data, $horarioId, $access);
            Audit::log('CREAR', 'colaboradores', $id, null, array_merge($data, [
                'id_horario' => $horarioId,
                'usuario_acceso' => $access['username'],
            ]), null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Colaborador y acceso al sistema registrados correctamente. Ya puede iniciar sesión y marcar asistencia.');
            $this->redirect('colaboradores');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar el colaborador.');
            $this->redirect('colaboradores/nuevo');
        }
    }

    public function update(): void
    {
        $this->guard();
        $id = (int) ($_POST['id_colaborador'] ?? 0);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró.');
            $this->redirect('colaboradores/editar', ['id' => $id]);
        }
        $old = Colaborador::find($id);
        if (!$old) {
            flash('error', 'Colaborador no encontrado.');
            $this->redirect('colaboradores');
        }
        [$data, $horarioId, $access] = $this->payload();
        $_SESSION['_old'] = $_POST;
        $error = $this->validate($data, $horarioId, $access, $id, $old);
        if ($error) {
            flash('error', $error);
            $this->redirect('colaboradores/editar', ['id' => $id]);
        }
        try {
            Colaborador::update($id, $data, $horarioId, $access);
            Audit::log('ACTUALIZAR', 'colaboradores', $id, $old, array_merge($data, [
                'id_horario' => $horarioId,
                'usuario_acceso' => $access['username'],
            ]), null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Colaborador y acceso al sistema actualizados correctamente.');
            $this->redirect('colaboradores');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo actualizar el colaborador.');
            $this->redirect('colaboradores/editar', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $this->guard();
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Solicitud inválida.');
            $this->redirect('colaboradores');
        }
        $id = (int) ($_POST['id_colaborador'] ?? 0);
        $colaborador = Colaborador::find($id);
        if (!$colaborador) {
            flash('error', 'Colaborador no encontrado.');
            $this->redirect('colaboradores');
        }
        if (Colaborador::usageCount($id) > 0) {
            flash('error', 'No se puede eliminar porque el colaborador tiene historial relacionado. Para conservar la trazabilidad, cambia su estado a CESADO.');
            $this->redirect('colaboradores');
        }
        Colaborador::delete($id);
        Audit::log('ELIMINAR', 'colaboradores', $id, $colaborador, null, null, Auth::id());
        flash('success', 'Colaborador eliminado correctamente.');
        $this->redirect('colaboradores');
    }

    private function catalogs(?array $colaborador): array
    {
        return [
            'colaborador' => $colaborador,
            'areas' => Area::active(),
            'equipos' => Equipo::active(),
            'horarios' => Horario::active(),
            'supervisores' => Colaborador::supervisors($colaborador ? (int) $colaborador['id_colaborador'] : null),
            'estados' => self::ESTADOS,
            'modalidades' => self::MODALIDADES,
        ];
    }

    private function payload(): array
    {
        $intOrNull = static fn ($value) => ((int) $value > 0 ? (int) $value : null);
        $email = trim((string) ($_POST['email_corporativo'] ?? ''));
        $fechaCese = trim((string) ($_POST['fecha_cese'] ?? ''));

        $data = [
            'codigo_trabajador' => strtoupper(trim((string) ($_POST['codigo_trabajador'] ?? ''))),
            'tipo_documento' => strtoupper(trim((string) ($_POST['tipo_documento'] ?? 'DNI'))),
            'numero_documento' => trim((string) ($_POST['numero_documento'] ?? '')),
            'nombres' => trim((string) ($_POST['nombres'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'id_area' => $intOrNull($_POST['id_area'] ?? null),
            'id_equipo' => $intOrNull($_POST['id_equipo'] ?? null),
            'id_supervisor' => $intOrNull($_POST['id_supervisor'] ?? null),
            'cargo' => trim((string) ($_POST['cargo'] ?? '')),
            'sede' => trim((string) ($_POST['sede'] ?? '')),
            'fecha_ingreso' => trim((string) ($_POST['fecha_ingreso'] ?? '')),
            'fecha_cese' => $fechaCese !== '' ? $fechaCese : null,
            'jornada_horas' => (float) ($_POST['jornada_horas'] ?? 8),
            'modalidad' => strtoupper(trim((string) ($_POST['modalidad'] ?? 'PRESENCIAL'))),
            'estado' => strtoupper(trim((string) ($_POST['estado'] ?? 'REGISTRADO'))),
            'email_corporativo' => $email !== '' ? strtolower($email) : null,
            'telefono' => trim((string) ($_POST['telefono'] ?? '')) ?: null,
        ];

        $access = [
            'username' => strtolower(trim((string) ($_POST['acceso_username'] ?? ''))),
            'password' => (string) ($_POST['acceso_password'] ?? ''),
            'activo' => isset($_POST['acceso_activo']) ? 1 : 0,
            'email' => $data['email_corporativo'],
        ];

        return [$data, (int) ($_POST['id_horario'] ?? 0), $access];
    }

    private function validate(array $data, int $horarioId, array $access, ?int $excludeId = null, ?array $existing = null): ?string
    {
        foreach (['codigo_trabajador','numero_documento','nombres','apellidos','cargo','sede','fecha_ingreso'] as $field) {
            if ((string) ($data[$field] ?? '') === '') {
                return 'Completa todos los campos obligatorios.';
            }
        }
        if ($data['id_area'] === null) {
            return 'Selecciona un área.';
        }
        if ($horarioId <= 0 || !Horario::find($horarioId)) {
            return 'Selecciona un horario válido.';
        }
        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $data['codigo_trabajador'])) {
            return 'El código de trabajador solo puede contener letras, números, guion y guion bajo.';
        }
        if (mb_strlen($data['numero_documento']) < 6 || mb_strlen($data['numero_documento']) > 30) {
            return 'El número de documento debe tener entre 6 y 30 caracteres.';
        }
        if (mb_strlen($data['nombres']) > 100 || mb_strlen($data['apellidos']) > 120) {
            return 'Nombres o apellidos superan la longitud permitida.';
        }
        if ($data['jornada_horas'] <= 0 || $data['jornada_horas'] > 24) {
            return 'La jornada debe ser mayor a 0 y no superar 24 horas.';
        }
        if (!in_array($data['modalidad'], self::MODALIDADES, true) || !in_array($data['estado'], self::ESTADOS, true)) {
            return 'Modalidad o estado inválido.';
        }
        if ($data['email_corporativo'] !== null && !filter_var($data['email_corporativo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo corporativo no tiene un formato válido.';
        }
        if ($data['fecha_cese'] !== null && $data['fecha_cese'] < $data['fecha_ingreso']) {
            return 'La fecha de cese no puede ser anterior a la fecha de ingreso.';
        }
        if ($data['estado'] === 'CESADO' && $data['fecha_cese'] === null) {
            return 'Un colaborador en estado CESADO debe tener fecha de cese.';
        }
        if ($data['id_supervisor'] !== null && $excludeId !== null && $data['id_supervisor'] === $excludeId) {
            return 'El colaborador no puede ser su propio supervisor.';
        }
        if ($data['id_equipo'] !== null && !Equipo::belongsToArea($data['id_equipo'], (int) $data['id_area'])) {
            return 'El equipo seleccionado no pertenece al área indicada.';
        }
        if (Colaborador::uniqueExists('codigo_trabajador', $data['codigo_trabajador'], $excludeId)) {
            return 'El código de trabajador ya está registrado.';
        }
        if (Colaborador::uniqueExists('numero_documento', $data['numero_documento'], $excludeId)) {
            return 'El documento ya está registrado.';
        }
        if ($data['email_corporativo'] !== null && Colaborador::uniqueExists('email_corporativo', $data['email_corporativo'], $excludeId)) {
            return 'El correo corporativo ya está registrado.';
        }

        if ($access['username'] === '' || !preg_match('/^[a-z0-9._-]{3,60}$/', $access['username'])) {
            return 'Define un usuario de acceso de 3 a 60 caracteres usando letras, números, punto, guion o guion bajo.';
        }
        $existingUserId = isset($existing['acceso_id_usuario']) && $existing['acceso_id_usuario'] !== null
            ? (int) $existing['acceso_id_usuario']
            : null;
        if (User::usernameExists($access['username'], $existingUserId)) {
            return 'El usuario de acceso ya está siendo utilizado.';
        }
        if ($data['email_corporativo'] !== null && User::emailExists($data['email_corporativo'], $existingUserId)) {
            return 'El correo corporativo ya está asociado a otra cuenta de acceso.';
        }
        $needsPassword = $existingUserId === null;
        if ($needsPassword && $access['password'] === '') {
            return 'Define una contraseña para que el colaborador pueda iniciar sesión y marcar asistencia.';
        }
        if ($access['password'] !== '' && mb_strlen($access['password']) < 8) {
            return 'La contraseña de acceso debe tener como mínimo 8 caracteres.';
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
