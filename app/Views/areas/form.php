<?php
use App\Core\Csrf;
$isEdit = is_array($area);
$title = ($isEdit ? 'Editar área' : 'Nueva área') . ' · Workforce360 AI';
$v = static fn (string $key, mixed $default = '') => $_SESSION['_old'][$key] ?? $default;
$name = $v('nombre', $area['nombre'] ?? '');
$description = $v('descripcion', $area['descripcion'] ?? '');
$active = array_key_exists('_old', $_SESSION) ? isset($_SESSION['_old']['activo']) : (bool) ($area['activo'] ?? 1);
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="form-page mx-auto">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">Áreas</p>
            <h1 class="h2 fw-bold mb-1"><?= $isEdit ? 'Editar área' : 'Registrar área' ?></h1>
            <p class="text-secondary mb-0">Define una unidad organizacional para clasificar al personal.</p>
        </div>
        <a class="btn btn-light" href="<?= e(route_url('areas')) ?>">Volver</a>
    </div>

    <form method="post" action="<?= e(route_url($isEdit ? 'areas/actualizar' : 'areas/guardar')) ?>">
        <?= Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id_area" value="<?= e($area['id_area']) ?>"><?php endif; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="nombre">Nombre del área *</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= e($name) ?>" placeholder="Ej. Tecnología">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" maxlength="200" rows="3" placeholder="Describe brevemente la función del área"><?= e($description) ?></textarea>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $active ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Área activa</label>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-light" href="<?= e(route_url('areas')) ?>">Cancelar</a>
                    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar área' ?></button>
                </div>
            </div>
        </div>
    </form>
</div>
<?php unset($_SESSION['_old']); ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
