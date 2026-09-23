<?php
$title = 'Reporte de Tardanzas · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';

$formatMinutes = static function (int $minutes): string {
    if ($minutes <= 0) return '0 min';
    $hours = intdiv($minutes, 60); $rest = $minutes % 60;
    return $hours > 0 ? sprintf('%dh %02dm', $hours, $rest) : $rest . ' min';
};
$selectedCollaborator = '';
foreach ($collaborators as $item) if ((int)($filters['colaborador'] ?? 0) === (int)$item['id_colaborador']) { $selectedCollaborator = trim($item['nombres'].' '.$item['apellidos']); break; }
$selectedArea = '';
foreach ($areas as $item) if ((int)($filters['area'] ?? 0) === (int)$item['id_area']) { $selectedArea = (string)$item['nombre']; break; }
?>
<div class="page-hero mb-4 no-print"><div class="page-hero-main"><div class="page-hero-icon"><i class="fa-solid fa-clock"></i></div><div><h1>Reporte de Tardanzas</h1><p>Analiza llegadas tardías, minutos acumulados y concentración de demoras.</p></div></div><a href="<?= e(route_url('reportes')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Centro de Reportes</a></div>

<div class="card shadow-sm mb-4 no-print"><div class="card-body p-4"><form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-3 align-items-end"><input type="hidden" name="route" value="reportes/tardanzas">
<div class="col-md-6 col-xl-2"><label class="form-label fw-semibold">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>"></div>
<div class="col-md-6 col-xl-2"><label class="form-label fw-semibold">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>"></div>
<div class="col-md-6 col-xl-3"><label class="form-label fw-semibold">Colaborador</label><select name="colaborador" class="form-select"><option value="">Todos</option><?php foreach ($collaborators as $item): ?><option value="<?= e($item['id_colaborador']) ?>" <?= (int)($filters['colaborador'] ?? 0)===(int)$item['id_colaborador']?'selected':'' ?>><?= e($item['codigo_trabajador'].' · '.$item['apellidos'].', '.$item['nombres']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6 col-xl-3"><label class="form-label fw-semibold">Área</label><select name="area" class="form-select"><option value="">Todas</option><?php foreach ($areas as $item): ?><option value="<?= e($item['id_area']) ?>" <?= (int)($filters['area'] ?? 0)===(int)$item['id_area']?'selected':'' ?>><?= e($item['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6 col-xl-2 d-grid"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-2"></i>Consultar</button></div>
</form></div></div>

<div class="report-actions no-print"><button type="button" class="btn btn-success" data-report-excel><i class="fa-solid fa-file-excel me-2"></i>Excel</button><button type="button" class="btn btn-danger" data-report-pdf><i class="fa-solid fa-file-pdf me-2"></i>PDF</button><button type="button" class="btn btn-dark" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Imprimir</button></div>

<section class="report-export-root" data-report-export data-report-title="Reporte de Tardanzas" data-file-name="Reporte_Tardanzas_<?= e($filters['desde']) ?>_<?= e($filters['hasta']) ?>" data-logo-url="<?= e(asset('devioz-logo1.png')) ?>">
<div class="report-export-meta d-none"><span data-export-meta data-label="Periodo" data-value="<?= e(date('d/m/Y',strtotime($filters['desde'])).' al '.date('d/m/Y',strtotime($filters['hasta']))) ?>"></span><span data-export-meta data-label="Área" data-value="<?= e($selectedArea?:'Todas') ?>"></span><span data-export-meta data-label="Colaborador" data-value="<?= e($selectedCollaborator?:'Todos') ?>"></span><span data-export-meta data-label="Generado" data-value="<?= e($generatedAt->format('d/m/Y H:i')) ?>"></span></div>
<section class="print-report-header"><div class="print-brand-row"><img src="<?= e(asset('devioz-logo1.png')) ?>" alt="DEVIOZ" class="print-brand-logo"><div class="print-company">WORKFORCE360 AI</div><div class="print-report-title">REPORTE DE TARDANZAS</div></div><div class="print-report-meta"><div><strong>Periodo:</strong> <?= e(date('d/m/Y',strtotime($filters['desde']))) ?> al <?= e(date('d/m/Y',strtotime($filters['hasta']))) ?></div><div><strong>Área:</strong> <?= e($selectedArea?:'Todas') ?></div><div><strong>Colaborador:</strong> <?= e($selectedCollaborator?:'Todos') ?></div><div><strong>Generado:</strong> <?= e($generatedAt->format('d/m/Y H:i')) ?></div></div></section>
<div class="row g-3 mb-4 report-summary-grid" data-export-summary>
<div class="col-6 col-xl-3"><div class="report-summary-card"><span>Tardanzas</span><strong><?= e($summary['total']) ?></strong><small>Eventos encontrados</small></div></div>
<div class="col-6 col-xl-3"><div class="report-summary-card"><span>Tiempo acumulado</span><strong><?= e($formatMinutes((int)$summary['minutos_total'])) ?></strong><small>Minutos de demora</small></div></div>
<div class="col-6 col-xl-3"><div class="report-summary-card"><span>Promedio</span><strong><?= e(number_format((float)$summary['promedio_minutos'],1)) ?> min</strong><small>Por tardanza</small></div></div>
<div class="col-6 col-xl-3"><div class="report-summary-card"><span>Mayor tardanza</span><strong><?= e($summary['maximo_minutos']) ?> min</strong><small>Máximo registrado</small></div></div>
</div>
<div class="card shadow-sm report-table-card"><div class="card-header py-3 px-4 no-print"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-table-list me-2"></i>Detalle de tardanzas</h2></div><div class="table-responsive"><table class="table app-table report-data-table align-middle mb-0" data-export-table><thead><tr><th>#</th><th>Código</th><th>Colaborador</th><th>Área</th><th>Fecha</th><th>Entrada programada</th><th>Entrada real</th><th>Tardanza</th><th>Horario</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="9" class="text-center py-5 text-secondary">No se encontraron tardanzas para los filtros seleccionados.</td></tr><?php else: foreach ($rows as $i=>$row): ?>
<tr><td><?= e($i+1) ?></td><td><strong><?= e($row['codigo_trabajador']) ?></strong></td><td><div class="fw-semibold"><?= e($row['nombres'].' '.$row['apellidos']) ?></div><div class="small text-secondary"><?= e($row['cargo']?:'Sin cargo') ?></div></td><td><?= e($row['area_nombre']?:'—') ?></td><td><?= e(date('d/m/Y',strtotime($row['fecha']))) ?></td><td><?= e($row['entrada_programada']?date('H:i',strtotime($row['entrada_programada'])):'—') ?></td><td><?= e($row['hora_entrada']?date('H:i',strtotime($row['hora_entrada'])):'—') ?></td><td><span class="badge bg-warning-subtle text-warning-emphasis"><?= e($row['minutos_tardanza']) ?> min</span></td><td><?= e($row['horario_nombre']?:'—') ?></td></tr>
<?php endforeach; endif; ?></tbody></table></div></div><div class="print-report-footer">Workforce360 AI · DEVIOZ · Reporte generado automáticamente desde el sistema.</div></section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
