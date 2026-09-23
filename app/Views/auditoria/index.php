<?php
use App\Core\Auth;

Auth::requireAnyRole(['ADMINISTRADOR']);
require BASE_PATH . '/app/Views/layouts/header.php';

$rows = $result['rows'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);

$actionClass = static function (string $action): string {
    $action = strtoupper($action);
    if (str_contains($action, 'ELIMINAR') || str_contains($action, 'FALLIDO') || str_contains($action, 'ANULAR')) {
        return 'audit-danger';
    }
    if (str_contains($action, 'CREAR') || str_contains($action, 'EXITOSO') || str_contains($action, 'APROBAR')) {
        return 'audit-success';
    }
    if (str_contains($action, 'ACTUALIZAR') || str_contains($action, 'REVISAR')) {
        return 'audit-primary';
    }
    if (str_contains($action, 'MARCAR')) {
        return 'audit-info';
    }
    if (str_contains($action, 'SOLICITAR') || str_contains($action, 'REGISTRAR')) {
        return 'audit-warning';
    }
    return 'audit-neutral';
};

$actionLabel = static fn(string $action): string => ucwords(strtolower(str_replace('_', ' ', $action)));
$entityLabel = static fn(string $entity): string => ucwords(str_replace('_', ' ', $entity));

$baseFilterParams = array_filter([
    'desde' => $filters['desde'] ?? '',
    'hasta' => $filters['hasta'] ?? '',
    'usuario' => $filters['usuario'] ?? 0,
    'accion' => $filters['accion'] ?? '',
    'entidad' => $filters['entidad'] ?? '',
    'q' => $filters['q'] ?? '',
], static fn($value) => $value !== '' && $value !== 0 && $value !== '0');
?>

<div class="page-hero mb-4">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <h1>Auditoría y Trazabilidad</h1>
            <p>Historial inalterable de accesos, marcaciones y cambios realizados dentro de Workforce360 AI.</p>
        </div>
    </div>
    <span class="badge status-badge px-3 py-2">FASE 6</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card audit-stat-card h-100">
            <div class="card-body">
                <span class="audit-stat-label">Eventos registrados</span>
                <strong><?= e($summary['total'] ?? 0) ?></strong>
                <small>Histórico acumulado</small>
                <i class="fa-solid fa-database"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card audit-stat-card audit-stat-green h-100">
            <div class="card-body">
                <span class="audit-stat-label">Eventos de hoy</span>
                <strong><?= e($summary['hoy'] ?? 0) ?></strong>
                <small>Actividad del día</small>
                <i class="fa-solid fa-calendar-day"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card audit-stat-card audit-stat-blue h-100">
            <div class="card-body">
                <span class="audit-stat-label">Usuarios auditados</span>
                <strong><?= e($summary['usuarios'] ?? 0) ?></strong>
                <small>Usuarios con actividad</small>
                <i class="fa-solid fa-user-shield"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card audit-stat-card audit-stat-red h-100">
            <div class="card-body">
                <span class="audit-stat-label">Eventos sensibles</span>
                <strong><?= e($summary['sensibles'] ?? 0) ?></strong>
                <small>Fallos, anulaciones y eliminaciones</small>
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 audit-filter-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-4">
        <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros de auditoría</h2>
        <a class="btn btn-outline-success btn-sm" href="<?= e(route_url('auditoria/exportar', $baseFilterParams)) ?>">
            <i class="fa-solid fa-file-csv me-2"></i>Exportar CSV
        </a>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="auditoria">

            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= e($filters['desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Usuario</label>
                <select name="usuario" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (($catalogs['usuarios'] ?? []) as $auditUser): ?>
                        <option value="<?= e($auditUser['id_usuario']) ?>" <?= (int) ($filters['usuario'] ?? 0) === (int) $auditUser['id_usuario'] ? 'selected' : '' ?>>
                            <?= e($auditUser['nombre']) ?> (<?= e($auditUser['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Acción</label>
                <select name="accion" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach (($catalogs['acciones'] ?? []) as $action): ?>
                        <option value="<?= e($action) ?>" <?= ($filters['accion'] ?? '') === $action ? 'selected' : '' ?>>
                            <?= e($actionLabel($action)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Módulo</label>
                <select name="entidad" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (($catalogs['entidades'] ?? []) as $entity): ?>
                        <option value="<?= e($entity) ?>" <?= ($filters['entidad'] ?? '') === $entity ? 'selected' : '' ?>>
                            <?= e($entityLabel($entity)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Buscar</label>
                <input type="search" name="q" class="form-control" placeholder="Usuario, IP, registro..." value="<?= e($filters['q'] ?? '') ?>">
            </div>
            <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                <a href="<?= e(route_url('auditoria')) ?>" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-rotate-left me-2"></i>Restablecer
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Consultar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm audit-table-card">
    <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left me-2"></i>Bitácora de eventos</h2>
            <small class="text-muted"><?= e($total) ?> evento(s) encontrados para los filtros seleccionados.</small>
        </div>
        <span class="badge text-bg-light border">Página <?= e($page) ?> de <?= e($pages) ?></span>
    </div>

    <div class="table-responsive">
        <table class="table app-table audit-table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Fecha / hora</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>Registro</th>
                    <th>IP</th>
                    <th class="text-end">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fa-solid fa-magnifying-glass d-block fs-3 mb-2 opacity-50"></i>
                            No se encontraron eventos para los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-nowrap">
                                <strong><?= e(date('d/m/Y', strtotime($row['fecha_evento']))) ?></strong>
                                <small class="d-block text-muted"><?= e(date('H:i:s', strtotime($row['fecha_evento']))) ?></small>
                            </td>
                            <td>
                                <strong><?= e($row['usuario_nombre'] ?? 'Sistema') ?></strong>
                                <?php if (!empty($row['username'])): ?>
                                    <small class="d-block text-muted">@<?= e($row['username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="audit-role-chip"><?= e($row['roles'] ?? 'SIN ROL') ?></span></td>
                            <td><span class="audit-action <?= e($actionClass((string) $row['accion'])) ?>"><?= e($actionLabel((string) $row['accion'])) ?></span></td>
                            <td><?= e($entityLabel((string) $row['entidad'])) ?></td>
                            <td><?= $row['entidad_id'] !== null ? '#' . e($row['entidad_id']) : '—' ?></td>
                            <td class="text-nowrap"><code><?= e($row['ip_origen'] ?: '—') ?></code></td>
                            <td class="text-end">
                                <a href="<?= e(route_url('auditoria/detalle', ['id' => $row['id_auditoria']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle del evento">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <div class="card-footer bg-white py-3">
            <nav aria-label="Paginación de auditoría">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap">
                    <?php $prev = max(1, $page - 1); $next = min($pages, $page + 1); ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(route_url('auditoria', array_merge($baseFilterParams, ['page' => $prev]))) ?>">Anterior</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($pages, $page + 2);
                    for ($p = $start; $p <= $end; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(route_url('auditoria', array_merge($baseFilterParams, ['page' => $p]))) ?>"><?= e($p) ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(route_url('auditoria', array_merge($baseFilterParams, ['page' => $next]))) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
