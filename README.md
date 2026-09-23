# Workforce360 AI

Workforce360 AI es el proyecto que estoy desarrollando para gestionar la asistencia, jornada laboral e incidencias de los colaboradores. Lo estoy trabajando por fases para poder probar cada módulo antes de continuar con el siguiente.

## Estado actual

El proyecto incluye hasta el momento:

- Login, sesiones y roles.
- Gestión de áreas, horarios y colaboradores.
- Acceso individual para colaboradores.
- Registro de ingreso y salida.
- Detección de tardanzas y salidas anticipadas.
- Historial personal de asistencia.
- Vacaciones, licencias, permisos y descansos médicos.
- Aprobación y rechazo de solicitudes.
- Dashboard operativo.
- KPIs de asistencia, puntualidad, cumplimiento de jornada y absentismo.
- Gráficos con Chart.js.
- Centro de Reportes completo.
- Reportes de asistencia, tardanzas, salidas anticipadas, incidencias y horas trabajadas.
- Exportación a Excel, PDF e impresión con diseño DEVIOZ.

## Tecnologías

- PHP 8
- MySQL 8
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Chart.js
- XAMPP
- Arquitectura MVC

## Fase actual

Actualmente estoy trabajando en la **Fase 6 - Reportes y Auditoría**.

La parte de Reportes ya incluye los reportes principales, filtros y exportación a Excel, PDF e impresión. El siguiente avance dentro de esta misma fase será completar el módulo de Auditoría y trazabilidad.

## Ejecución local

La carpeta debe estar en:

`C:\xampp\htdocs\Workforce360AI`

Con Apache y MySQL encendidos, el sistema se abre desde:

`http://localhost/Workforce360AI/`

## Base de datos

Los scripts se encuentran en la carpeta `database`. Si ya vienes trabajando con la Fase 4 y la Fase 5, el Centro de Reportes no requiere una nueva migración de MySQL.

## Credenciales de prueba

Administrador:

- Usuario: `admin`
- Contraseña: `Admin123*`

Colaborador demo:

- Usuario: `colaborador`
- Contraseña: `Colab123*`

## Importante

El proyecto se mantiene en desarrollo. Cada avance se prueba antes de guardarlo como una nueva versión en Git y subirlo al repositorio.
