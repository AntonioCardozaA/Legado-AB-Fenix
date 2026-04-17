# Runmigraton.ps1 - Ejecutar migraciones de Laravel

$projectPath = "d:\laragon\www\legado-ab-fenix"
Set-Location $projectPath

# Intentar buscar PHP en Laragon
$phpVersions = @(
    "d:\laragon\bin\php\php-8.4-64\php.exe",
    "d:\laragon\bin\php\php-8.3-64\php.exe",
    "d:\laragon\bin\php\php-8.2-64\php.exe",
    "d:\laragon\bin\php\php-8.1-64\php.exe"
)

$phpExe = $null
foreach ($path in $phpVersions) {
    if (Test-Path $path) {
        $phpExe = $path
        Write-Host "✓ PHP encontrado en: $phpExe"
        break
    }
}

if (-not $phpExe) {
    Write-Host "✗ No se encontró PHP en Laragon"
    Write-Host "Por favor ejecuta manualmente desde Laragon Terminal:"
    Write-Host "cd d:\laragon\www\legado-ab-fenix && php artisan migrate"
    exit 1
}

# Ejecutar migraciones
Write-Host ""
Write-Host "Ejecutando migraciones..."
Write-Host ""

& $phpExe artisan migrate

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓ Migraciones ejecutadas correctamente"
} else {
    Write-Host ""
    Write-Host "✗ Error al ejecutar migraciones"
}
