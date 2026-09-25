WORKFORCE360 AI - SEGUIMIENTO DE PRONOSTICOS + EXPLICACION DEL MODELO
=====================================================================

QUE AGREGA ESTA VERSION
-----------------------
1. Guarda automaticamente el pronostico diario en la tabla pronosticos.
2. Guarda automaticamente el pronostico semanal en la tabla pronosticos.
3. Evita duplicar el mismo pronostico: actualiza el registro de la misma fecha/version.
4. Muestra historial de predicciones diarias en Workforce AI.
5. Cuando una fecha ya termino, compara prediccion vs asistencia real.
6. Calcula la diferencia absoluta en puntos porcentuales.
7. Muestra un error real medio calculado solo con predicciones ya cerradas.
8. Agrega la seccion "¿Por que se genero esta prediccion?" con variables de contexto.
9. El asistente Administrador ahora entiende:
   - Historial de predicciones
   - ¿Fue correcta la prediccion de ayer?
   - Comparar prediccion 25/09/2026
10. Mantiene el asistente flotante v2 y la lista de asistencia por sede.

IMPORTANTE
----------
- NO borres la carpeta actual del proyecto.
- NO borres .git, .env ni ai_service/.venv.
- Este paquete NO reemplaza config/ai.php, por lo que conserva tu timeout actual (por ejemplo 30 segundos).
- No necesitas crear una tabla nueva. Tu base ya contiene la tabla pronosticos.

INSTALACION
-----------
1. Cierra o deja en espera el navegador.
2. Descomprime este ZIP.
3. Copia el CONTENIDO de la carpeta Workforce360AI encima de:

   C:\xampp\htdocs\Workforce360AI

4. Acepta reemplazar archivos cuando Windows lo pregunte.
5. Reinicia Apache desde XAMPP.
6. Enciende MySQL.
7. Inicia Workforce AI con:

   C:\xampp\htdocs\Workforce360AI\INICIAR_AI_WORKFORCE.bat

8. No cierres la consola de Uvicorn.
9. Abre Workforce AI en el navegador y pulsa Ctrl + F5.

PRUEBAS RECOMENDADAS
--------------------
A. Abre Workforce AI. Debe aparecer el pronostico diario y semanal.
B. Debe aparecer una nueva seccion "¿Por que se genero esta prediccion?".
C. Debe aparecer "Seguimiento de pronosticos".
D. Recarga la pagina: no deberia crear duplicados de la misma fecha/modelo.
E. En phpMyAdmin ejecuta VERIFICAR_PRONOSTICOS.sql.
F. En el asistente flotante, como Administrador, prueba:

   Historial de predicciones
   ¿Fue correcta la prediccion de ayer?
   Comparar prediccion 25/09/2026
   Prediccion de asistencia

COMO FUNCIONA LA COMPARACION
----------------------------
- Fecha futura: queda como PENDIENTE.
- Fecha de hoy: queda EN CURSO para evitar comparar una jornada incompleta.
- Fecha pasada con datos: se marca EVALUADO y muestra:
  prediccion, asistencia real y error absoluto en puntos porcentuales.
- Fecha pasada sin personal programado/datos: se marca SIN DATOS.

SOBRE LA EXPLICACION
--------------------
La nueva seccion muestra variables que entraron al modelo, por ejemplo:
- Dia de la semana.
- Personal programado.
- Asistencia del ultimo dia historico.
- Asistencia de referencia de hace 7 dias.
- Promedio de asistencia de 7 dias.
- Promedio de asistencia de 30 dias.
- Tardanza media reciente.

Estas variables son CONTEXTO del modelo, no una afirmacion causal.

SI FASTAPI NO FUNCIONA
----------------------
Ejecuta:

   VERIFICAR_AI_WORKFORCE.bat

Tambien puedes abrir:

   http://127.0.0.1:8000/health
   http://127.0.0.1:8000/docs

ANTES DEL COMMIT
----------------
No hagas commit hasta probar:
1. Workforce AI abre.
2. Pronostico diario funciona.
3. Pronostico semanal funciona.
4. Se guarda al menos un registro en pronosticos.
5. Historial aparece.
6. Asistente sigue funcionando.

Despues usa los comandos del archivo COMANDOS_GIT_FASE7_SEGUIMIENTO.txt.
