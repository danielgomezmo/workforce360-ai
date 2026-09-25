WORKFORCE360 AI - ASISTENTE FLOTANTE v2
========================================

NOVEDADES
---------
- Lista de asistentes en formato DEVIOZ, disponible solo para ADMINISTRADOR.
- Agrupación automática por sede usando colaboradores.sede.
- Botón rápido "Lista de hoy".
- Consulta del estado de FastAPI desde el chat.
- Ayuda desde el chat para activar Workforce AI.
- INICIAR_AI_WORKFORCE.bat para iniciar FastAPI con doble clic.
- VERIFICAR_AI_WORKFORCE.bat para comprobar el servicio.

FORMATO DEVIOZ
--------------
SISTEMA DEVIOZ
Asistencia Sede "Lima"
Viernes, 25 de setiembre de 2026

1. Nombre Apellido
2. Nombre Apellido
3. Nombre Apellido

Generado automáticamente · Sistema Devioz

La lista considera asistente a quien tenga una marcación de ingreso registrada en esa fecha.
Si existen varias sedes, se genera un bloque separado para cada sede.

PREGUNTAS DE PRUEBA - ADMINISTRADOR
-----------------------------------
- Lista de los que asistieron hoy
- Lista de asistencia de hoy
- ¿Quiénes asistieron hoy?
- ¿Quiénes asistieron ayer?
- Resumen de asistencia de hoy
- ¿Cuántas tardanzas hubo hoy?
- ¿Cuántos ausentes hay hoy?
- Incidencias pendientes
- Predicción de asistencia
- Predicción semanal
- Estado del modelo predictivo
- ¿Cómo activo Workforce AI?
- ¿FastAPI está activo?

PRIVACIDAD
----------
La lista global solo se habilita para ADMINISTRADOR.
El COLABORADOR sigue limitado a su propia asistencia, tardanzas, faltas, incidencias y marcaciones.

INSTALACIÓN
-----------
1. NO borres C:\xampp\htdocs\Workforce360AI porque contiene .git.
2. Copia el CONTENIDO de la carpeta Workforce360AI del ZIP sobre:
   C:\xampp\htdocs\Workforce360AI
3. Acepta reemplazar archivos.
4. El ZIP no incluye ni reemplaza .git, .env, .venv ni config/ai.php.
5. Reinicia Apache si fuera necesario y presiona Ctrl + F5 en el navegador.

CÓMO ACTIVAR WORKFORCE AI
-------------------------
Requisitos:
- Apache: START en XAMPP.
- MySQL: START en XAMPP.
- Debe existir C:\xampp\htdocs\Workforce360AI\ai_service\.venv

FORMA MÁS FÁCIL:
1. Abre C:\xampp\htdocs\Workforce360AI
2. Doble clic en INICIAR_AI_WORKFORCE.bat
3. Debe aparecer:
   Uvicorn running on http://127.0.0.1:8000
4. Deja esa ventana abierta mientras uses predicciones.

PARA VERIFICAR:
- Doble clic en VERIFICAR_AI_WORKFORCE.bat
- O abre http://127.0.0.1:8000/health
- Swagger: http://127.0.0.1:8000/docs

FORMA MANUAL EN POWERSHELL:
cd C:\xampp\htdocs\Workforce360AI\ai_service
.\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8000

IMPORTANTE SOBRE VELOCIDAD
--------------------------
El asistente NO ejecuta predicciones al abrir los módulos. FastAPI se consulta únicamente cuando el Administrador pregunta por predicciones o por su estado. Las consultas normales del chat se realizan directamente sobre MySQL.

BASE DE DATOS
-------------
No requiere migración ni tablas nuevas.

ANTES DEL COMMIT
----------------
1. Prueba "Lista de los que asistieron hoy" como ADMINISTRADOR.
2. Prueba "Mi asistencia este mes" como COLABORADOR.
3. Ejecuta INICIAR_AI_WORKFORCE.bat.
4. Prueba "¿FastAPI está activo?".
5. Prueba "Predicción de asistencia" y "Predicción semanal".

GIT - DESPUÉS DE VALIDAR
------------------------
cd C:\xampp\htdocs\Workforce360AI
git status
git diff
git add .
git status
git commit -m "Fase 7 - Integración de Workforce AI y asistente inteligente"
git push origin main
