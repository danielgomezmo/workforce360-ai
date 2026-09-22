# Workforce360 AI

Workforce360 AI es un proyecto que estoy desarrollando como parte de mis prácticas.

La idea del sistema es llevar un mejor control de la asistencia de los colaboradores, permitiendo registrar ingresos, salidas, horarios, tardanzas e incidencias laborales.

El proyecto se está desarrollando por fases para ir implementando y probando cada módulo antes de continuar con el siguiente.

## Tecnologías utilizadas

- PHP 8
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap 5
- XAMPP

## Funcionalidades desarrolladas hasta el momento

Actualmente el sistema cuenta con:

- Inicio y cierre de sesión.
- Roles de usuario.
- Gestión de colaboradores.
- Gestión de áreas.
- Gestión de horarios.
- Registro de ingreso y salida.
- Detección de tardanzas.
- Detección de salidas anticipadas.
- Historial de asistencia del colaborador.
- Solicitudes de incidencias.
- Vacaciones.
- Permisos.
- Licencias.
- Descansos médicos.
- Aprobación y rechazo de solicitudes.
- Auditoría básica de las operaciones.

## Roles del sistema

El sistema contempla los siguientes roles:

- Administrador
- Recursos Humanos
- Supervisor
- Colaborador
- Gerencia

Cada usuario tiene acceso a diferentes opciones dependiendo de su rol.

## Estado actual

El proyecto se encuentra desarrollado hasta la Fase 4.

En las siguientes fases se implementarán dashboards, indicadores, reportes y posteriormente el módulo de Inteligencia Artificial.

## Ejecución del proyecto

El proyecto se ejecuta de manera local utilizando XAMPP.

La carpeta del proyecto debe colocarse dentro de:

C:\xampp\htdocs\Workforce360AI

Luego se debe iniciar Apache y MySQL desde XAMPP.

La aplicación se puede abrir desde:

http://localhost/Workforce360AI/

## Base de datos

La base de datos utilizada es MySQL.

Dentro de la carpeta `database` se encuentran los scripts necesarios para la instalación y las migraciones realizadas durante las diferentes fases del proyecto.