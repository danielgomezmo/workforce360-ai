<?php
use App\Core\Csrf;
$title = 'Horarios · Workforce360 AI';
$dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-primary fw-semibold mb-1">Gestión de jornada</p>
        <h1 class="h2 fw-bold mb-1">Horarios</h1>
        <p class="text-secondary mb-0">Configura turnos, tolerancia, descanso y días laborables.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(route_url('horarios/nuevo')) ?>">+ Nuevo horario</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 app-table">
                <thead><tr><th>Horario</th><th>Jornada</th><th>Días</th><th>Tolerancia</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php if (!$horarios): ?><tr><td colspan="6" class="text-center text-secondary py-5">No hay horarios registrados.</td></tr><?php endif; ?>
                <?php foreach ($horarios as $horario):
                    $days = json_decode($horario['dias_semana'] ?: '[]', true) ?: [];
                ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($horario['nombre']) ?></div><div class="small text-secondary"><?= $horario['cruza_medianoche'] ? 'Cruza medianoche' : 'Mismo día' ?></div></td>
                        <td class="text-nowrap"><strong><?= e(substr($horario['hora_inicio'], 0, 5)) ?></strong> – <strong><?= e(substr($horario['hora_fin'], 0, 5)) ?></strong><div class="small text-secondary">Descanso: <?= e(number_format((float) $horario['horas_descanso'], 2)) ?> h</div></td>
                        <td><div class="d-flex flex-wrap gap-1"><?php foreach ($days as $day): ?><span class="day-pill"><?= e($dayNames[(int) $day] ?? $day) ?></span><?php endforeach; ?></div></td>
                        <td><?= e($horario['minutos_tolerancia']) ?> min</td>
                        <td><span class="badge <?= $horario['activo'] ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' ?>"><?= $horario['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-light btn-sm" href="<?= e(route_url('horarios/editar', ['id' => $horario['id_horario']])) ?>">Editar</a>
                            <form method="post" action="<?= e(route_url('horarios/eliminar')) ?>" class="d-inline" data-confirm="¿Eliminar este horario? Solo se podrá eliminar si no está asignado.">
                                <?= Csrf::field() ?><input type="hidden" name="id_horario" value="<?= e($horario['id_horario']) ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
