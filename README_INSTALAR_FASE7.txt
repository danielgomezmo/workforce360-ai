WORKFORCE360 AI - INTEGRACIÓN FASE 7 (PHP + FASTAPI)

IMPORTANTE
- NO borres tu carpeta Workforce360AI.
- NO borres .git.
- NO reemplaces ai_service/.env.
- NO copies una .venv nueva.
- Este paquete se copia SOBRE tu proyecto actual conservando tu repositorio y tu entorno virtual.

1. DETENER UVICORN
   En el CMD donde está ejecutándose:
   Ctrl + C

2. COPIAR ESTE PAQUETE
   Copia el contenido de este ZIP dentro de:
   C:\xampp\htdocs\Workforce360AI\

   Acepta reemplazar los archivos indicados del proyecto.

3. CONFIRMAR .env
   C:\xampp\htdocs\Workforce360AI\ai_service\.env

   Debe conservar al menos:
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=workforce360_ai_pruebas
   DB_USER=root
   DB_PASSWORD=
   AI_TRAINING_CUTOFF=2026-09-22

4. VALIDAR Y REENTRENAR
   cd C:\xampp\htdocs\Workforce360AI\ai_service

   .\.venv\Scripts\python.exe -m app.validar_modelos
   .\.venv\Scripts\python.exe -m app.train

   El modelo deberá quedar marcado como EXPERIMENTAL si la validación temporal no supera al baseline.

5. LEVANTAR FASTAPI
   .\.venv\Scripts\python.exe -m uvicorn app.main:app --reload --port 8000

6. PROBAR API
   http://127.0.0.1:8000/health
   http://127.0.0.1:8000/model/status
   http://127.0.0.1:8000/evaluation/status
   http://127.0.0.1:8000/forecast/daily
   http://127.0.0.1:8000/forecast/weekly

7. ABRIR WORKFORCE AI EN PHP
   Inicia Apache/MySQL en XAMPP.
   Inicia sesión con un rol ADMINISTRADOR, RRHH, SUPERVISOR o GERENCIA.
   En el menú lateral aparecerá:

   Workforce AI

   URL directa:
   http://localhost/Workforce360AI/public/index.php?route=ai

8. SI SALE "SERVICIO DE IA NO DISPONIBLE"
   FastAPI no está levantado. Ejecuta otra vez:
   .\.venv\Scripts\python.exe -m uvicorn app.main:app --reload --port 8000

9. GIT (DESPUÉS DE PROBAR TODO)
   Desde:
   C:\xampp\htdocs\Workforce360AI

   git status
   git diff
   git add .
   git status
   git commit -m "Fase 7 - Integración del módulo predictivo de asistencia"
   git push

NOTA
Los datos usados actualmente son sintéticos. Las métricas validan técnicamente el pipeline, pero no prueban rendimiento real en producción.
