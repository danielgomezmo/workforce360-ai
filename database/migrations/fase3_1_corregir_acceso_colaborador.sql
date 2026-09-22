-- Workforce360 AI · Corrección Fase 3.1
-- Objetivo: reparar/garantizar el acceso del colaborador demo sin borrar datos.
-- Ejecutar UNA SOLA VEZ sobre una instalación de Fase 3.
USE workforce360_ai;

-- 1) Asegurar catálogos mínimos usados por la cuenta demo.
INSERT IGNORE INTO areas (nombre, descripcion, activo)
VALUES ('Tecnología', 'Área de ejemplo para la gestión de colaboradores', 1);

INSERT INTO horarios (nombre, hora_inicio, hora_fin, minutos_tolerancia, horas_descanso, cruza_medianoche, dias_semana, activo)
SELECT 'Administrativo', '08:00:00', '18:00:00', 5, 1.00, 0, JSON_ARRAY(1,2,3,4,5), 1
WHERE NOT EXISTS (
    SELECT 1 FROM horarios WHERE nombre = 'Administrativo'
);

-- 2) Asegurar que el colaborador demo exista y esté habilitado para pruebas.
INSERT INTO colaboradores
(codigo_trabajador, tipo_documento, numero_documento, nombres, apellidos, id_area, cargo, sede,
 fecha_ingreso, jornada_horas, modalidad, estado, email_corporativo)
SELECT 'WF-DEMO-001', 'DNI', '70000001', 'Colaborador', 'Demo', a.id_area,
       'Asistente de Operaciones', 'Lima', '2026-01-01', 8.00, 'PRESENCIAL', 'ACTIVO',
       'colaborador@workforce360.local'
FROM areas a
WHERE a.nombre = 'Tecnología'
  AND NOT EXISTS (SELECT 1 FROM colaboradores WHERE codigo_trabajador = 'WF-DEMO-001')
LIMIT 1;

UPDATE colaboradores
SET estado = 'ACTIVO', fecha_cese = NULL
WHERE codigo_trabajador = 'WF-DEMO-001';

-- 3) Asegurar horario base vigente.
INSERT INTO asignacion_horarios (id_colaborador, id_horario, fecha_inicio, es_temporal, motivo)
SELECT c.id_colaborador, h.id_horario, c.fecha_ingreso, 0, 'Horario base demo reparado en Fase 3.1'
FROM colaboradores c
JOIN horarios h ON h.nombre = 'Administrativo'
WHERE c.codigo_trabajador = 'WF-DEMO-001'
  AND NOT EXISTS (
      SELECT 1 FROM asignacion_horarios ah
      WHERE ah.id_colaborador = c.id_colaborador
        AND ah.fecha_inicio <= CURDATE()
        AND (ah.fecha_fin IS NULL OR ah.fecha_fin >= CURDATE())
  )
LIMIT 1;

-- 4) Si ya existe el usuario "colaborador" pero estaba sin vínculo, enlazarlo al demo.
UPDATE usuarios u
JOIN colaboradores c ON c.codigo_trabajador = 'WF-DEMO-001'
SET u.id_colaborador = c.id_colaborador,
    u.password_hash = '$2y$12$Aa.3Ws45c8tXrINXG3f9W.ciYa2yjio3KJ34I.Srg.o4i9tufGE7G',
    u.activo = 1
WHERE u.username = 'colaborador'
  AND (u.id_colaborador IS NULL OR u.id_colaborador = c.id_colaborador);

-- 5) Si todavía no existe cuenta vinculada, crearla.
INSERT INTO usuarios (id_colaborador, username, email, password_hash, activo)
SELECT c.id_colaborador, 'colaborador', NULL,
       '$2y$12$Aa.3Ws45c8tXrINXG3f9W.ciYa2yjio3KJ34I.Srg.o4i9tufGE7G', 1
FROM colaboradores c
WHERE c.codigo_trabajador = 'WF-DEMO-001'
  AND NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.id_colaborador = c.id_colaborador)
  AND NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.username = 'colaborador')
LIMIT 1;

-- 6) Garantizar el rol COLABORADOR para la cuenta vinculada.
INSERT IGNORE INTO usuario_rol (id_usuario, id_rol)
SELECT u.id_usuario, r.id_rol
FROM usuarios u
JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
JOIN roles r ON r.nombre = 'COLABORADOR'
WHERE c.codigo_trabajador = 'WF-DEMO-001';

-- 7) Mantener activo el permiso de marcación del rol colaborador.
INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
JOIN permisos_sistema p ON p.codigo IN ('dashboard.ver', 'marcaciones.registrar')
WHERE r.nombre = 'COLABORADOR';

-- Diagnóstico final: esta consulta debe devolver una fila con usuario colaborador,
-- id_colaborador no nulo, estado ACTIVO y horario Administrativo.
SELECT
    u.username,
    u.activo AS usuario_activo,
    c.id_colaborador,
    c.codigo_trabajador,
    c.estado,
    h.nombre AS horario,
    ah.fecha_inicio,
    ah.fecha_fin
FROM usuarios u
JOIN colaboradores c ON c.id_colaborador = u.id_colaborador
LEFT JOIN asignacion_horarios ah ON ah.id_asignacion = (
    SELECT ah2.id_asignacion
    FROM asignacion_horarios ah2
    WHERE ah2.id_colaborador = c.id_colaborador
    ORDER BY ah2.es_temporal DESC, ah2.fecha_inicio DESC, ah2.id_asignacion DESC
    LIMIT 1
)
LEFT JOIN horarios h ON h.id_horario = ah.id_horario
WHERE u.username = 'colaborador';
