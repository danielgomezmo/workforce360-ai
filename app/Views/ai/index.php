<?php
$title = 'Workforce AI · Pronósticos';
require BASE_PATH . '/app/Views/layouts/header.php';

$dailyIndex = (float) ($daily['indice_asistencia_estimado'] ?? 0);
$dailyProgramados = (int) ($daily['programados'] ?? 0);
$dailyPresentes = (int) ($daily['presentes_estimados'] ?? 0);
$dailyAusentes = (int) ($daily['ausentes_estimados'] ?? 0);
$dailyOperational = (bool) ($daily['dia_operativo'] ?? false);
$modelName = (string) ($model['modelo'] ?? $daily['modelo'] ?? 'Sin modelo');
$modelVersion = (string) ($model['version_modelo'] ?? $daily['version_modelo'] ?? '—');
$modelState = (string) ($model['estado_modelo'] ?? $evaluation['estado_modelo'] ?? 'EXPERIMENTAL');
$temporalBeatsBaseline = (bool) ($evaluation['ml_supera_baseline'] ?? false);
$weeklyDetail = is_array($weekly['detalle'] ?? null) ? $weekly['detalle'] : [];
$dailyFactors = is_array($daily['factores_contexto'] ?? null) ? $daily['factores_contexto'] : [];
$predictionHistory = is_array($predictionHistory ?? null) ? $predictionHistory : [];
$predictionSummary = is_array($predictionSummary ?? null) ? $predictionSummary : [];

$stateClass = $modelState === 'VALIDADO'
    ? 'bg-success-subtle text-success-emphasis'
    : 'bg-warning-subtle text-warning-emphasis';
?>

<div class="page-hero mb-4">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-brain"></i></div>
        <div>
            <h1>Workforce AI</h1>
            <p>Pronóstico de asistencia y disponibilidad laboral mediante el microservicio predictivo.</p>
        </div>
    </div>
    <span class="badge status-badge px-3 py-2">FASE 7 · IA</span>
</div>

<?php if (!$apiAvailable): ?>
    <div class="alert alert-danger shadow-sm border-0">
        <div class="d-flex gap-3 align-items-start">
            <i class="fa-solid fa-triangle-exclamation fs-4 mt-1"></i>
            <div>
                <strong>Servicio de IA no disponible.</strong>
                <div class="small mt-1"><?= e($apiError ?? 'Inicia FastAPI en el puerto 8000.') ?></div>
                <div class="small mt-2"><code>.\.venv\Scripts\python.exe -m uvicorn app.main:app --reload --port 8000</code></div>
            </div>
        </div>
    </div>
<?php else: ?>

    <?php if ($apiError): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= e($apiError) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?= e(base_url('public/index.php')) ?>" class="row g-3 align-items-end">
                <input type="hidden" name="route" value="ai">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fecha de pronóstico diario</label>
                    <input type="date" name="fecha" class="form-control" value="<?= e($selectedDate) ?>">
                    <div class="form-text">Déjalo vacío para usar el próximo día operativo disponible.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Horizonte semanal</label>
                    <select name="dias" class="form-select">
                        <?php foreach ([5, 7, 10, 14] as $option): ?>
                            <option value="<?= $option ?>" <?= $selectedDays === $option ? 'selected' : '' ?>><?= $option ?> días operativos</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-rotate me-2"></i>Actualizar pronóstico</button>
                </div>
                <div class="col-md-auto">
                    <a href="<?= e(route_url('ai')) ?>" class="btn btn-outline-secondary">Restablecer</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card kpi-card kpi-teal h-100">
                <div class="card-body p-3">
                    <div class="kpi-label">Personal programado</div>
                    <div class="kpi-value"><?= e($dailyProgramados) ?></div>
                    <div class="kpi-hint"><?= e($daily['fecha'] ?? 'Sin fecha') ?></div>
                    <i class="fa-solid fa-users kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card kpi-card kpi-green h-100">
                <div class="card-body p-3">
                    <div class="kpi-label">Presentes estimados</div>
                    <div class="kpi-value"><?= e($dailyPresentes) ?></div>
                    <div class="kpi-hint">Pronóstico del modelo</div>
                    <i class="fa-solid fa-user-check kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card kpi-card kpi-red h-100">
                <div class="card-body p-3">
                    <div class="kpi-label">Ausentes estimados</div>
                    <div class="kpi-value"><?= e($dailyAusentes) ?></div>
                    <div class="kpi-hint">Programados − presentes</div>
                    <i class="fa-solid fa-user-xmark kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card kpi-card kpi-blue h-100">
                <div class="card-body p-3">
                    <div class="kpi-label">Asistencia estimada</div>
                    <div class="kpi-value"><?= e(number_format($dailyIndex, 1)) ?>%</div>
                    <div class="kpi-hint">Disponibilidad prevista</div>
                    <i class="fa-solid fa-chart-line kpi-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-chart-area me-2"></i>Pronóstico de los próximos días operativos</h2>
                        <div class="small text-secondary">Evolución estimada del índice de asistencia.</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary-emphasis"><?= e((int) ($weekly['dias_operativos'] ?? 0)) ?> días</span>
                </div>
                <div class="card-body">
                    <div class="chart-box" style="min-height: 315px;">
                        <canvas id="aiWeeklyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-microchip me-2"></i>Estado del modelo</h2></div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-2 align-items-start mb-3">
                        <div>
                            <div class="small text-uppercase fw-bold text-secondary">Modelo activo</div>
                            <div class="fw-bold fs-5"><?= e($modelName) ?></div>
                            <div class="small text-secondary">Versión <?= e($modelVersion) ?></div>
                        </div>
                        <span class="badge <?= e($stateClass) ?> px-3 py-2"><?= e($modelState) ?></span>
                    </div>

                    <div class="metric-soft mb-2">
                        <span>MAE holdout</span>
                        <strong><?= e(number_format((float) ($model['mae_holdout'] ?? $daily['error_mae_holdout'] ?? 0), 3)) ?></strong>
                        <small>Error medio en puntos porcentuales.</small>
                    </div>

                    <div class="metric-soft mb-2">
                        <span>R² holdout</span>
                        <strong><?= e(number_format((float) ($model['r2_holdout'] ?? 0), 3)) ?></strong>
                        <small>Capacidad explicativa observada en el corte temporal.</small>
                    </div>

                    <div class="small mt-3 <?= $temporalBeatsBaseline ? 'text-success' : 'text-warning-emphasis' ?>">
                        <i class="fa-solid <?= $temporalBeatsBaseline ? 'fa-circle-check' : 'fa-flask' ?> me-1"></i>
                        <?= $temporalBeatsBaseline
                            ? 'El modelo ML superó al baseline en la validación temporal.'
                            : 'El modelo se mantiene experimental: el baseline fue más estable en la validación temporal.' ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-database me-2"></i>Dataset utilizado</h2></div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary small">Registros</span><strong><?= e($dataset['registros'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary small">Desde</span><strong><?= e($dataset['fecha_inicio'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary small">Hasta</span><strong><?= e($dataset['fecha_fin'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-secondary small">Asistencia histórica</span><strong><?= e(number_format((float) ($dataset['asistencia_promedio'] ?? 0), 2)) ?>%</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-calendar-day me-2"></i>Detalle diario</h2></div>
                <div class="card-body p-4">
                    <?php if (!$dailyOperational): ?>
                        <div class="alert alert-info mb-0">La fecha seleccionada no tiene personal programado.</div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Fecha</span><strong><?= e($daily['fecha'] ?? '—') ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Asistencia estimada</span><strong><?= e(number_format($dailyIndex, 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Intervalo aproximado 90%</span><strong><?= e(number_format((float) ($daily['intervalo_aprox_90']['min'] ?? 0), 2)) ?>% – <?= e(number_format((float) ($daily['intervalo_aprox_90']['max'] ?? 0), 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2"><span class="text-secondary">Estado</span><strong><?= $dailyIndex >= 90 ? 'Cobertura normal' : 'Revisar cobertura' ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-table-list me-2"></i>Detalle semanal</h2></div>
                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                        <tr><th>Fecha</th><th>Programados</th><th>Asistencia</th><th>Presentes</th><th>Ausentes</th></tr>
                        </thead>
                        <tbody>
                        <?php if (!$weeklyDetail): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-5">No hay pronóstico semanal disponible.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($weeklyDetail as $row): ?>
                            <tr>
                                <td><strong><?= e($row['fecha'] ?? '—') ?></strong></td>
                                <td><?= e($row['programados'] ?? 0) ?></td>
                                <td><span class="badge bg-info-subtle text-info-emphasis"><?= e(number_format((float) ($row['indice_asistencia_estimado'] ?? 0), 2)) ?>%</span></td>
                                <td><?= e($row['presentes_estimados'] ?? 0) ?></td>
                                <td><?= e($row['ausentes_estimados'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($historyError): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <i class="fa-solid fa-database me-2"></i><?= e($historyError) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4">
                    <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-magnifying-glass-chart me-2"></i>¿Por qué se generó esta predicción?</h2>
                    <div class="small text-secondary">Variables de contexto que recibió el modelo para la fecha seleccionada.</div>
                </div>
                <div class="card-body p-4">
                    <?php if (!$dailyOperational || !$dailyFactors): ?>
                        <div class="text-secondary">No hay factores disponibles para esta fecha.</div>
                    <?php else: ?>
                        <?php
                        $avg7 = (float) ($dailyFactors['asistencia_media_7'] ?? 0);
                        $avg30 = (float) ($dailyFactors['asistencia_media_30'] ?? 0);
                        $trendText = abs($avg7 - $avg30) < 0.25
                            ? 'Estable frente al promedio de 30 días'
                            : ($avg7 > $avg30 ? 'Reciente ligeramente por encima del promedio de 30 días' : 'Reciente ligeramente por debajo del promedio de 30 días');
                        ?>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Día de la semana</span><strong><?= e($dailyFactors['dia_semana_nombre'] ?? '—') ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Personal programado</span><strong><?= e($dailyFactors['programados'] ?? 0) ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Asistencia del último día histórico</span><strong><?= e(number_format((float) ($dailyFactors['asistencia_ultimo_dia'] ?? 0), 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Asistencia de referencia hace 7 días</span><strong><?= e(number_format((float) ($dailyFactors['asistencia_hace_7_dias'] ?? 0), 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Promedio reciente de 7 días</span><strong><?= e(number_format($avg7, 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Promedio de 30 días</span><strong><?= e(number_format($avg30, 2)) ?>%</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Tardanza media reciente</span><strong><?= e(number_format((float) ($dailyFactors['tardanza_media_7'] ?? 0), 2)) ?>%</strong></div>
                        <div class="mt-3 small"><strong>Lectura de tendencia:</strong> <?= e($trendText) ?>.</div>
                        <div class="small text-secondary mt-2">Estos valores describen el contexto de entrada del modelo. No deben interpretarse como causas individuales de la predicción.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left me-2"></i>Seguimiento de pronósticos</h2>
                        <div class="small text-secondary">Compara predicciones guardadas con la asistencia real cuando la jornada ya terminó.</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary-emphasis"><?= e((int) ($predictionSummary['guardados'] ?? 0)) ?> guardados</span>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0 border-bottom">
                        <div class="col-4 p-3 text-center border-end">
                            <div class="small text-secondary">Evaluados</div>
                            <div class="fw-bold fs-5"><?= e((int) ($predictionSummary['evaluados'] ?? 0)) ?></div>
                        </div>
                        <div class="col-4 p-3 text-center border-end">
                            <div class="small text-secondary">Pendientes</div>
                            <div class="fw-bold fs-5"><?= e((int) ($predictionSummary['pendientes'] ?? 0)) ?></div>
                        </div>
                        <div class="col-4 p-3 text-center">
                            <div class="small text-secondary">Error real medio</div>
                            <div class="fw-bold fs-5"><?= ($predictionSummary['error_medio_pp'] ?? null) === null ? '—' : e(number_format((float) $predictionSummary['error_medio_pp'], 2)) . ' pp' ?></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table app-table align-middle mb-0">
                            <thead>
                            <tr><th>Fecha</th><th>Predicción</th><th>Real</th><th>Error</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                            <?php if (!$predictionHistory): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-5">Todavía no hay predicciones guardadas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($predictionHistory as $row): ?>
                                <?php
                                $comparisonState = (string) ($row['estado_comparacion'] ?? 'FUTURO');
                                $badgeClass = $comparisonState === 'EVALUADO'
                                    ? 'bg-success-subtle text-success-emphasis'
                                    : ($comparisonState === 'EN_CURSO' ? 'bg-info-subtle text-info-emphasis' : 'bg-warning-subtle text-warning-emphasis');
                                $stateLabel = match ($comparisonState) {
                                    'EVALUADO' => 'Evaluado',
                                    'EN_CURSO' => 'En curso',
                                    'SIN_DATOS' => 'Sin datos',
                                    default => 'Pendiente',
                                };
                                ?>
                                <tr>
                                    <td><strong><?= e(date('d/m/Y', strtotime((string) ($row['fecha'] ?? 'now')))) ?></strong></td>
                                    <td><?= e(number_format((float) ($row['prediccion'] ?? 0), 2)) ?>%</td>
                                    <td><?= ($row['real'] ?? null) === null ? '—' : e(number_format((float) $row['real'], 2)) . '%' ?></td>
                                    <td><?= ($row['error_absoluto_pp'] ?? null) === null ? '—' : e(number_format((float) $row['error_absoluto_pp'], 2)) . ' pp' ?></td>
                                    <td><span class="badge <?= e($badgeClass) ?>"><?= e($stateLabel) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm mb-0">
        <i class="fa-solid fa-circle-info me-2"></i>
        Los datos utilizados actualmente son sintéticos y sirven para validar técnicamente el flujo predictivo. El rendimiento deberá reevaluarse con datos reales antes de utilizar el modelo para decisiones operativas.
    </div>

    <script>
    window.addEventListener('load', function () {
        const canvas = document.getElementById('aiWeeklyChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const detail = <?= json_encode($weeklyDetail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const labels = detail.map(item => item.fecha || '');
        const values = detail.map(item => Number(item.indice_asistencia_estimado || 0));

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Asistencia estimada (%)',
                    data: values,
                    borderWidth: 2,
                    tension: 0.25,
                    fill: false,
                    pointRadius: 4,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        suggestedMin: 80,
                        suggestedMax: 100,
                        ticks: {
                            callback: value => value + '%'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    });
    </script>

<?php endif; ?>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
