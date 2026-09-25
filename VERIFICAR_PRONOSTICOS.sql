-- Ejecutar en phpMyAdmin sobre workforce360_ai_pruebas (o la BD configurada en tu proyecto)
SELECT
    id_pronostico,
    tipo,
    fecha_generacion,
    fecha_objetivo_inicio,
    fecha_objetivo_fin,
    valor_estimado,
    unidad,
    version_modelo
FROM pronosticos
ORDER BY id_pronostico DESC
LIMIT 30;
