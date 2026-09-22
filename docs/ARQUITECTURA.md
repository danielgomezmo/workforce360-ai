# Arquitectura · Workforce360 AI

## Aplicación web

- Frontend: HTML5, CSS3, Bootstrap 5, JavaScript y Chart.js.
- Backend: PHP 8.x.
- Patrón: MVC.
- Base de datos: MySQL.
- Acceso a datos: PDO con consultas preparadas.

## Componentes incorporados hasta Fase 3

`Controllers` recibe las acciones HTTP y aplica autorización/CSRF.

`Models` concentra las consultas SQL para catálogos, colaboradores y marcaciones.

`Services/AttendanceRuleEngine.php` concentra las reglas de jornada para no mezclar cálculos de negocio con controladores o vistas.

`Views` contiene las pantallas administrativas y la pantalla personal de marcación.

`Audit` registra acciones relevantes con usuario, IP, user-agent y cambios principales.

## Flujo de marcación

Usuario autenticado → usuario vinculado a colaborador → validación de estado laboral → horario vigente → validación del día → hora del servidor → motor de reglas → marcación → incidencia automática si corresponde → auditoría → interfaz.

## Evolución prevista

En la fase predictiva, PHP seguirá siendo el backend principal y consumirá mediante REST un servicio independiente en Python/FastAPI encargado de los modelos de Workforce AI.
