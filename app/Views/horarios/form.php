<?php
use App\Core\Csrf;
$isEdit = is_array($horario);
$title = ($isEdit ? 'Editar horario' : 'Nuevo horario') . ' · Workforce360 AI';
$v = static fn (string $key, mixed $default = '') => $_SESSION['_old'][$key] ?? $default;
$name = $v('nombre', $horario['nombre'] ?? '');
$start = $v('hora_inicio', isset($horario['hora_inicio']) ? substr($horario['hora_inicio'], 0, 5) : '08:00');
$end = $v('hora_fin', isset($horario['hora_fin']) ? substr($horario['hora_fin'], 0, 5) : '18:00');
$tolerance = $v('minutos_tolerancia', $horario['minutos_tolerancia'] ?? 5);
$break = $v('horas_descanso', $horario['horas_descanso'] ?? 1);
$storedDays = $isEdit ? (json_decode($horario['dias_semana'] ?: '[]', true) ?: []) : [1,2,3,4,5];
$selectedDays = isset($_SESSION['_old']) ? array_map('intval', (array) ($_SESSION['_old']['dias_semana'] ?? [])) : array_map('intval', $storedDays);
$active = isset($_SESSION['_old']) ? isset($_SESSION['_old']['activo']) : (bool) ($horario['activo'] ?? 1);
$days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="form-page mx-auto">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div><p class="text-primary fw-semibold mb-1">Horarios</p><h1 class="h2 fw-bold mb-1"><?= $isEdit ? 'Editar horario' : 'Registrar horario' ?></h1><p class="text-secondary mb-0">Las reglas de tardanza usarán la hora de inicio y la tolerancia configurada.</p></div>
        <a class="btn btn-light" href="<?= e(route_url('horarios')) ?>">Volver</a>
    </div>
    <form method="post" action="<?= e(route_url($isEdit ? 'horarios/actualizar' : 'horarios/guardar')) ?>">
        <?= Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id_horario" value="<?= e($horario['id_horario']) ?>"><?php endif; ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
            <div class="row g-4">
                <div class="col-12"><label class="form-label fw-semibold" for="nombre">Nombre *</label><input class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= e($name) ?>" placeholder="Ej. Administrativo"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="hora_inicio">Hora de inicio *</label><input class="form-control" type="time" id="hora_inicio" name="hora_inicio" required value="<?= e($start) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="hora_fin">Hora de fin *</label><input class="form-control" type="time" id="hora_fin" name="hora_fin" required value="<?= e($end) ?>"><div class="form-text">Si la hora de fin es menor que la inicial, se considera turno nocturno.</div></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="minutos_tolerancia">Tolerancia de ingreso (minutos) *</label><input class="form-control" type="number" min="0" max="120" id="minutos_tolerancia" name="minutos_tolerancia" required value="<?= e($tolerance) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="horas_descanso">Horas de descanso *</label><input class="form-control" type="number" min="0" max="12" step="0.25" id="horas_descanso" name="horas_descanso" required value="<?= e($break) ?>"></div>
                <div class="col-12">
                    <label class="form-label fw-semibold d-block">Días laborables *</label>
                    <div class="weekday-grid">
                        <?php foreach ($days as $num => $label): ?>
                            <label class="weekday-option"><input type="checkbox" name="dias_semana[]" value="<?= $num ?>" <?= in_array($num, $selectedDays, true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $active ? 'checked' : '' ?>><label class="form-check-label" for="activo">Horario activo</label></div></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-5"><a class="btn btn-light" href="<?= e(route_url('horarios')) ?>">Cancelar</a><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar horario' ?></button></div>
        </div></div>
    </form>
</div>
<?php unset($_SESSION['_old']); ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
