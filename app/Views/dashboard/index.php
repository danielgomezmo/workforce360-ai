<?php
use App\Core\Auth;
$title = 'Dashboard · Workforce360 AI';
$canManage = Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH']);
$canViewMarks = Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR']);
require BASE_PATH . '/app/Views/layouts/header.php';
$fmtMinutes = static function (int $minutes): string {
    if ($minutes <= 0) return '0 min';
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return $hours > 0 ? sprintf('%dh %02dm', $hours, $mins) : $mins . ' min';
};
?>

<?php if ($isCollaboratorPanel): ?>
    <div class="page-hero mb-3">
        <div class="page-hero-main">
            <div class="page-hero-icon"><i class="fa-solid fa-user-clock"></i></div>
            <div>
                <h1>Mi Resumen</h1>
                <p>Información personal de tu asistencia y solicitudes del mes actual.</p>
            </div>
        </div>
        <span class="badge status-badge px-3 py-2">FASE 4</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a class="text-decoration-none" href="<?= e(route_url('mi-historial')) ?>">
                <div class="card kpi-card kpi-blue h-100"><div class="card-body p-3"><div class="kpi-label">Asistencias del mes</div><div class="kpi-value"><?= e($personalStats['total'] ?? 0) ?></div><div class="kpi-hint">Registros personales</div><i class="fa-solid fa-fingerprint kpi-icon"></i></div></div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="text-decoration-none" href="<?= e(route_url('mi-historial', ['estado' => 'PUNTUAL'])) ?>">
                <div class="card kpi-card kpi-green h-100"><div class="card-body p-3"><div class="kpi-label">Puntuales</div><div class="kpi-value"><?= e($personalStats['puntuales'] ?? 0) ?></div><div class="kpi-hint">Entradas dentro de tolerancia</div><i class="fa-solid fa-circle-check kpi-icon"></i></div></div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="text-decoration-none" href="<?= e(route_url('mi-historial', ['estado' => 'TARDANZA'])) ?>">
                <div class="card kpi-card kpi-warning h-100"><div class="card-body p-3"><div class="kpi-label">Tardanzas</div><div class="kpi-value"><?= e($personalStats['tardanzas'] ?? 0) ?></div><div class="kpi-hint"><?= e($fmtMinutes((int)($personalStats['minutos_tardanza'] ?? 0))) ?> acumulados</div><i class="fa-solid fa-clock kpi-icon"></i></div></div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="text-decoration-none" href="<?= e(route_url('mis-solicitudes')) ?>">
                <div class="card kpi-card kpi-purple h-100"><div class="card-body p-3"><div class="kpi-label">Solicitudes pendientes</div><div class="kpi-value"><?= e($personalStats['solicitudes_pendientes'] ?? 0) ?></div><div class="kpi-hint">Por revisar</div><i class="fa-solid fa-file-circle-question kpi-icon"></i></div></div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center gap-2">
                    <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Mis últimos registros</h2>
                    <a href="<?= e(route_url('mi-historial')) ?>" class="btn btn-sm btn-outline-secondary">Ver historial</a>
                </div>
                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Tardanza</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php if (!$recent): ?><tr><td colspan="5" class="text-center text-secondary py-5">Aún no tienes marcaciones registradas.</td></tr><?php endif; ?>
                        <?php foreach ($recent as $row): ?>
                            <tr><td><strong><?= e(date('d/m/Y', strtotime($row['fecha']))) ?></strong></td><td><?= e($row['hora_entrada'] ? date('H:i:s', strtotime($row['hora_entrada'])) : '—') ?></td><td><?= e($row['hora_salida'] ? date('H:i:s', strtotime($row['hora_salida'])) : '—') ?></td><td><?= (int)$row['minutos_tardanza'] > 0 ? e($row['minutos_tardanza'] . ' min') : '—' ?></td><td><span class="badge <?= $row['resultado_entrada'] === 'TARDANZA' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis' ?>"><?= e($row['estado']) ?></span></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-bolt me-2"></i>Accesos rápidos</h2></div>
                <div class="card-body p-4 d-grid gap-2">
                    <a class="btn btn-primary text-start" href="<?= e(route_url('mi-asistencia')) ?>"><i class="fa-solid fa-fingerprint me-2"></i>Registrar mi asistencia</a>
                    <a class="btn btn-outline-primary text-start" href="<?= e(route_url('mi-historial')) ?>"><i class="fa-solid fa-clock-rotate-left me-2"></i>Ver mi historial</a>
                    <a class="btn btn-outline-secondary text-start" href="<?= e(route_url('mis-solicitudes')) ?>"><i class="fa-solid fa-file-circle-check me-2"></i>Mis solicitudes</a>
                </div>
            </div>
            <div class="card shadow-sm"><div class="card-body p-4"><div class="small text-uppercase fw-bold text-secondary mb-2">Mi situación laboral</div><div class="fw-semibold mb-1"><?= e($colaborador['estado'] ?? 'Sin información') ?></div><div class="small text-secondary"><?= e(($colaborador['cargo'] ?? 'Colaborador') . ' · ' . ($colaborador['area_nombre'] ?? 'Sin área')) ?></div></div></div>
        </div>
    </div>
<?php else: ?>
    <div class="page-hero mb-3">
        <div class="page-hero-main"><div class="page-hero-icon"><i class="fa-solid fa-briefcase"></i></div><div><h1>Panel de Control Operativo</h1><p>Monitoreo general de la operación — <?= e(date('Y-m-d')) ?></p></div></div>
        <span class="badge status-badge px-3 py-2">FASE 4</span>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['Colaboradores', $stats['colaboradores'], 'Total en plantilla', 'fa-users', 'kpi-teal', $canManage ? 'colaboradores' : null],
            ['Personal activo', $stats['activos'], 'Estado ACTIVO', 'fa-user-check', 'kpi-green', $canManage ? 'colaboradores' : null],
            ['Asistencias hoy', $stats['marcaciones_hoy'], 'Entradas registradas', 'fa-fingerprint', 'kpi-blue', $canViewMarks ? 'marcaciones' : null],
            ['Tardanzas hoy', $stats['tardanzas_hoy'], 'Fuera de tolerancia', 'fa-clock', 'kpi-warning', $canViewMarks ? 'marcaciones' : null],
            ['Incidencias pendientes', $stats['incidencias_pendientes'], 'Por revisar', 'fa-file-circle-question', 'kpi-purple', $canViewMarks ? 'incidencias' : null],
            ['Horarios activos', $stats['horarios'], 'Turnos configurados', 'fa-calendar-days', 'kpi-cyan', $canManage ? 'horarios' : null],
        ];
        foreach ($cards as [$label, $value, $hint, $icon, $tone, $route]): ?>
            <div class="col-6 col-lg-4 col-xl-2"><?php if ($route): ?><a href="<?= e(route_url($route)) ?>" class="text-decoration-none"><?php endif; ?><div class="card kpi-card <?= e($tone) ?> h-100"><div class="card-body p-3"><div class="kpi-label"><?= e($label) ?></div><div class="kpi-value"><?= e($value) ?></div><div class="kpi-hint"><?= e($hint) ?></div><i class="fa-solid <?= e($icon) ?> kpi-icon"></i></div></div><?php if ($route): ?></a><?php endif; ?></div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-xl-8"><div class="card shadow-sm h-100"><div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-secondary"></i>Avance del proyecto</h2></div><div class="card-body p-4"><div class="phase-list"><div class="phase-item done"><strong>Fase 1</strong><span>MVC, base de datos, login, roles, seguridad inicial y dashboard.</span></div><div class="phase-item done"><strong>Fase 2</strong><span>CRUD de áreas, horarios y colaboradores, validaciones y auditoría.</span></div><div class="phase-item done"><strong>Fase 3</strong><span>Ingreso/salida, tardanzas, salida anticipada, turnos nocturnos, IP y auditoría.</span></div><div class="phase-item done"><strong>Fase 4</strong><span>Solicitudes e incidencias: vacaciones, licencias, permisos, descansos médicos, aprobación y bloqueo de marcación.</span></div><div class="phase-item next"><strong>Fase 5</strong><span>KPIs, reportes, auditoría avanzada y dashboards.</span></div><div class="phase-item"><strong>Fase 6</strong><span>Workforce AI con servicio Python/FastAPI y pronósticos.</span></div></div></div></div></div>
        <div class="col-xl-4">
            <div class="card shadow-sm mb-4"><div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-bolt me-2 text-secondary"></i>Accesos rápidos</h2></div><div class="card-body p-4"><div class="d-grid gap-2"><?php if ($canViewMarks): ?><a class="btn btn-primary text-start" href="<?= e(route_url('marcaciones')) ?>"><i class="fa-solid fa-clock me-2"></i>Supervisar asistencias</a><a class="btn btn-outline-primary text-start" href="<?= e(route_url('incidencias')) ?>"><i class="fa-solid fa-clipboard-check me-2"></i>Gestionar incidencias</a><?php endif; ?><?php if ($canManage): ?><a class="btn btn-outline-secondary text-start" href="<?= e(route_url('colaboradores')) ?>"><i class="fa-solid fa-users me-2"></i>Gestionar colaboradores</a><?php endif; ?></div></div></div>
            <div class="card shadow-sm"><div class="card-body p-4"><div class="small text-uppercase fw-bold text-secondary mb-2">Motor de asistencia</div><div class="fw-semibold mb-1">Reglas activas</div><div class="small text-secondary">Tolerancia, tardanza, salida anticipada, bloqueos por cese e incidencias aprobadas.</div></div></div>
        </div>
    </div>
<?php endif; ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
