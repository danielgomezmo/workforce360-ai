# Hotfix Workforce360 AI v3.2

## Error corregido

`SQLSTATE[HY093]: Invalid parameter number` al acceder a Mi asistencia o registrar asistencia.

## Causa

Con `PDO::ATTR_EMULATE_PREPARES = false`, un placeholder nombrado no debe reutilizarse varias veces en la misma consulta.

Se corrigieron:

- `Marcacion::scheduleForDate()` (`:fecha_inicio` y `:fecha_fin`).
- Búsqueda de marcaciones (un placeholder por campo).
- Búsqueda de colaboradores (un placeholder por campo).

## Instalación sobre v3.1

No ejecutar scripts SQL. Reemplazar la carpeta del proyecto por la versión v3.2 y conservar la misma base de datos `workforce360_ai`.
