@echo off
setlocal
title Workforce360 AI - Verificacion FastAPI

echo Verificando http://127.0.0.1:8000/health ...
echo.

curl.exe -s --max-time 5 http://127.0.0.1:8000/health
if errorlevel 1 (
    echo.
    echo.
    echo [NO DISPONIBLE] FastAPI no respondio.
    echo Ejecuta INICIAR_AI_WORKFORCE.bat y deja esa ventana abierta.
) else (
    echo.
    echo.
    echo [OK] El servicio Workforce AI esta respondiendo.
)

echo.
pause
endlocal
