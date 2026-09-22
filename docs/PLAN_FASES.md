# Plan incremental · Workforce360 AI

## Fase 1 — Base del sistema ✅
- Arquitectura MVC.
- Conexión PDO/MySQL.
- Login, sesiones y roles.
- CSRF y auditoría inicial.
- Dashboard base.

## Fase 2 — Maestros organizacionales ✅
- CRUD de áreas.
- CRUD de horarios.
- CRUD de colaboradores.
- Equipos y supervisor.
- Asignación de horario base.
- Ciclo de vida y estado laboral.

## Fase 3 — Marcaciones y motor de reglas ✅ (revisión 3.1)
- Marcación de entrada y salida.
- Hora oficial del servidor.
- IP de marcación.
- Horario vigente y días laborables.
- Tolerancia configurable.
- Puntualidad y tardanza.
- Salida anticipada.
- Minutos trabajados.
- Turnos nocturnos.
- Bloqueo por cese/estado.
- Incidencias automáticas de tardanza y salida anticipada.
- Panel personal e interfaz de supervisión.
- Cuenta de acceso vinculada a cada colaborador desde el CRUD.
- Rol COLABORADOR asignado automáticamente.

## Fase 4 — Incidencias laborales ⏭️
- CRUD/flujo de incidencias.
- Vacaciones.
- Licencias con y sin goce.
- Permisos.
- Descansos médicos.
- Descansos programados.
- Justificación de faltas.
- Validación/aprobación por Supervisor/RRHH.
- Integración de incidencias con el estado diario de asistencia.

## Fase 5 — Inteligencia operativa
- Dashboard operativo.
- Dashboard gerencial.
- Índice de asistencia.
- Puntualidad y tardanza.
- Cumplimiento de jornada.
- Absentismo.
- Horas perdidas.
- Disponibilidad laboral.
- Reportes y exportación.
- Auditoría avanzada.

## Fase 6 — Workforce AI
- Servicio Python + FastAPI.
- Dataset histórico preparado desde MySQL.
- Predicción diaria de asistencia.
- Predicción semanal de disponibilidad.
- Pronóstico mensual de absentismo.
- Tendencias y alertas de cobertura.
- Integración REST con PHP.


## Estado de la Fase 4

Implementada en esta versión: solicitudes de vacaciones, licencias, permisos, descansos médicos y faltas justificadas; revisión por roles; integración con marcación; historial personal y ajustes de interfaz del colaborador.
