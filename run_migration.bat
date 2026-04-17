@echo off
REM Script para ejecutar migraciones desde Laragon

cd /d "d:\laragon\www\legado-ab-fenix"

REM Intenta con php desde laragon
if exist "d:\laragon\bin\php\php.exe" (
    "d:\laragon\bin\php\php.exe" artisan migrate --force
) else if exist "d:\laragon\bin\php\php81\php.exe" (
    "d:\laragon\bin\php\php81\php.exe" artisan migrate --force
) else if exist "d:\laragon\bin\php\php82\php.exe" (
    "d:\laragon\bin\php\php82\php.exe" artisan migrate --force
) else if exist "d:\laragon\bin\php\php84\php.exe" (
    "d:\laragon\bin\php\php84\php.exe" artisan migrate --force
) else (
    echo No se encontró PHP en Laragon
    pause
)

pause
