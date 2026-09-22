-- Workforce360 AI - Migración Fase 3.3 -> Fase 4
-- Ejecutar UNA sola vez sobre la base workforce360_ai existente.

USE workforce360_ai;

ALTER TABLE incidencias
    ADD COLUMN revisado_por INT UNSIGNED NULL AFTER registrado_por,
    ADD COLUMN revisado_en DATETIME NULL AFTER revisado_por,
    ADD COLUMN observacion_revision VARCHAR(255) NULL AFTER revisado_en,
    ADD INDEX idx_incidencia_estado (estado),
    ADD CONSTRAINT fk_incidencia_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario);

ALTER TABLE vacaciones
    ADD COLUMN id_incidencia BIGINT UNSIGNED NULL AFTER id_vacacion,
    ADD UNIQUE KEY uq_vacacion_incidencia (id_incidencia),
    ADD CONSTRAINT fk_vacacion_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia);

ALTER TABLE licencias
    ADD COLUMN id_incidencia BIGINT UNSIGNED NULL AFTER id_licencia,
    ADD UNIQUE KEY uq_licencia_incidencia (id_incidencia),
    ADD CONSTRAINT fk_licencia_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia);

ALTER TABLE descansos_medicos
    ADD COLUMN id_incidencia BIGINT UNSIGNED NULL AFTER id_descanso,
    ADD UNIQUE KEY uq_descanso_incidencia (id_incidencia),
    ADD CONSTRAINT fk_descanso_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia);

ALTER TABLE permisos
    ADD COLUMN id_incidencia BIGINT UNSIGNED NULL AFTER id_permiso_laboral,
    ADD UNIQUE KEY uq_permiso_incidencia (id_incidencia),
    ADD CONSTRAINT fk_permiso_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia);

-- RRHH y Supervisores pueden gestionar/revisar incidencias según su alcance.
INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos_sistema p ON p.codigo = 'incidencias.gestionar'
WHERE r.nombre IN ('RRHH', 'SUPERVISOR');

SELECT 'Workforce360 AI - Migración Fase 4 aplicada correctamente' AS resultado;
