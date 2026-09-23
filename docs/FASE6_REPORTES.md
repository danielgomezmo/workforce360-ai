# Fase 6 - Centro de Reportes

Este avance completa el Centro de Reportes antes de continuar con Auditoría.

## Reportes disponibles

- Reporte de Asistencia.
- Reporte de Tardanzas.
- Reporte de Salidas Anticipadas.
- Reporte de Incidencias.
- Reporte de Horas Trabajadas y cumplimiento de jornada.

## Filtros

Según el reporte se puede filtrar por:

- Rango de fechas.
- Colaborador.
- Área.
- Estado de jornada.
- Tipo de incidencia.
- Estado de incidencia.

Los supervisores solo pueden consultar información de colaboradores asociados a su equipo.

## Exportación

Cada reporte incluye:

- Excel real en formato `.xlsx` generado con ExcelJS.
- PDF en formato A4 horizontal generado con jsPDF + AutoTable.
- Vista de impresión optimizada desde el navegador.

Los documentos incluyen identidad visual DEVIOZ, título, periodo, filtros utilizados, indicadores de resumen, tabla y pie de página.

Las librerías de exportación se cargan por CDN, al igual que Bootstrap y Chart.js, por lo que se requiere conexión a Internet al momento de exportar Excel o PDF.

## Base de datos

Este avance no requiere migraciones ni cambios en la estructura MySQL.

## Siguiente avance

Dentro de la misma Fase 6 se implementará Auditoría avanzada y consulta de trazabilidad.
