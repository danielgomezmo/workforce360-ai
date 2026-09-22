<?php
$title = 'Marcaciones · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
$fmtTime = static fn (?string $v): string => $v ? date('h:i A', strtotime($v)) : '—';
$fmtMinutes = static function (int $minutes): string {
    $h = intdiv(max(0,$minutes),60); $m = max(0,$minutes)%60;
    return $h > 0 ? $h . ' h ' . $m . ' min' : $m . ' min';
};
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-primary fw-semibold mb-1">Control operativo</p>
        <h1 class="h2 fw-bold mb-1">Marcaciones</h1>
        <p class="text-secondary mb-0">Consulta diaria de ingresos, salidas y reglas detectadas automáticamente.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(route_url('marcaciones', ['fecha' => date('Y-m-d')])) ?>">Ver hoy</a>
</div>

<form class="card border-0 shadow-sm mb-4" method="get" action="<?= e(base_url('public/index.php')) ?>">
    <input type="hidden" name="route" value="marcaciones">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label fw-semibold">Fecha</label><input type="date" class="form-control" name="fecha" value="<?= e($fecha) ?>"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Buscar colaborador</label><input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Código, documento, nombre o área"></div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit">Aplicar filtros</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php $cards = [
        ['Marcaciones', $resumen['total'] ?? 0],
        ['Puntuales', $resumen['puntuales'] ?? 0],
        ['Tardanzas', $resumen['tardanzas'] ?? 0],
        ['Salidas anticipadas', $resumen['salidas_anticipadas'] ?? 0],
        ['Jornadas abiertas', $resumen['jornadas_abiertas'] ?? 0],
        ['Horas efectivas', round(($resumen['minutos_trabajados'] ?? 0)/60, 1)],
    ]; foreach ($cards as [$label,$value]): ?>
        <div class="col-6 col-md-4 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body p-3"><div class="small text-secondary mb-1"><?= e($label) ?></div><div class="h3 fw-bold mb-0"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table app-table align-middle mb-0">
            <thead><tr><th>Colaborador</th><th>Área</th><th>Horario</th><th>Ingreso</th><th>Resultado</th><th>Salida</th><th>Anticipada</th><th>Trabajado</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (!$marcaciones): ?><tr><td colspan="9" class="text-center text-secondary py-5">No existen marcaciones para los filtros seleccionados.</td></tr><?php endif; ?>
            <?php foreach ($marcaciones as $m): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($m['nombres'].' '.$m['apellidos']) ?></div><div class="small text-secondary"><?= e($m['codigo_trabajador']) ?></div></td>
                    <td><?= e($m['area_nombre'] ?: '—') ?></td>
                    <td><?= e($m['horario_nombre'] ?: '—') ?></td>
                    <td><?= e($fmtTime($m['hora_entrada'])) ?></td>
                    <td><span class="badge <?= $m['resultado_entrada']==='TARDANZA' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= e($m['resultado_entrada']) ?></span><?php if((int)$m['minutos_tardanza']>0): ?><div class="small text-danger mt-1"><?= e($m['minutos_tardanza']) ?> min</div><?php endif; ?></td>
                    <td><?= e($fmtTime($m['hora_salida'])) ?></td>
                    <td><?= e($fmtMinutes((int)$m['minutos_salida_anticipada'])) ?></td>
                    <td><?= e($fmtMinutes((int)$m['minutos_trabajados'])) ?></td>
                    <td><?= e($m['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
