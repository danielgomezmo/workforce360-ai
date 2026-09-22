<?php
use App\Core\Csrf;
$title = 'Mis solicitudes · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';

$statusClass = static fn(string $s): string => match ($s) {
    'APROBADA' => 'bg-success-subtle text-success-emphasis',
    'RECHAZADA' => 'bg-danger-subtle text-danger-emphasis',
    'ANULADA' => 'bg-secondary-subtle text-secondary-emphasis',
    default => 'bg-warning-subtle text-warning-emphasis',
};
?>
<div class="page-hero mb-3">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-file-circle-check"></i></div>
        <div><h1>Mis Solicitudes</h1><p>Vacaciones, licencias, permisos, descansos médicos y justificaciones.</p></div>
    </div>
    <span class="badge status-badge px-3 py-2">FASE 4</span>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-plus-circle me-2"></i>Nueva solicitud</h2></div>
            <div class="card-body p-4">
                <form method="post" enctype="multipart/form-data" action="<?= e(route_url('mis-solicitudes/guardar')) ?>" id="requestForm">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de solicitud *</label>
                        <select name="tipo_codigo" id="tipoCodigo" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= e($tipo['codigo']) ?>"><?= e($tipo['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="labelInicio">Fecha inicio *</label>
                            <input type="date" name="fecha_inicio" id="fechaInicio" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="labelFin">Fecha fin *</label>
                            <input type="date" name="fecha_fin" id="fechaFin" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo *</label>
                        <input type="text" name="motivo" maxlength="255" class="form-control" placeholder="Ej.: vacaciones programadas, cita médica..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comentario</label>
                        <textarea name="comentario" rows="3" class="form-control" placeholder="Información adicional para RRHH o tu supervisor"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Documento de sustento</label>
                        <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">PDF, JPG o PNG. Máximo 5 MB.</div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Enviar solicitud</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-list me-2"></i>Historial de solicitudes</h2>
                <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="d-flex gap-2">
                    <input type="hidden" name="route" value="mis-solicitudes">
                    <select class="form-select form-select-sm" name="estado" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <?php foreach (['PENDIENTE','APROBADA','RECHAZADA','ANULADA'] as $estado): ?>
                            <option value="<?= e($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= e(ucfirst(strtolower($estado))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table app-table align-middle mb-0">
                    <thead><tr><th>Tipo</th><th>Periodo</th><th>Motivo</th><th>Estado</th><th>Revisión</th></tr></thead>
                    <tbody>
                    <?php if (!$solicitudes): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-5">Todavía no tienes solicitudes registradas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($solicitudes as $item): ?>
                        <tr>
                            <td><strong><?= e($item['tipo_nombre']) ?></strong><?php if ($item['documento_path']): ?><div><a class="small" target="_blank" rel="noopener" href="<?= e(base_url('public/' . $item['documento_path'])) ?>"><i class="fa-solid fa-paperclip me-1"></i>Ver sustento</a></div><?php endif; ?></td>
                            <td class="text-nowrap"><div><?= e(date('d/m/Y', strtotime($item['fecha_inicio']))) ?></div><div class="small text-secondary">hasta <?= e(date('d/m/Y', strtotime($item['fecha_fin'] ?: $item['fecha_inicio']))) ?></div></td>
                            <td><?= e($item['motivo'] ?: '—') ?></td>
                            <td><span class="badge <?= e($statusClass($item['estado'])) ?>"><?= e($item['estado']) ?></span></td>
                            <td class="small"><?= e($item['observacion_revision'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const type = document.getElementById('tipoCodigo');
    const start = document.getElementById('fechaInicio');
    const end = document.getElementById('fechaFin');
    const labelStart = document.getElementById('labelInicio');
    const labelEnd = document.getElementById('labelFin');
    if (!type || !start || !end) return;
    const update = () => {
        const permission = type.value === 'PERMISO';
        start.type = permission ? 'datetime-local' : 'date';
        end.type = permission ? 'datetime-local' : 'date';
        labelStart.textContent = permission ? 'Inicio del permiso *' : 'Fecha inicio *';
        labelEnd.textContent = permission ? 'Fin del permiso *' : 'Fecha fin *';
        start.value = '';
        end.value = '';
    };
    type.addEventListener('change', update);
})();
</script>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
