<?php
// Script para verificar y ejecutar migraciones pendientes

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Ejecutar migraciones pendientes
$exitCode = $kernel->call('migrate', ['--force' => true]);

if ($exitCode === 0) {
    echo "\n✅ MIGRACIONES EJECUTADAS EXITOSAMENTE\n";
    echo "La columna 'componentes_revisados' ha sido creada en la tabla 'analisis_pasteurizadora'.\n\n";
} else {
    echo "\n❌ ERROR AL EJECUTAR MIGRACIONES\n";
}

exit($exitCode);
