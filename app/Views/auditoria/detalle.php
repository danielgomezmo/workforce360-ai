<?php
use App\Core\Auth;

Auth::requireAnyRole(['ADMINISTRADOR']);
require BASE_PATH . '/app/Views/layouts/header.php';

$flatten = static function (array $data, string $prefix = '') use (&$flatten): array {
    $result = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $result += $flatten($value, $path);
        } else {
            $result[$path] = $value;
        }
    }
    return $result;
};

$formatValue = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    if (is_bool($value)) {
        return $value ? 'Sí' : 'No';
    }
    if (is_float($value)) {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
    return (string) $value;
};

$fieldLabel = static function (string $field): string {
    $parts = explode('.', $field);
    $labels = array_map(static fn(string $part): string => ucwords(str_replace('_', ' ', $part)), $parts);
    return implode(' → ', $labels);
};

$beforeFlat = $flatten($before);
$afterFlat = $flatten($after);
$fields = array_values(array_unique(array_merge(array_keys($beforeFlat), array_keys($afterFlat))));
sort($fields);

$actionLabel = ucwords(strtolower(str_replace('_', ' ', (string) $event['accion'])));
$entityLabel = ucwords(str_replace('_', ' ', (string) $event['entidad']));
?>

<div class="page-hero mb-4">
    <div class="page-hero-main">
        <div class="page-hero-icon"><i class="fa-solid fa-file-shield"></i></div>
        <div>
            <h1>Detalle de Auditoría #<?= e($event['id_auditoria']) ?></h1>
            <p>Consulta la trazabilidad del evento, sus metadatos y los valores registrados antes y después.</p>
        </div>
    </div>
    <a href="<?= e(route_url('auditoria')) ?>" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Volver
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 px-4">
                <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-circle-info me-2"></i>Información del evento</h2>
            </div>
            <div class="card-body">
                <div class="row g-3 audit-detail-grid">
                    <div class="col-md-6">
                        <span>Fecha y hora</span>
                        <strong><?= e(date('d/m/Y H:i:s', strtotime($event['fecha_evento']))) ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Usuario</span>
                        <strong><?= e($event['usuario_nombre'] ?? 'Sistema') ?></strong>
                        <small><?= !empty($event['username']) ? '@' . e($event['username']) : 'Evento sin usuario autenticado' ?></small>
                    </div>
                    <div class="col-md-6">
                        <span>Rol(es)</span>
                        <strong><?= e($event['roles'] ?? 'SIN ROL') ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Acción</span>
                        <strong><?= e($actionLabel) ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Módulo / entidad</span>
                        <strong><?= e($entityLabel) ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Registro afectado</span>
                        <strong><?= $event['entidad_id'] !== null ? '#' . e($event['entidad_id']) : '—' ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Dirección IP</span>
                        <strong><code><?= e($event['ip_origen'] ?: '—') ?></code></strong>
                    </div>
                    <div class="col-md-6">
                        <span>Correo de cuenta</span>
                        <strong><?= e($event['usuario_email'] ?: '—') ?></strong>
                    </div>
                </div>

                <?php if (!empty($event['motivo'])): ?>
                    <div class="audit-reason mt-4">
                        <span>Motivo / observación</span>
                        <p class="mb-0"><?= nl2br(e($event['motivo'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 px-4">
                <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-desktop me-2"></i>Origen técnico</h2>
            </div>
            <div class="card-body">
                <div class="audit-origin-item">
                    <span>IP registrada</span>
                    <code><?= e($event['ip_origen'] ?: 'No disponible') ?></code>
                </div>
                <div class="audit-origin-item mt-3">
                    <span>Navegador / User Agent</span>
                    <p class="mb-0 small text-break"><?= e($event['user_agent'] ?: 'No disponible') ?></p>
                </div>
                <div class="audit-integrity-note mt-4">
                    <i class="fa-solid fa-lock me-2"></i>
                    Este registro se presenta en modo de solo lectura para conservar la trazabilidad histórica.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm audit-comparison-card">
    <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-code-compare me-2"></i>Comparación de valores</h2>
            <small class="text-muted">Los campos modificados se destacan para facilitar la revisión.</small>
        </div>
        <span class="badge text-bg-light border"><?= e(count($fields)) ?> campo(s)</span>
    </div>

    <?php if (!$fields): ?>
        <div class="card-body text-center py-5 text-muted">
            <i class="fa-solid fa-circle-info d-block fs-3 mb-2 opacity-50"></i>
            Este evento no registró valores anteriores o nuevos adicionales.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table app-table audit-comparison-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 26%">Campo</th>
                        <th style="width: 37%">Antes</th>
                        <th style="width: 37%">Después</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $beforeValue = $beforeFlat[$field] ?? null;
                        $afterValue = $afterFlat[$field] ?? null;
                        $changed = $beforeValue !== $afterValue;
                        ?>
                        <tr class="<?= $changed ? 'audit-changed-row' : '' ?>">
                            <td><strong><?= e($fieldLabel($field)) ?></strong></td>
                            <td><div class="audit-value-box audit-value-before"><?= e($formatValue($beforeValue)) ?></div></td>
                            <td><div class="audit-value-box audit-value-after"><?= e($formatValue($afterValue)) ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
