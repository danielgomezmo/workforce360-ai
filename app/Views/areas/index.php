<?php
use App\Core\Csrf;
$title = 'Áreas · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-primary fw-semibold mb-1">Configuración organizacional</p>
        <h1 class="h2 fw-bold mb-1">Áreas</h1>
        <p class="text-secondary mb-0">Administra las áreas que agrupan a los colaboradores.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(route_url('areas/nuevo')) ?>">+ Nueva área</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 app-table">
                <thead>
                <tr>
                    <th>Área</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$areas): ?>
                    <tr><td colspan="4" class="text-center text-secondary py-5">Todavía no hay áreas registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($areas as $area): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($area['nombre']) ?></div><div class="small text-secondary">ID <?= e($area['id_area']) ?></div></td>
                        <td class="text-secondary"><?= e($area['descripcion'] ?: '—') ?></td>
                        <td><span class="badge <?= $area['activo'] ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' ?>"><?= $area['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-light btn-sm" href="<?= e(route_url('areas/editar', ['id' => $area['id_area']])) ?>">Editar</a>
                            <form method="post" action="<?= e(route_url('areas/eliminar')) ?>" class="d-inline" data-confirm="¿Eliminar esta área? Esta acción solo se permitirá si no tiene información relacionada.">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id_area" value="<?= e($area['id_area']) ?>">
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
