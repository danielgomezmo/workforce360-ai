-- Workforce360 AI
-- Migración opcional para una instalación existente de Base v1.
-- No elimina datos existentes.
USE workforce360_ai;

INSERT IGNORE INTO permisos_sistema (codigo, nombre, descripcion)
VALUES ('areas.gestionar', 'Gestionar áreas', 'Crear, editar, activar/desactivar y eliminar áreas sin relaciones');

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos_sistema p ON p.codigo = 'areas.gestionar'
WHERE r.nombre = 'ADMINISTRADOR';

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos_sistema p ON p.codigo IN ('dashboard.ver','areas.gestionar','colaboradores.gestionar','horarios.gestionar')
WHERE r.nombre = 'RRHH';

INSERT IGNORE INTO equipos (id_area, nombre, descripcion)
SELECT id_area, 'Desarrollo', 'Equipo de ejemplo para Fase 2'
FROM areas WHERE nombre = 'Tecnología';

INSERT IGNORE INTO equipos (id_area, nombre, descripcion)
SELECT id_area, 'Gestión de Personas', 'Equipo de ejemplo para Fase 2'
FROM areas WHERE nombre = 'Recursos Humanos';

SELECT 'Migración Fase 2 aplicada correctamente' AS resultado;
