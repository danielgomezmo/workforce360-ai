-- Workforce360 AI - Base de datos Fase 2
-- Base v2: CRUD de áreas, horarios y colaboradores.

CREATE DATABASE IF NOT EXISTS workforce360_ai
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE workforce360_ai;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS auditoria;
DROP TABLE IF EXISTS pronosticos;
DROP TABLE IF EXISTS feriados;
DROP TABLE IF EXISTS ceses;
DROP TABLE IF EXISTS permisos;
DROP TABLE IF EXISTS descansos_medicos;
DROP TABLE IF EXISTS licencias;
DROP TABLE IF EXISTS vacaciones;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS tipos_incidencia;
DROP TABLE IF EXISTS marcaciones;
DROP TABLE IF EXISTS asignacion_horarios;
DROP TABLE IF EXISTS usuario_rol;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS colaboradores;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS areas;
DROP TABLE IF EXISTS rol_permiso;
DROP TABLE IF EXISTS permisos_sistema;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id_rol INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(180) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permisos_sistema (
    id_permiso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(180) NULL
) ENGINE=InnoDB;

CREATE TABLE rol_permiso (
    id_rol INT UNSIGNED NOT NULL,
    id_permiso INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (id_permiso) REFERENCES permisos_sistema(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE areas (
    id_area INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE equipos (
    id_equipo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_area INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_equipo_area_nombre (id_area, nombre),
    CONSTRAINT fk_equipo_area FOREIGN KEY (id_area) REFERENCES areas(id_area)
) ENGINE=InnoDB;

CREATE TABLE horarios (
    id_horario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    minutos_tolerancia INT UNSIGNED NOT NULL DEFAULT 5,
    horas_descanso DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    cruza_medianoche TINYINT(1) NOT NULL DEFAULT 0,
    dias_semana JSON NULL COMMENT 'Ejemplo: [1,2,3,4,5] para lunes-viernes',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE colaboradores (
    id_colaborador INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_trabajador VARCHAR(30) NOT NULL UNIQUE,
    tipo_documento VARCHAR(20) NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(30) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    id_area INT UNSIGNED NULL,
    id_equipo INT UNSIGNED NULL,
    id_supervisor INT UNSIGNED NULL,
    cargo VARCHAR(120) NULL,
    sede VARCHAR(120) NULL,
    fecha_ingreso DATE NOT NULL,
    fecha_cese DATE NULL,
    jornada_horas DECIMAL(4,2) NOT NULL DEFAULT 8.00,
    modalidad ENUM('PRESENCIAL','REMOTO','HIBRIDO') NOT NULL DEFAULT 'PRESENCIAL',
    estado ENUM('REGISTRADO','HABILITADO','ACTIVO','LICENCIA','VACACIONES','SUSPENDIDO','CESADO') NOT NULL DEFAULT 'REGISTRADO',
    email_corporativo VARCHAR(150) NULL,
    telefono VARCHAR(30) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_colaborador_area FOREIGN KEY (id_area) REFERENCES areas(id_area),
    CONSTRAINT fk_colaborador_equipo FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo),
    CONSTRAINT fk_colaborador_supervisor FOREIGN KEY (id_supervisor) REFERENCES colaboradores(id_colaborador)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador)
) ENGINE=InnoDB;

CREATE TABLE usuario_rol (
    id_usuario INT UNSIGNED NOT NULL,
    id_rol INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_usuario, id_rol),
    CONSTRAINT fk_usuario_rol_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_rol_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE asignacion_horarios (
    id_asignacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    id_horario INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    es_temporal TINYINT(1) NOT NULL DEFAULT 0,
    motivo VARCHAR(200) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asignacion_colaborador_fecha (id_colaborador, fecha_inicio, fecha_fin),
    CONSTRAINT fk_asignacion_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_asignacion_horario FOREIGN KEY (id_horario) REFERENCES horarios(id_horario)
) ENGINE=InnoDB;

CREATE TABLE marcaciones (
    id_marcacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada DATETIME NULL,
    hora_salida DATETIME NULL,
    ip_entrada VARCHAR(45) NULL,
    ip_salida VARCHAR(45) NULL,
    minutos_tardanza INT UNSIGNED NOT NULL DEFAULT 0,
    minutos_salida_anticipada INT UNSIGNED NOT NULL DEFAULT 0,
    minutos_trabajados INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('PENDIENTE','PUNTUAL','TARDANZA','PARCIAL','COMPLETA','FALTA_POR_VALIDAR') NOT NULL DEFAULT 'PENDIENTE',
    origen VARCHAR(30) NOT NULL DEFAULT 'WEB',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_marcacion_colaborador_fecha (id_colaborador, fecha),
    INDEX idx_marcacion_fecha (fecha),
    CONSTRAINT fk_marcacion_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador)
) ENGINE=InnoDB;

CREATE TABLE tipos_incidencia (
    id_tipo_incidencia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    es_ausencia TINYINT(1) NOT NULL DEFAULT 0,
    es_justificada TINYINT(1) NOT NULL DEFAULT 0,
    requiere_aprobacion TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE incidencias (
    id_incidencia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    id_tipo_incidencia INT UNSIGNED NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    motivo VARCHAR(255) NULL,
    comentario TEXT NULL,
    documento_path VARCHAR(255) NULL,
    estado ENUM('REGISTRADA','PENDIENTE','APROBADA','RECHAZADA','ANULADA') NOT NULL DEFAULT 'REGISTRADA',
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidencia_colaborador_fecha (id_colaborador, fecha_inicio),
    CONSTRAINT fk_incidencia_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_incidencia_tipo FOREIGN KEY (id_tipo_incidencia) REFERENCES tipos_incidencia(id_tipo_incidencia),
    CONSTRAINT fk_incidencia_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE vacaciones (
    id_vacacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('PENDIENTE','APROBADA','RECHAZADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
    observacion VARCHAR(255) NULL,
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vacacion_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_vacacion_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE licencias (
    id_licencia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    tipo ENUM('CON_GOCE','SIN_GOCE') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    estado ENUM('PENDIENTE','APROBADA','RECHAZADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_licencia_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_licencia_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE descansos_medicos (
    id_descanso BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    documento_path VARCHAR(255) NULL,
    observacion VARCHAR(255) NULL,
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_descanso_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_descanso_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE permisos (
    id_permiso_laboral BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo VARCHAR(255) NULL,
    con_goce TINYINT(1) NOT NULL DEFAULT 1,
    estado ENUM('PENDIENTE','APROBADO','RECHAZADO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_permiso_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_permiso_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE ceses (
    id_cese BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_colaborador INT UNSIGNED NOT NULL,
    fecha_cese DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cese_colaborador (id_colaborador),
    CONSTRAINT fk_cese_colaborador FOREIGN KEY (id_colaborador) REFERENCES colaboradores(id_colaborador),
    CONSTRAINT fk_cese_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE feriados (
    id_feriado INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    ambito VARCHAR(80) NOT NULL DEFAULT 'NACIONAL',
    laborable TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE pronosticos (
    id_pronostico BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ASISTENCIA_DIARIA','DISPONIBILIDAD_SEMANAL','ABSENTISMO_MENSUAL','HORAS_DISPONIBLES','ALERTA_COBERTURA') NOT NULL,
    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_objetivo_inicio DATE NOT NULL,
    fecha_objetivo_fin DATE NULL,
    id_area INT UNSIGNED NULL,
    id_equipo INT UNSIGNED NULL,
    valor_estimado DECIMAL(12,4) NULL,
    unidad VARCHAR(30) NULL,
    confianza DECIMAL(6,3) NULL,
    detalle JSON NULL,
    version_modelo VARCHAR(80) NULL,
    CONSTRAINT fk_pronostico_area FOREIGN KEY (id_area) REFERENCES areas(id_area),
    CONSTRAINT fk_pronostico_equipo FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo)
) ENGINE=InnoDB;

CREATE TABLE auditoria (
    id_auditoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL,
    accion VARCHAR(60) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    entidad_id BIGINT UNSIGNED NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    motivo VARCHAR(255) NULL,
    ip_origen VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_entidad (entidad, entidad_id),
    INDEX idx_auditoria_fecha (fecha_evento),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- Roles de acceso definidos por el proyecto.
INSERT INTO roles (nombre, descripcion) VALUES
('ADMINISTRADOR', 'Acceso completo a la plataforma'),
('RRHH', 'Gestión de colaboradores, jornadas e incidencias'),
('SUPERVISOR', 'Seguimiento del equipo y validación de incidencias'),
('COLABORADOR', 'Marcación y consulta de información propia'),
('GERENCIA', 'Dashboards e indicadores consolidados');

INSERT INTO permisos_sistema (codigo, nombre) VALUES
('dashboard.ver', 'Ver dashboard'),
('areas.gestionar', 'Gestionar áreas'),
('colaboradores.gestionar', 'Gestionar colaboradores'),
('horarios.gestionar', 'Gestionar horarios'),
('marcaciones.registrar', 'Registrar marcaciones'),
('incidencias.gestionar', 'Gestionar incidencias'),
('reportes.ver', 'Ver reportes'),
('auditoria.ver', 'Ver auditoría'),
('pronosticos.ver', 'Ver pronósticos');

-- Administrador con todos los permisos.
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r CROSS JOIN permisos_sistema p
WHERE r.nombre = 'ADMINISTRADOR';

-- Recursos Humanos puede administrar los catálogos incorporados en Fase 2.
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos_sistema p ON p.codigo IN ('dashboard.ver','areas.gestionar','colaboradores.gestionar','horarios.gestionar')
WHERE r.nombre = 'RRHH';

-- Catálogo de incidencias previsto por el documento.
INSERT INTO tipos_incidencia (codigo, nombre, es_ausencia, es_justificada, requiere_aprobacion) VALUES
('ASISTENCIA_COMPLETA', 'Asistencia completa', 0, 1, 0),
('ASISTENCIA_PARCIAL', 'Asistencia parcial', 0, 1, 0),
('TARDANZA', 'Tardanza', 0, 0, 0),
('FALTA_INJUSTIFICADA', 'Falta injustificada', 1, 0, 0),
('FALTA_JUSTIFICADA', 'Falta justificada', 1, 1, 1),
('SALIDA_ANTICIPADA', 'Salida anticipada', 0, 0, 0),
('VACACIONES', 'Vacaciones', 1, 1, 1),
('LICENCIA_CON_GOCE', 'Licencia con goce', 1, 1, 1),
('LICENCIA_SIN_GOCE', 'Licencia sin goce', 1, 1, 1),
('DESCANSO_MEDICO', 'Descanso médico', 1, 1, 1),
('PERMISO', 'Permiso', 1, 1, 1),
('DESCANSO_PROGRAMADO', 'Descanso programado', 1, 1, 0),
('COMPENSACION', 'Compensación', 1, 1, 1),
('TRABAJO_REMOTO', 'Trabajo remoto', 0, 1, 0),
('SUSPENSION', 'Suspensión', 1, 1, 1),
('CESE', 'Cese', 1, 1, 0);

-- Datos de demostración mínimos para comprobar el dashboard.
INSERT INTO areas (nombre, descripcion) VALUES
('Tecnología', 'Área de ejemplo para la gestión de colaboradores'),
('Recursos Humanos', 'Área de ejemplo para la gestión de colaboradores');

INSERT INTO equipos (id_area, nombre, descripcion)
SELECT id_area, 'Desarrollo', 'Equipo de ejemplo' FROM areas WHERE nombre = 'Tecnología';
INSERT INTO equipos (id_area, nombre, descripcion)
SELECT id_area, 'Gestión de Personas', 'Equipo de ejemplo' FROM areas WHERE nombre = 'Recursos Humanos';

INSERT INTO horarios (nombre, hora_inicio, hora_fin, minutos_tolerancia, horas_descanso, dias_semana) VALUES
('Administrativo', '08:00:00', '18:00:00', 5, 1.00, JSON_ARRAY(1,2,3,4,5)),
('Turno mañana', '06:00:00', '14:00:00', 5, 0.00, JSON_ARRAY(1,2,3,4,5,6)),
('Turno tarde', '14:00:00', '22:00:00', 5, 0.00, JSON_ARRAY(1,2,3,4,5,6)),
('Turno noche', '22:00:00', '06:00:00', 5, 0.00, JSON_ARRAY(1,2,3,4,5,6));

-- Hash generado con password_hash('Admin123*', PASSWORD_DEFAULT).
INSERT INTO usuarios (username, email, password_hash, activo)
VALUES ('admin', 'admin@workforce360.local', '$2y$12$0LtgW8nFvzny4Wu9uDCdRuDK7hl.9AKXOSJ5680oReh8iBl4SnxvW', 1);

INSERT INTO usuario_rol (id_usuario, id_rol)
SELECT u.id_usuario, r.id_rol
FROM usuarios u, roles r
WHERE u.username = 'admin' AND r.nombre = 'ADMINISTRADOR';

SELECT 'Workforce360 AI - Fase 2 instalada correctamente' AS resultado;
