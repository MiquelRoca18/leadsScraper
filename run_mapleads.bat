@echo off
REM Script para ejecutar MapLeads Frontend
REM Este archivo inicia la aplicación automáticamente

setlocal enabledelayedexpansion

echo.
echo ============================================
echo    MapLeads - Frontend
echo ============================================
echo.

REM Buscar el .exe en scraperLead-web/dist/
if exist "scraperLead-web\dist\MapLeads-Frontend\MapLeads-Frontend.exe" (
    set "EXE_PATH=scraperLead-web\dist\MapLeads-Frontend\MapLeads-Frontend.exe"
) else (
    echo Error: No se encontró MapLeads-Frontend.exe
    echo.
    echo Para crear el ejecutable:
    echo   1. Ve a la carpeta: cd scraperLead-web
    echo   2. Ejecuta: build_exe.bat
    echo.
    pause
    exit /b 1
)

echo Iniciando MapLeads...
echo.

REM Ejecutar la aplicación
"%EXE_PATH%"
