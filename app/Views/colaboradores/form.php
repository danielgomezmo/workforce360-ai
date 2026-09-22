<?php
use App\Core\Csrf;
$isEdit = is_array($colaborador);
$title = ($isEdit ? 'Editar colaborador' : 'Nuevo colaborador') . ' · Workforce360 AI';
$v = static fn (string $key, mixed $default = '') => $_SESSION['_old'][$key] ?? $default;
$selectedArea = (int) $v('id_area', $colaborador['id_area'] ?? 0);
$selectedEquipo = (int) $v('id_equipo', $colaborador['id_equipo'] ?? 0);
$selectedSupervisor = (int) $v('id_supervisor', $colaborador['id_supervisor'] ?? 0);
$selectedHorario = (int) $v('id_horario', $colaborador['id_horario'] ?? 0);
$selectedModalidad = (string) $v('modalidad', $colaborador['modalidad'] ?? 'PRESENCIAL');
$selectedEstado = (string) $v('estado', $colaborador['estado'] ?? 'REGISTRADO');
$hasAccess = $isEdit && !empty($colaborador['acceso_id_usuario']);
$accessUsername = (string) $v('acceso_username', $colaborador['acceso_username'] ?? '');
$accessActive = array_key_exists('acceso_activo', $_SESSION['_old'] ?? [])
    ? isset($_SESSION['_old']['acceso_activo'])
    : (!$isEdit || (bool)($colaborador['acceso_activo'] ?? false));
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="form-page form-page-wide mx-auto">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">Colaboradores</p>
            <h1 class="h2 fw-bold mb-1"><?= $isEdit ? 'Editar colaborador' : 'Registrar colaborador' ?></h1>
            <p class="text-secondary mb-0">Completa la información laboral base que luego utilizarán asistencia, incidencias y reportes.</p>
        </div>
        <a class="btn btn-light" href="<?= e(route_url('colaboradores')) ?>">Volver</a>
    </div>

    <form method="post" action="<?= e(route_url($isEdit ? 'colaboradores/actualizar' : 'colaboradores/guardar')) ?>">
        <?= Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id_colaborador" value="<?= e($colaborador['id_colaborador']) ?>"><?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="h5 fw-bold mb-4">Identificación</h2>
                <div class="row g-4">
                    <div class="col-md-4"><label class="form-label fw-semibold" for="codigo_trabajador">Código de trabajador *</label><input class="form-control" id="codigo_trabajador" name="codigo_trabajador" maxlength="30" required value="<?= e($v('codigo_trabajador', $colaborador['codigo_trabajador'] ?? '')) ?>" placeholder="DEV-001"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold" for="tipo_documento">Tipo de documento *</label><select class="form-select" id="tipo_documento" name="tipo_documento"><?php $doc=$v('tipo_documento',$colaborador['tipo_documento']??'DNI'); foreach (['DNI','CE','PASAPORTE','OTRO'] as $d): ?><option value="<?= e($d) ?>" <?= $doc===$d?'selected':'' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-5"><label class="form-label fw-semibold" for="numero_documento">Número de documento *</label><input class="form-control" id="numero_documento" name="numero_documento" maxlength="30" required value="<?= e($v('numero_documento', $colaborador['numero_documento'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="nombres">Nombres *</label><input class="form-control" id="nombres" name="nombres" maxlength="100" required value="<?= e($v('nombres', $colaborador['nombres'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="apellidos">Apellidos *</label><input class="form-control" id="apellidos" name="apellidos" maxlength="120" required value="<?= e($v('apellidos', $colaborador['apellidos'] ?? '')) ?>"></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="h5 fw-bold mb-4">Información laboral</h2>
                <div class="row g-4">
                    <div class="col-md-6"><label class="form-label fw-semibold" for="id_area">Área *</label><select class="form-select" id="id_area" name="id_area" required><option value="">Seleccionar</option><?php foreach ($areas as $a): ?><option value="<?= e($a['id_area']) ?>" <?= $selectedArea===(int)$a['id_area']?'selected':'' ?>><?= e($a['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="id_equipo">Equipo</label><select class="form-select" id="id_equipo" name="id_equipo"><option value="">Sin equipo</option><?php foreach ($equipos as $equipe): ?><option data-area="<?= e($equipe['id_area']) ?>" value="<?= e($equipe['id_equipo']) ?>" <?= $selectedEquipo===(int)$equipe['id_equipo']?'selected':'' ?>><?= e($equipe['area_nombre'] . ' · ' . $equipe['nombre']) ?></option><?php endforeach; ?></select><div class="form-text">En esta fase el equipo es opcional; el CRUD propio de equipos puede añadirse después.</div></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="cargo">Cargo *</label><input class="form-control" id="cargo" name="cargo" maxlength="120" required value="<?= e($v('cargo', $colaborador['cargo'] ?? '')) ?>" placeholder="Ej. Desarrollador junior"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="sede">Sede *</label><input class="form-control" id="sede" name="sede" maxlength="120" required value="<?= e($v('sede', $colaborador['sede'] ?? '')) ?>" placeholder="Ej. Lima"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="id_supervisor">Supervisor</label><select class="form-select" id="id_supervisor" name="id_supervisor"><option value="">Sin supervisor</option><?php foreach ($supervisores as $s): ?><option value="<?= e($s['id_colaborador']) ?>" <?= $selectedSupervisor===(int)$s['id_colaborador']?'selected':'' ?>><?= e($s['apellidos'] . ', ' . $s['nombres'] . ' · ' . $s['codigo_trabajador']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label fw-semibold" for="id_horario">Horario base *</label><select class="form-select" id="id_horario" name="id_horario" required><option value="">Seleccionar</option><?php foreach ($horarios as $h): ?><option value="<?= e($h['id_horario']) ?>" <?= $selectedHorario===(int)$h['id_horario']?'selected':'' ?>><?= e($h['nombre']) ?> · <?= e(substr($h['hora_inicio'],0,5)) ?>–<?= e(substr($h['hora_fin'],0,5)) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label fw-semibold" for="fecha_ingreso">Fecha de ingreso *</label><input class="form-control" type="date" id="fecha_ingreso" name="fecha_ingreso" required value="<?= e($v('fecha_ingreso', $colaborador['fecha_ingreso'] ?? date('Y-m-d'))) ?>"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold" for="jornada_horas">Jornada diaria (h) *</label><input class="form-control" type="number" min="0.25" max="24" step="0.25" id="jornada_horas" name="jornada_horas" required value="<?= e($v('jornada_horas', $colaborador['jornada_horas'] ?? 8)) ?>"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold" for="modalidad">Modalidad *</label><select class="form-select" id="modalidad" name="modalidad" required><?php foreach ($modalidades as $m): ?><option value="<?= e($m) ?>" <?= $selectedModalidad===$m?'selected':'' ?>><?= e(ucfirst(strtolower($m))) ?></option><?php endforeach; ?></select></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="h5 fw-bold mb-4">Estado y contacto</h2>
                <div class="row g-4">
                    <div class="col-md-4"><label class="form-label fw-semibold" for="estado">Estado *</label><select class="form-select" id="estado" name="estado" required><?php foreach ($estados as $st): ?><option value="<?= e($st) ?>" <?= $selectedEstado===$st?'selected':'' ?>><?= e(ucwords(strtolower(str_replace('_',' ', $st)))) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label fw-semibold" for="fecha_cese">Fecha de cese</label><input class="form-control" type="date" id="fecha_cese" name="fecha_cese" value="<?= e($v('fecha_cese', $colaborador['fecha_cese'] ?? '')) ?>"><div class="form-text">Obligatoria cuando el estado sea Cesado.</div></div>
                    <div class="col-md-4"><label class="form-label fw-semibold" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" maxlength="30" value="<?= e($v('telefono', $colaborador['telefono'] ?? '')) ?>"></div>
                    <div class="col-md-8"><label class="form-label fw-semibold" for="email_corporativo">Correo corporativo</label><input class="form-control" type="email" id="email_corporativo" name="email_corporativo" maxlength="150" value="<?= e($v('email_corporativo', $colaborador['email_corporativo'] ?? '')) ?>" placeholder="nombre@empresa.com"></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Acceso para marcar asistencia</h2>
                        <p class="text-secondary small mb-0">Estas credenciales vinculan al colaborador con “Mi asistencia”.</p>
                    </div>
                    <?php if ($hasAccess): ?><span class="badge text-bg-success">Acceso creado</span><?php else: ?><span class="badge text-bg-warning">Acceso pendiente</span><?php endif; ?>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="acceso_username">Usuario de acceso *</label>
                        <input class="form-control" id="acceso_username" name="acceso_username" maxlength="60" required value="<?= e($accessUsername) ?>" placeholder="ej. pedro.ramirez" autocomplete="off">
                        <div class="form-text">Se usará para iniciar sesión en Workforce360 AI.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="acceso_password">Contraseña <?= $hasAccess ? '' : '*' ?></label>
                        <input class="form-control" type="password" id="acceso_password" name="acceso_password" minlength="8" <?= $hasAccess ? '' : 'required' ?> autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                        <div class="form-text"><?= $hasAccess ? 'Déjala vacía para conservar la contraseña actual.' : 'Es necesaria para que el colaborador pueda entrar y marcar.' ?></div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="acceso_activo" name="acceso_activo" value="1" <?= $accessActive ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="acceso_activo">Permitir inicio de sesión y marcación</label>
                        </div>
                        <div class="form-text">El estado laboral ACTIVO y un horario vigente siguen siendo necesarios para iniciar una jornada.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4"><a class="btn btn-light" href="<?= e(route_url('colaboradores')) ?>">Cancelar</a><button class="btn btn-primary px-4" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar colaborador' ?></button></div>
    </form>
</div>
<?php unset($_SESSION['_old']); ?>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
