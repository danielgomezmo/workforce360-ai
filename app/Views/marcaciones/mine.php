<?php
use App\Core\Csrf;
$title = 'Mi asistencia · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';

$fmtTime = static function (?string $value): string {
    if (!$value) return '—';
    return date('H:i:s', strtotime($value));
};
$fmtShortTime = static function (?string $value): string {
    if (!$value) return '—:—';
    return date('H:i', strtotime($value));
};
$fmtMinutes = static function (int $minutes): string {
    if ($minutes <= 0) return '—';
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return $hours > 0 ? sprintf('%02d:%02d', $hours, $mins) : $mins . ' m';
};
$days = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
$dayNames = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$monthNames = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$todayLabel = ($dayNames[date('l')] ?? date('l')) . ', ' . date('d') . ' de ' . ($monthNames[(int)date('n')] ?? date('m')) . ' de ' . date('Y');
?>

<?php if (!$colaborador): ?>
    <div class="page-hero mb-4">
        <div class="page-hero-main"><div class="page-hero-icon"><i class="fa-solid fa-user-slash"></i></div><div><h1>Mi asistencia</h1><p>Cuenta sin colaborador vinculado</p></div></div>
    </div>
    <div class="alert alert-warning border-0 shadow-sm p-4"><strong>No se puede abrir el panel de marcación.</strong><br><?= e($blockReason) ?></div>
<?php else: ?>
    <div class="collab-welcome mb-3">
        <h1 class="fw-bold mb-1">Bienvenido, <?= e($colaborador['nombres']) ?></h1>
        <div class="date-line"><i class="fa-regular fa-calendar me-1"></i>Fecha de hoy: <?= e(date('Y-m-d')) ?></div>
    </div>

    <div class="row g-4 align-items-stretch">
        <div class="col-xl-5">
            <div class="card attendance-card shadow-sm h-100">
                <div class="card-header text-center">Módulo de Registro Diario</div>
                <div class="card-body p-3 p-md-4">
                    <div class="reloj-container">
                        <div class="clock-date"><?= e($todayLabel) ?></div>
                        <div id="serverClock" data-server-time="<?= e(date(DATE_ATOM)) ?>"><?= e(date('h:i:s A')) ?></div>
                    </div>

                    <?php if ($horario): ?>
                        <div class="schedule-strip">
                            <div><span>Horario</span><strong><?= e($horario['nombre']) ?></strong></div>
                            <div><span>Jornada</span><strong><?= e($fmtShortTime($horario['hora_inicio'])) ?> – <?= e($fmtShortTime($horario['hora_fin'])) ?></strong></div>
                            <div><span>Tolerancia</span><strong><?= e((int)$horario['minutos_tolerancia']) ?> min</strong></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$canMark): ?>
                        <div class="alert alert-warning attendance-message mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($blockReason ?: 'La marcación no está habilitada en este momento.') ?></div>
                    <?php endif; ?>

                    <?php if (!$marcacion || empty($marcacion['hora_entrada'])): ?>
                        <form method="post" action="<?= e(route_url('marcaciones/entrada')) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-primary w-100 py-2" <?= !$canMark ? 'disabled' : '' ?>><i class="fa-solid fa-fingerprint me-2"></i>MARCAR INGRESO</button>
                        </form>
                    <?php elseif (empty($marcacion['hora_salida'])): ?>
                        <div class="alert <?= $marcacion['resultado_entrada'] === 'TARDANZA' ? 'alert-warning' : 'alert-success' ?> attendance-message mb-3">
                            <strong>Ingreso registrado:</strong> <?= e($fmtTime($marcacion['hora_entrada'])) ?> · <?= e($marcacion['resultado_entrada']) ?>
                            <?php if ((int)$marcacion['minutos_tardanza'] > 0): ?> (<?= e($marcacion['minutos_tardanza']) ?> min)<?php endif; ?>
                        </div>
                        <form method="post" action="<?= e(route_url('marcaciones/salida')) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-primary w-100 py-2"><i class="fa-solid fa-right-from-bracket me-2"></i>MARCAR SALIDA</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success attendance-message mb-3">
                            <i class="fa-solid fa-circle-check me-2"></i><strong>Jornada cerrada.</strong><br>
                            Entrada <?= e($fmtTime($marcacion['hora_entrada'])) ?> · Salida <?= e($fmtTime($marcacion['hora_salida'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($marcacion): ?>
                        <div class="attendance-status-list mt-3">
                            <div><span>Estado de entrada</span><strong><?= e($marcacion['resultado_entrada']) ?></strong></div>
                            <div><span>Tardanza</span><strong><?= (int)$marcacion['minutos_tardanza'] > 0 ? e($marcacion['minutos_tardanza']) . ' min' : '—' ?></strong></div>
                            <div><span>Salida anticipada</span><strong><?= (int)$marcacion['minutos_salida_anticipada'] > 0 ? e($marcacion['minutos_salida_anticipada']) . ' min' : '—' ?></strong></div>
                            <div><span>Tiempo trabajado</span><strong><?= e($fmtMinutes((int)$marcacion['minutos_trabajados'])) ?></strong></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7" id="historial">
            <div class="card recent-card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-list me-2"></i>Mis Registros Recientes</span>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(route_url('mi-historial')) ?>">Ver todo <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Tardanza</th><th>Horas</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php if (!$historial): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-5">Todavía no hay marcaciones.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($historial as $row):
                            $isLate = ($row['resultado_entrada'] ?? '') === 'TARDANZA' || (int)($row['minutos_tardanza'] ?? 0) > 0;
                            $status = $row['estado'] ?? ($isLate ? 'TARDANZA' : 'PRESENTE');
                        ?>
                            <tr>
                                <td class="fw-semibold"><?= e($row['fecha']) ?></td>
                                <td><?= e($fmtTime($row['hora_entrada'])) ?></td>
                                <td><?= e($fmtTime($row['hora_salida'])) ?></td>
                                <td class="<?= $isLate ? 'text-danger' : 'text-secondary' ?>"><?= $isLate ? e((int)$row['minutos_tardanza']) . ' m' : '—' ?></td>
                                <td class="fw-semibold text-secondary"><?= e($fmtMinutes((int)$row['minutos_trabajados'])) ?></td>
                                <td>
                                    <?php if ($isLate): ?><span class="badge rounded-pill text-bg-warning">Tardanza</span>
                                    <?php elseif (!empty($row['hora_entrada'])): ?><span class="badge rounded-pill text-bg-success">Presente</span>
                                    <?php else: ?><span class="badge rounded-pill text-bg-secondary"><?= e(ucfirst(strtolower($status))) ?></span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
