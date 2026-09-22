<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Area;
use Throwable;

class AreaController extends Controller
{
    private function guard(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR', 'RRHH']);
    }

    public function index(): void
    {
        $this->guard();
        $this->view('areas/index', ['areas' => Area::all()]);
    }

    public function create(): void
    {
        $this->guard();
        $this->view('areas/form', ['area' => null]);
    }

    public function edit(): void
    {
        $this->guard();
        $id = (int) ($_GET['id'] ?? 0);
        $area = Area::find($id);
        if (!$area) {
            flash('error', 'Área no encontrada.');
            $this->redirect('areas');
        }
        $this->view('areas/form', ['area' => $area]);
    }

    public function store(): void
    {
        $this->guard();
        $this->validateCsrf('areas/nuevo');
        $data = $this->payload();
        $_SESSION['_old'] = $_POST;

        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            $this->redirect('areas/nuevo');
        }

        try {
            $id = Area::create($data);
            Audit::log('CREAR', 'areas', $id, null, $data, null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Área registrada correctamente.');
            $this->redirect('areas');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo registrar el área.');
            $this->redirect('areas/nuevo');
        }
    }

    public function update(): void
    {
        $this->guard();
        $id = (int) ($_POST['id_area'] ?? 0);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró.');
            $this->redirect('areas/editar', ['id' => $id]);
        }
        $area = Area::find($id);
        if (!$area) {
            flash('error', 'Área no encontrada.');
            $this->redirect('areas');
        }

        $data = $this->payload();
        $_SESSION['_old'] = $_POST;
        $error = $this->validate($data, $id);
        if ($error) {
            flash('error', $error);
            $this->redirect('areas/editar', ['id' => $id]);
        }

        try {
            Area::update($id, $data);
            Audit::log('ACTUALIZAR', 'areas', $id, $area, $data, null, Auth::id());
            unset($_SESSION['_old']);
            flash('success', 'Área actualizada correctamente.');
            $this->redirect('areas');
        } catch (Throwable $e) {
            flash('error', config('app.debug') ? $e->getMessage() : 'No se pudo actualizar el área.');
            $this->redirect('areas/editar', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $this->guard();
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Solicitud inválida.');
            $this->redirect('areas');
        }
        $id = (int) ($_POST['id_area'] ?? 0);
        $area = Area::find($id);
        if (!$area) {
            flash('error', 'Área no encontrada.');
            $this->redirect('areas');
        }
        if (Area::usageCount($id) > 0) {
            flash('error', 'No se puede eliminar el área porque tiene información relacionada. Puedes desactivarla desde Editar.');
            $this->redirect('areas');
        }
        Area::delete($id);
        Audit::log('ELIMINAR', 'areas', $id, $area, null, null, Auth::id());
        flash('success', 'Área eliminada correctamente.');
        $this->redirect('areas');
    }

    private function payload(): array
    {
        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')) ?: null,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $excludeId = null): ?string
    {
        if (mb_strlen($data['nombre']) < 2 || mb_strlen($data['nombre']) > 100) {
            return 'El nombre del área debe tener entre 2 y 100 caracteres.';
        }
        if ($data['descripcion'] !== null && mb_strlen($data['descripcion']) > 200) {
            return 'La descripción no debe superar 200 caracteres.';
        }
        if (Area::nameExists($data['nombre'], $excludeId)) {
            return 'Ya existe un área con ese nombre.';
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
