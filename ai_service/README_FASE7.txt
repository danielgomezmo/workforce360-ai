WORKFORCE360 AI - FASE 7

1. Copiar la carpeta app y los archivos de este paquete dentro de:
   C:\xampp\htdocs\Workforce360AI\ai_service

2. Mantener tu .env real. Si no existe, crear uno tomando .env.example como referencia.

3. Activar/usar el entorno virtual ya creado.

4. Verificar sintaxis:
   .\.venv\Scripts\python.exe -m py_compile app\dataset.py
   .\.venv\Scripts\python.exe -m py_compile app\modeling.py
   .\.venv\Scripts\python.exe -m py_compile app\train.py
   .\.venv\Scripts\python.exe -m py_compile app\forecast.py
   .\.venv\Scripts\python.exe -m py_compile app\main.py

5. Comparar modelos:
   .\.venv\Scripts\python.exe -m app.comparar_modelos

6. Validación temporal:
   .\.venv\Scripts\python.exe -m app.validar_modelos

7. Entrenamiento automático del mejor modelo según holdout:
   .\.venv\Scripts\python.exe -m app.train

8. Diagnóstico:
   .\.venv\Scripts\python.exe -m app.diagnostico

9. Iniciar API:
   .\.venv\Scripts\python.exe -m uvicorn app.main:app --reload --port 8000

10. Probar en navegador:
    http://127.0.0.1:8000/health
    http://127.0.0.1:8000/dataset/status
    http://127.0.0.1:8000/model/status
    http://127.0.0.1:8000/forecast/daily
    http://127.0.0.1:8000/forecast/weekly
    http://127.0.0.1:8000/docs

NOTA METODOLÓGICA:
El dataset usado en desarrollo es sintético. Las métricas permiten validar técnicamente
el pipeline, pero no demuestran por sí solas rendimiento predictivo en un entorno real.
