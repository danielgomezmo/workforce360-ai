# Fase 6 — Auditoría avanzada y trazabilidad

Con este avance se completa la Fase 6 de Workforce360 AI.

## Funcionalidades

- Módulo de Auditoría visible únicamente para Administrador.
- Bitácora de accesos, marcaciones y operaciones ya registradas por el sistema.
- Filtros por rango de fechas, usuario, acción, módulo y búsqueda libre.
- Indicadores de eventos acumulados, eventos del día, usuarios auditados y eventos sensibles.
- Vista detallada de cada evento.
- Comparación de valores anteriores y nuevos.
- Registro de IP, navegador, fecha y hora.
- Exportación del historial filtrado a CSV.
- Paginación para mantener una consulta ordenada.
- Registros en modo de solo lectura desde la interfaz.
- Protección de claves sensibles en nuevos registros de auditoría.

## Acceso

Por seguridad, el módulo está disponible únicamente para el rol `ADMINISTRADOR`.

## Base de datos

No se requiere una migración adicional. La tabla `auditoria` fue creada desde las primeras fases y ya contiene las columnas necesarias para esta implementación.
