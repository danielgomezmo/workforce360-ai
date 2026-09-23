<?php
$title = 'Centro de Reportes · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="page-hero mb-4">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div>
            <h1>Centro de Reportes</h1>
            <p>Consulta, analiza, imprime y exporta información operativa de Workforce360 AI.</p>
        </div>
    </div>
    <span class="badge status-badge px-3 py-2">FASE 6</span>
</div>

<div class="row g-4">
    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <h5 class="fw-bold">Reporte de Asistencia</h5>
                <p class="text-secondary">Marcaciones, puntualidad, tardanzas, entradas, salidas y jornada registrada.</p>
                <a href="<?= e(route_url('reportes/asistencia')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right me-2"></i>Generar reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-clock"></i></div>
                <h5 class="fw-bold">Reporte de Tardanzas</h5>
                <p class="text-secondary">Detalle de llegadas tardías, minutos acumulados, promedio y tardanza máxima.</p>
                <a href="<?= e(route_url('reportes/tardanzas')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right me-2"></i>Generar reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
                <h5 class="fw-bold">Salidas Anticipadas</h5>
                <p class="text-secondary">Retiros antes del horario programado y minutos de capacidad laboral perdidos.</p>
                <a href="<?= e(route_url('reportes/salidas-anticipadas')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right me-2"></i>Generar reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-file-medical"></i></div>
                <h5 class="fw-bold">Reporte de Incidencias</h5>
                <p class="text-secondary">Vacaciones, licencias, permisos, descansos médicos y estado de solicitudes.</p>
                <a href="<?= e(route_url('reportes/incidencias')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right me-2"></i>Generar reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-business-time"></i></div>
                <h5 class="fw-bold">Horas Trabajadas</h5>
                <p class="text-secondary">Horas programadas, horas efectivas, diferencia y cumplimiento de jornada.</p>
                <a href="<?= e(route_url('reportes/horas-trabajadas')) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right me-2"></i>Generar reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card report-menu-card h-100 report-menu-card-info">
            <div class="card-body p-4">
                <div class="report-menu-icon"><i class="fa-solid fa-download"></i></div>
                <h5 class="fw-bold">Exportación Profesional</h5>
                <p class="text-secondary">Todos los reportes incluyen Excel, PDF e impresión con el diseño institucional DEVIOZ.</p>
                <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">Excel · PDF · Imprimir</span>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mt-4 mb-0">
    <i class="fa-solid fa-circle-info me-2"></i>
    Los reportes respetan los permisos del sistema. Los supervisores solo visualizan información de su equipo.
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
