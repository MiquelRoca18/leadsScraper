@echo off
REM Script para ejecutar MapLeads Frontend
REM Este archivo inicia la aplicación y abre el navegador automáticamente

setlocal enabledelayedexpansion

echo.
echo ============================================
echo    MapLeads - Frontend
echo ============================================
echo.

REM Buscar el .exe en dist/ o en el directorio actual
if exist "dist\MapLeads-Frontend\MapLeads-Frontend.exe" (
    set "EXE_PATH=dist\MapLeads-Frontend\MapLeads-Frontend.exe"
) else if exist "MapLeads-Frontend.exe" (
    set "EXE_PATH=MapLeads-Frontend.exe"
) else (
    echo Error: No se encontró MapLeads-Frontend.exe
    echo.
    echo Para crear el ejecutable:
    echo   1. Abre PowerShell o CMD en este directorio
    echo   2. Ejecuta: pip install -r requirements.txt
    echo   3. Ejecuta: pyinstaller build_mapleads.spec
    echo.
    pause
    exit /b 1
)

echo Iniciando MapLeads...
echo.

REM Ejecutar la aplicación
REM (launcher.py abrirá el navegador automáticamente)
"%EXE_PATH%"
