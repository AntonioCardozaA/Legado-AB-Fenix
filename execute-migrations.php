<?php
// Ejecutar migraciones
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');

$exit = $kernel->call('migrate');

echo $exit === 0 ? "✓ Migraciones ejecutadas correctamente\n" : "✗ Error al ejecutar migraciones\n";

exit($exit);
