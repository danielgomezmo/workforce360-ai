<?php
$title = 'Mi historial · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
$fmt = static function (?string $value): string {
    return $value ? date('H:i:s', strtotime($value)) : '—';
};
$fmtMinutes = static function (int $minutes): string {
    if ($minutes <= 0) return '—';
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return $hours > 0 ? sprintf('%dh %02dm', $hours, $mins) : $mins . ' min';
};
?>
<div class="page-hero mb-3">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div><h1>Mi Historial</h1><p>Consulta todos tus registros de asistencia por fecha y estado.</p></div>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(route_url('mi-asistencia')) ?>"><i class="fa-solid fa-fingerprint me-1"></i>Panel de marcación</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="card kpi-card kpi-teal"><div class="card-body p-3"><div class="kpi-label">Registros</div><div class="kpi-value"><?= e($resumen['total']) ?></div><div class="kpi-hint">En el periodo</div><i class="fa-solid fa-list kpi-icon"></i></div></div></div>
    <div class="col-6 col-xl-3"><div class="card kpi-card kpi-green"><div class="card-body p-3"><div class="kpi-label">Puntuales</div><div class="kpi-value"><?= e($resumen['puntuales']) ?></div><div class="kpi-hint">Entradas a tiempo</div><i class="fa-solid fa-circle-check kpi-icon"></i></div></div></div>
    <div class="col-6 col-xl-3"><div class="card kpi-card kpi-warning"><div class="card-body p-3"><div class="kpi-label">Tardanzas</div><div class="kpi-value"><?= e($resumen['tardanzas']) ?></div><div class="kpi-hint"><?= e($fmtMinutes($resumen['minutos_tardanza'])) ?> acumulados</div><i class="fa-solid fa-clock kpi-icon"></i></div></div></div>
    <div class="col-6 col-xl-3"><div class="card kpi-card kpi-blue"><div class="card-body p-3"><div class="kpi-label">Tiempo trabajado</div><div class="kpi-value history-hours"><?= e(number_format($resumen['minutos_trabajados'] / 60, 1)) ?>h</div><div class="kpi-hint">Acumulado</div><i class="fa-solid fa-business-time kpi-icon"></i></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header p-3">
        <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="mi-historial">
            <div class="col-sm-4 col-lg-3"><label class="form-label small fw-semibold mb-1">Desde</label><input type="date" class="form-control form-control-sm" name="desde" value="<?= e($desde) ?>"></div>
            <div class="col-sm-4 col-lg-3"><label class="form-label small fw-semibold mb-1">Hasta</label><input type="date" class="form-control form-control-sm" name="hasta" value="<?= e($hasta) ?>"></div>
            <div class="col-sm-4 col-lg-3"><label class="form-label small fw-semibold mb-1">Estado</label><select name="estado" class="form-select form-select-sm"><option value="">Todos</option><?php foreach (['PUNTUAL','TARDANZA','PARCIAL','COMPLETA','PENDIENTE'] as $estado): ?><option value="<?= e($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= e($estado) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-auto"><button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Filtrar</button></div>
            <div class="col-lg-auto"><a class="btn btn-sm btn-outline-secondary" href="<?= e(route_url('mi-historial')) ?>">Mes actual</a></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table app-table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Horario</th><th>Entrada</th><th>Salida</th><th>Tardanza</th><th>Salida anticipada</th><th>Tiempo trabajado</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (!$historial): ?><tr><td colspan="8" class="text-center text-secondary py-5">No tienes registros en el periodo seleccionado.</td></tr><?php endif; ?>
            <?php foreach ($historial as $row): ?>
                <?php $state = (string)($row['estado'] ?: $row['resultado_entrada']); ?>
                <tr>
                    <td><strong><?= e(date('d/m/Y', strtotime($row['fecha']))) ?></strong></td>
                    <td><?= e($row['horario_nombre'] ?: '—') ?></td>
                    <td><?= e($fmt($row['hora_entrada'])) ?></td>
                    <td><?= e($fmt($row['hora_salida'])) ?></td>
                    <td class="<?= (int)$row['minutos_tardanza'] > 0 ? 'text-danger fw-semibold' : '' ?>"><?= e($fmtMinutes((int)$row['minutos_tardanza'])) ?></td>
                    <td class="<?= (int)$row['minutos_salida_anticipada'] > 0 ? 'text-danger fw-semibold' : '' ?>"><?= e($fmtMinutes((int)$row['minutos_salida_anticipada'])) ?></td>
                    <td><?= e($fmtMinutes((int)$row['minutos_trabajados'])) ?></td>
                    <td><span class="badge <?= $row['resultado_entrada'] === 'TARDANZA' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis' ?>"><?= e($state) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
