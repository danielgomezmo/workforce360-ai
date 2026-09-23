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

    <div class="page-hero-main">

        <div class="page-hero-icon">
            <i class="fa-solid fa-chart-line"></i>
        </div>

        <div>
            <h1>Dashboard Operativo</h1>

            <p>
                Indicadores de asistencia y
                cumplimiento de jornada
            </p>
        </div>

    </div>

    <span class="badge status-badge px-3 py-2">
        FASE 5
    </span>

</div>


<!-- FILTRO POR FECHA -->

<div class="card shadow-sm mb-4">

    <div class="card-body">

        <form
            method="get"
            action="<?= e(base_url('public/index.php')) ?>"
            class="row g-3 align-items-end"
        >

            <input
                type="hidden"
                name="route"
                value="dashboard"
            >

            <div class="col-md-4">

                <label class="form-label fw-semibold">
                    Fecha de análisis
                </label>

                <input
                    type="date"
                    name="fecha"
                    class="form-control"
                    value="<?= e($selectedDate) ?>"
                >

            </div>

            <div class="col-md-auto">

                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    <i class="fa-solid fa-filter me-2"></i>
                    Consultar
                </button>

            </div>

            <div class="col-md-auto">

                <a
                    href="<?= e(route_url('dashboard')) ?>"
                    class="btn btn-outline-secondary"
                >
                    Hoy
                </a>

            </div>

        </form>

    </div>

</div>


<!-- ESTADO OPERATIVO -->

<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">

        <div class="card kpi-card kpi-teal h-100">

            <div class="card-body p-3">

                <div class="kpi-label">
                    Personal programado
                </div>

                <div class="kpi-value">
                    <?= e($operational['programados']) ?>
                </div>

                <div class="kpi-hint">
                    Para la fecha seleccionada
                </div>

                <i class="fa-solid fa-users kpi-icon"></i>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card kpi-card kpi-green h-100">

            <div class="card-body p-3">

                <div class="kpi-label">
                    Presentes
                </div>

                <div class="kpi-value">
                    <?= e($operational['presentes']) ?>
                </div>

                <div class="kpi-hint">
                    Con ingreso registrado
                </div>

                <i class="fa-solid fa-user-check kpi-icon"></i>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card kpi-card kpi-red h-100">

            <div class="card-body p-3">

                <div class="kpi-label">
                    Ausentes
                </div>

                <div class="kpi-value">
                    <?= e($operational['ausentes']) ?>
                </div>

                <div class="kpi-hint">
                    Sin ingreso registrado
                </div>

                <i class="fa-solid fa-user-xmark kpi-icon"></i>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card kpi-card kpi-warning h-100">

            <div class="card-body p-3">

                <div class="kpi-label">
                    Tardanzas
                </div>

                <div class="kpi-value">
                    <?= e($operational['tardanzas']) ?>
                </div>

                <div class="kpi-hint">
                    <?= e(
                        $operational['minutos_tardanza']
                        . ' min acumulados'
                    ) ?>
                </div>

                <i class="fa-solid fa-clock kpi-icon"></i>

            </div>

        </div>

    </div>

</div>


<!-- KPIs -->

<div class="card shadow-sm mb-4">

    <div class="card-header py-3 px-4">

        <h2 class="h6 fw-bold mb-0">

            <i class="fa-solid fa-gauge-high me-2"></i>

            Indicadores de Gestión

        </h2>

    </div>


    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-6 col-xl-3">

                <div class="metric-soft">

                    <span>
                        Índice de asistencia
                    </span>

                    <strong>
                        <?= e(
                            number_format(
                                $operational[
                                    'indice_asistencia'
                                ],
                                1
                            )
                        ) ?> %
                    </strong>

                    <small>
                        Presentes / programados
                    </small>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric-soft">

                    <span>
                        Índice de puntualidad
                    </span>

                    <strong>
                        <?= e(
                            number_format(
                                $operational[
                                    'indice_puntualidad'
                                ],
                                1
                            )
                        ) ?> %
                    </strong>

                    <small>
                        Puntuales / presentes
                    </small>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric-soft">

                    <span>
                        Cumplimiento de jornada
                    </span>

                    <strong>
                        <?= e(
                            number_format(
                                $operational[
                                    'cumplimiento_jornada'
                                ],
                                1
                            )
                        ) ?> %
                    </strong>

                    <small>
                        Horas trabajadas / programadas
                    </small>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric-soft">

                    <span>
                        Absentismo
                    </span>

                    <strong>
                        <?= e(
                            number_format(
                                $operational[
                                    'absentismo'
                                ],
                                1
                            )
                        ) ?> %
                    </strong>

                    <small>
                        Horas ausentes / programadas
                    </small>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- GRÁFICOS -->

<div class="row g-4 mb-4">

    <div class="col-xl-6">

        <div class="card shadow-sm h-100">

            <div class="card-header py-3 px-4">

                <h2 class="h6 fw-bold mb-0">

                    <i class="fa-solid fa-chart-pie me-2"></i>

                    Asistencia del día

                </h2>

            </div>

            <div class="card-body">

                <div class="chart-box">

                    <canvas
                        id="attendanceChart"
                        data-presentes="<?= e($operational['presentes']) ?>"
                        data-ausentes="<?= e($operational['ausentes']) ?>"
                    ></canvas>

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-6">

        <div class="card shadow-sm h-100">

            <div class="card-header py-3 px-4">

                <h2 class="h6 fw-bold mb-0">

                    <i class="fa-solid fa-chart-column me-2"></i>

                    Puntualidad

                </h2>

            </div>

            <div class="card-body">

                <div class="chart-box">

                    <canvas
                        id="entryChart"
                        data-puntuales="<?= e($operational['puntuales']) ?>"
                        data-tardanzas="<?= e($operational['tardanzas']) ?>"
                    ></canvas>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- DETALLE -->

<div class="card shadow-sm">

    <div class="card-header py-3 px-4">

        <h2 class="h6 fw-bold mb-0">

            <i class="fa-solid fa-list me-2"></i>

            Resumen de incidencias del día

        </h2>

    </div>

    <div class="table-responsive">

        <table class="table app-table mb-0">

            <thead>

                <tr>
                    <th>Indicador</th>
                    <th>Cantidad</th>
                </tr>

            </thead>

            <tbody>

                <tr>
                    <td>Vacaciones</td>
                    <td><?= e($operational['vacaciones']) ?></td>
                </tr>

                <tr>
                    <td>Descansos médicos</td>
                    <td><?= e($operational['descansos_medicos']) ?></td>
                </tr>

                <tr>
                    <td>Licencias</td>
                    <td><?= e($operational['licencias']) ?></td>
                </tr>

                <tr>
                    <td>Permisos</td>
                    <td><?= e($operational['permisos']) ?></td>
                </tr>

                <tr>
                    <td>Salidas anticipadas</td>
                    <td>
                        <?= e(
                            $operational[
                                'salidas_anticipadas'
                            ]
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Minutos perdidos por tardanza
                    </td>

                    <td>
                        <?= e(
                            $operational[
                                'minutos_tardanza'
                            ]
                        ) ?> min
                    </td>
                </tr>

                <tr>
                    <td>
                        Minutos perdidos por salida anticipada
                    </td>

                    <td>
                        <?= e(
                            $operational[
                                'minutos_salida_anticipada'
                            ]
                        ) ?> min
                    </td>
                </tr>

                <tr>
                    <td>
                        Faltas por validar
                    </td>

                    <td>
                        <?= e(
                            $operational[
                                'faltas_por_validar'
                            ]
                        ) ?>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

<?php endif; ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
