-- Workforce360 AI
-- Migración de Fase 2 a Fase 3.
-- Ejecutar UNA SOLA VEZ sobre una base instalada con workforce360_v2.sql.
USE workforce360_ai;

ALTER TABLE marcaciones
    ADD COLUMN id_horario INT UNSIGNED NULL AFTER id_colaborador,
    ADD COLUMN entrada_programada DATETIME NULL AFTER hora_salida,
    ADD COLUMN salida_programada DATETIME NULL AFTER entrada_programada,
    ADD COLUMN tolerancia_aplicada INT UNSIGNED NOT NULL DEFAULT 0 AFTER salida_programada,
    ADD COLUMN horas_descanso_programadas DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER tolerancia_aplicada,
    ADD COLUMN resultado_entrada ENUM('PENDIENTE','PUNTUAL','TARDANZA') NOT NULL DEFAULT 'PENDIENTE' AFTER minutos_trabajados,
    ADD COLUMN resultado_salida ENUM('PENDIENTE','A_TIEMPO','ANTICIPADA') NOT NULL DEFAULT 'PENDIENTE' AFTER resultado_entrada,
    ADD INDEX idx_marcacion_horario (id_horario),
    ADD CONSTRAINT fk_marcacion_horario FOREIGN KEY (id_horario) REFERENCES horarios(id_horario);

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos_sistema p ON p.codigo IN ('dashboard.ver','marcaciones.registrar')
WHERE r.nombre IN ('SUPERVISOR','COLABORADOR');

-- Usuario colaborador de demostración. No altera colaboradores existentes.
INSERT IGNORE INTO colaboradores
(codigo_trabajador, tipo_documento, numero_documento, nombres, apellidos, id_area, id_equipo, cargo, sede, fecha_ingreso, jornada_horas, modalidad, estado, email_corporativo)
SELECT 'WF-DEMO-001', 'DNI', '70000001', 'Colaborador', 'Demo', a.id_area, e.id_equipo,
       'Asistente de Operaciones', 'Lima', '2026-01-01', 8.00, 'PRESENCIAL', 'ACTIVO', 'colaborador@workforce360.local'
FROM areas a
LEFT JOIN equipos e ON e.id_area = a.id_area AND e.nombre = 'Desarrollo'
WHERE a.nombre = 'Tecnología'
LIMIT 1;

INSERT INTO asignacion_horarios (id_colaborador, id_horario, fecha_inicio, es_temporal, motivo)
SELECT c.id_colaborador, h.id_horario, c.fecha_ingreso, 0, 'Horario base para demostración de Fase 3'
FROM colaboradores c, horarios h
WHERE c.codigo_trabajador = 'WF-DEMO-001' AND h.nombre = 'Administrativo'
  AND NOT EXISTS (
      SELECT 1 FROM asignacion_horarios ah
      WHERE ah.id_colaborador = c.id_colaborador AND ah.es_temporal = 0
  );

INSERT IGNORE INTO usuarios (id_colaborador, username, email, password_hash, activo)
SELECT c.id_colaborador, 'colaborador', 'colaborador@workforce360.local', '$2y$12$Aa.3Ws45c8tXrINXG3f9W.ciYa2yjio3KJ34I.Srg.o4i9tufGE7G', 1
FROM colaboradores c WHERE c.codigo_trabajador = 'WF-DEMO-001';

INSERT IGNORE INTO usuario_rol (id_usuario, id_rol)
SELECT u.id_usuario, r.id_rol FROM usuarios u, roles r
WHERE u.username = 'colaborador' AND r.nombre = 'COLABORADOR';

SELECT 'Migración Fase 3 aplicada correctamente' AS resultado;
