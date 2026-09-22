<?php
use App\Core\Auth;
use App\Core\Csrf;
$title = 'Incidencias · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
$statusClass = static fn(string $s): string => match ($s) {
    'APROBADA' => 'bg-success-subtle text-success-emphasis',
    'RECHAZADA' => 'bg-danger-subtle text-danger-emphasis',
    'ANULADA' => 'bg-secondary-subtle text-secondary-emphasis',
    default => 'bg-warning-subtle text-warning-emphasis',
};
?>
<div class="page-hero mb-3">
    <div class="page-hero-main"><div class="page-hero-icon"><i class="fa-solid fa-clipboard-check"></i></div><div><h1>Gestión de Incidencias</h1><p>Revisión y trazabilidad de vacaciones, licencias, permisos y descansos médicos.</p></div></div>
    <a href="<?= e(route_url('incidencias/nueva')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Nueva incidencia</a>
</div>

<div class="row g-3 mb-3">
    <?php foreach ([['Pendientes',$resumen['pendientes'],'kpi-warning','fa-hourglass-half'],['Aprobadas',$resumen['aprobadas'],'kpi-green','fa-circle-check'],['Rechazadas',$resumen['rechazadas'],'kpi-red','fa-circle-xmark'],['Total',$resumen['total'],'kpi-teal','fa-folder-open']] as [$label,$value,$tone,$icon]): ?>
        <div class="col-6 col-xl-3"><div class="card kpi-card <?= e($tone) ?>"><div class="card-body p-3"><div class="kpi-label"><?= e($label) ?></div><div class="kpi-value"><?= e($value) ?></div><i class="fa-solid <?= e($icon) ?> kpi-icon"></i></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header p-3">
        <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="incidencias">
            <div class="col-md-5"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar colaborador, documento, área o tipo..."></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="estado"><option value="">Todos los estados</option><?php foreach (['PENDIENTE','REGISTRADA','APROBADA','RECHAZADA','ANULADA'] as $estado): ?><option value="<?= e($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= e($estado) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-auto"><button class="btn btn-sm btn-outline-primary" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button></div>
            <div class="col-md-auto"><a class="btn btn-sm btn-outline-secondary" href="<?= e(route_url('incidencias')) ?>">Limpiar</a></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table app-table align-middle mb-0">
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Periodo</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$incidencias): ?><tr><td colspan="6" class="text-center text-secondary py-5">No hay incidencias para los filtros seleccionados.</td></tr><?php endif; ?>
            <?php foreach ($incidencias as $item): ?>
                <tr>
                    <td><strong><?= e($item['apellidos'] . ', ' . $item['nombres']) ?></strong><div class="small text-secondary"><?= e($item['codigo_trabajador']) ?> · <?= e($item['area_nombre'] ?: 'Sin área') ?></div></td>
                    <td><strong><?= e($item['tipo_nombre']) ?></strong><?php if ($item['documento_path']): ?><div><a target="_blank" rel="noopener" class="small" href="<?= e(base_url('public/' . $item['documento_path'])) ?>"><i class="fa-solid fa-paperclip me-1"></i>Sustento</a></div><?php endif; ?></td>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($item['fecha_inicio']))) ?><div class="small text-secondary">a <?= e(date('d/m/Y H:i', strtotime($item['fecha_fin'] ?: $item['fecha_inicio']))) ?></div></td>
                    <td style="min-width:180px"><?= e($item['motivo'] ?: '—') ?><?php if ($item['comentario']): ?><div class="small text-secondary mt-1"><?= e($item['comentario']) ?></div><?php endif; ?></td>
                    <td><span class="badge <?= e($statusClass($item['estado'])) ?>"><?= e($item['estado']) ?></span><?php if ($item['observacion_revision']): ?><div class="small text-secondary mt-1"><?= e($item['observacion_revision']) ?></div><?php endif; ?></td>
                    <td style="min-width:250px">
                        <?php if ($item['estado'] === 'PENDIENTE' || ($item['estado'] === 'REGISTRADA' && (int)($item['requiere_aprobacion'] ?? 0) === 1)): ?>
                            <form method="post" action="<?= e(route_url('incidencias/revisar')) ?>" class="d-flex gap-1 mb-1">
                                <?= Csrf::field() ?><input type="hidden" name="id_incidencia" value="<?= e($item['id_incidencia']) ?>">
                                <input type="hidden" name="decision" value="APROBADA">
                                <input class="form-control form-control-sm" name="observacion_revision" placeholder="Observación opcional">
                                <button class="btn btn-sm btn-success" title="Aprobar"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <form method="post" action="<?= e(route_url('incidencias/revisar')) ?>" class="d-flex gap-1">
                                <?= Csrf::field() ?><input type="hidden" name="id_incidencia" value="<?= e($item['id_incidencia']) ?>">
                                <input type="hidden" name="decision" value="RECHAZADA">
                                <input class="form-control form-control-sm" name="observacion_revision" placeholder="Motivo de rechazo">
                                <button class="btn btn-sm btn-danger" title="Rechazar"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        <?php elseif (Auth::hasAnyRole(['ADMINISTRADOR','RRHH']) && $item['estado'] !== 'ANULADA'): ?>
                            <form method="post" action="<?= e(route_url('incidencias/anular')) ?>" data-confirm="¿Anular esta incidencia?">
                                <?= Csrf::field() ?><input type="hidden" name="id_incidencia" value="<?= e($item['id_incidencia']) ?>">
                                <input type="hidden" name="observacion_revision" value="Anulada desde gestión de incidencias.">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-ban me-1"></i>Anular</button>
                            </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
