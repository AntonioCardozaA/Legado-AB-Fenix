#!/usr/bin/env php
<?php
// ============================================
// EJECUTAR MIGRACIONES PENDIENTES
// ============================================

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "\n==============================================\n";
echo "  EJECUTANDO MIGRACIONES\n";
echo "==============================================\n\n";

echo "📌 Ejecutando: php artisan migrate --force\n\n";

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$exitCode = $kernel->call('migrate', ['--force' => true]);

echo "\n";

if ($exitCode === 0) {
    echo "✅ ¡MIGRACIONES EJECUTADAS CON ÉXITO!\n\n";
    echo "cambios realizados:\n";
    echo "  • Se creó la columna 'componentes_revisados' (JSON)\n";
    echo "  • Ahora se pueden guardar qué piezas fueron revisadas\n\n";
} else {
    echo "❌ Error al ejecutar migraciones (código: {$exitCode})\n";
    echo "Verifica que la base de datos esté disponible.\n\n";
}

echo "==============================================\n";
echo "✅ LISTO\n";
echo "==============================================\n\n";

exit($exitCode);
