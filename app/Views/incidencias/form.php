<?php
use App\Core\Csrf;
$title = 'Nueva incidencia · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="page-hero mb-3">
    <div class="page-hero-main"><div class="page-hero-icon"><i class="fa-solid fa-file-circle-plus"></i></div><div><h1>Nueva Incidencia</h1><p>Registra una solicitud laboral para un colaborador.</p></div></div>
    <a href="<?= e(route_url('incidencias')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <form method="post" enctype="multipart/form-data" action="<?= e(route_url('incidencias/guardar')) ?>" id="managementIncidentForm">
            <?= Csrf::field() ?>
            <div class="row g-3">
                <div class="col-lg-6"><label class="form-label fw-semibold">Colaborador *</label><select name="id_colaborador" class="form-select" required><option value="">Seleccionar...</option><?php foreach ($colaboradores as $c): ?><option value="<?= e($c['id_colaborador']) ?>"><?= e($c['apellidos'] . ', ' . $c['nombres'] . ' — ' . $c['codigo_trabajador']) ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-6"><label class="form-label fw-semibold">Tipo *</label><select name="tipo_codigo" id="tipoCodigo" class="form-select" required><option value="">Seleccionar...</option><?php foreach ($tipos as $t): ?><option value="<?= e($t['codigo']) ?>"><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label fw-semibold" id="labelInicio">Fecha inicio *</label><input type="date" name="fecha_inicio" id="fechaInicio" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold" id="labelFin">Fecha fin *</label><input type="date" name="fecha_fin" id="fechaFin" class="form-control" required></div>
                <div class="col-12"><label class="form-label fw-semibold">Motivo *</label><input type="text" name="motivo" maxlength="255" class="form-control" required></div>
                <div class="col-12"><label class="form-label fw-semibold">Comentario</label><textarea name="comentario" rows="3" class="form-control"></textarea></div>
                <div class="col-12"><label class="form-label fw-semibold">Documento</label><input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png"><div class="form-text">PDF, JPG o PNG. Máximo 5 MB.</div></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Registrar incidencia</button><a class="btn btn-outline-secondary" href="<?= e(route_url('incidencias')) ?>">Cancelar</a></div>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const type=document.getElementById('tipoCodigo'), start=document.getElementById('fechaInicio'), end=document.getElementById('fechaFin');
    const ls=document.getElementById('labelInicio'), le=document.getElementById('labelFin');
    if(!type||!start||!end)return;
    type.addEventListener('change',()=>{const p=type.value==='PERMISO';start.type=p?'datetime-local':'date';end.type=p?'datetime-local':'date';ls.textContent=p?'Inicio del permiso *':'Fecha inicio *';le.textContent=p?'Fin del permiso *':'Fecha fin *';start.value='';end.value='';});
})();
</script>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
