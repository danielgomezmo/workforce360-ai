@echo off
setlocal
cd /d "%~dp0ai_service"
title Workforce360 AI - FastAPI

echo ======================================================
echo   WORKFORCE360 AI - INICIO DEL SERVICIO PREDICTIVO
echo ======================================================
echo.

if not exist ".venv\Scripts\python.exe" (
    echo [ERROR] No se encontro el entorno virtual en:
    echo %CD%\.venv
    echo.
    echo El modulo AI necesita primero su entorno virtual .venv.
    pause
    exit /b 1
)

echo Iniciando FastAPI en http://127.0.0.1:8000
echo Swagger: http://127.0.0.1:8000/docs
echo.
echo IMPORTANTE: deja esta ventana abierta mientras uses Workforce AI.
echo.

".venv\Scripts\python.exe" -m uvicorn app.main:app --host 127.0.0.1 --port 8000

echo.
echo El servicio se detuvo.
pause
endlocal
