<?php
use App\Core\Csrf;
$title = 'Colaboradores · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="page-hero mb-3">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-users"></i></div>
        <div><h1>Gestión de Colaboradores</h1><p>Administración del personal registrado en la empresa</p></div>
    </div>
    <a class="btn btn-primary btn-sm px-3 py-2" href="<?= e(route_url('colaboradores/nuevo')) ?>"><i class="fa-solid fa-user-plus me-2"></i>Nuevo Colaborador</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="colaboradores">
            <div class="col-md-7">
                <label class="form-label small fw-semibold" for="q">Buscar</label>
                <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Código, documento, nombres o apellidos">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $item): ?><option value="<?= e($item) ?>" <?= $estado === $item ? 'selected' : '' ?>><?= e(ucwords(strtolower(str_replace('_', ' ', $item)))) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Filtrar</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 app-table">
                <thead><tr><th>Colaborador</th><th>Área / cargo</th><th>Horario</th><th>Acceso</th><th>Modalidad</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php if (!$colaboradores): ?><tr><td colspan="7" class="text-center text-secondary py-5">No se encontraron colaboradores.</td></tr><?php endif; ?>
                <?php foreach ($colaboradores as $c): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></div>
                            <div class="small text-secondary"><?= e($c['codigo_trabajador']) ?> · <?= e($c['tipo_documento']) ?> <?= e($c['numero_documento']) ?></div>
                        </td>
                        <td><div><?= e($c['area_nombre'] ?: 'Sin área') ?></div><div class="small text-secondary"><?= e($c['cargo'] ?: 'Sin cargo') ?><?= $c['equipo_nombre'] ? ' · ' . e($c['equipo_nombre']) : '' ?></div></td>
                        <td><?php if ($c['horario_nombre']): ?><div class="fw-semibold"><?= e($c['horario_nombre']) ?></div><div class="small text-secondary"><?= e(substr($c['horario_inicio'],0,5)) ?>–<?= e(substr($c['horario_fin'],0,5)) ?></div><?php else: ?><span class="text-secondary">Sin asignar</span><?php endif; ?></td>
                        <td><?php if ($c['acceso_username']): ?><div class="fw-semibold"><?= e($c['acceso_username']) ?></div><span class="badge <?= (int)$c['acceso_activo'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$c['acceso_activo'] === 1 ? 'Activo' : 'Desactivado' ?></span><?php else: ?><span class="badge text-bg-warning">Sin acceso</span><?php endif; ?></td>
                        <td><?= e(ucfirst(strtolower($c['modalidad']))) ?></td>
                        <td><span class="badge status-<?= e(strtolower($c['estado'])) ?>"><?= e(ucwords(strtolower(str_replace('_',' ', $c['estado'])))) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-light btn-sm" href="<?= e(route_url('colaboradores/editar', ['id' => $c['id_colaborador']])) ?>">Editar</a>
                            <form method="post" action="<?= e(route_url('colaboradores/eliminar')) ?>" class="d-inline" data-confirm="¿Eliminar este colaborador? Solo se permitirá si todavía no tiene historial relacionado.">
                                <?= Csrf::field() ?><input type="hidden" name="id_colaborador" value="<?= e($c['id_colaborador']) ?>">
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
