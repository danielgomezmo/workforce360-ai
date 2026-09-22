# Workforce360 AI · Fase 4

Esta versión parte de la Fase 3.3 y conserva la arquitectura MVC existente.

## Correcciones de interfaz

- El dashboard del colaborador ya no muestra métricas globales de la empresa.
- El colaborador ve únicamente sus asistencias, puntualidad/tardanzas y solicitudes pendientes.
- `Mi Historial` es una pantalla real con filtros por fecha y estado.
- El reloj de marcación se mantiene centrado en una sola línea, incluido `a. m.` / `p. m.`.

## Funcionalidades de Fase 4

- Mis Solicitudes para colaboradores.
- Vacaciones.
- Licencias con goce y sin goce.
- Descansos médicos.
- Permisos por rango de fecha/hora.
- Falta justificada.
- Sustentos PDF/JPG/PNG de máximo 5 MB.
- Gestión de incidencias para Administrador y RRHH.
- Supervisor limitado a colaboradores de su equipo.
- Aprobación, rechazo y anulación con trazabilidad.
- Sincronización de solicitudes aprobadas con `vacaciones`, `licencias`, `descansos_medicos` y `permisos`.
- Una ausencia aprobada de jornada completa bloquea una nueva marcación para esa fecha.
- Auditoría de solicitud, revisión y anulación.

## Actualización desde Fase 3.3

1. Sustituir el código por esta versión.
2. Conservar la base `workforce360_ai` existente.
3. Ejecutar una sola vez `database/migrations/fase4_desde_v3_3.sql`.
4. Cerrar sesión y volver a iniciar sesión.

Para una instalación nueva puede importarse `database/workforce360_v4.sql`.
