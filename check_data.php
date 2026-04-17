#!/usr/bin/env php
<?php
// Script para verificar los últimos análisis guardados

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Ejecutar tinker para revisar los datos
echo "\n==============================================\n";
echo "  CONSULTANDO ÚLTIMOS ANÁLISIS\n";
echo "==============================================\n\n";

// Usar la kernel para ejecutar un comando
$code = <<<'PHP'
use App\Models\AnalisisPasteurizadora;

$analisis = AnalisisPasteurizadora::latest('id')->limit(5)->get();

if ($analisis->count() === 0) {
    echo "❌ No hay análisis registrados.\n\n";
} else {
    foreach ($analisis as $row) {
        echo "ID: {$row->id} | Orden: #{$row->numero_orden}\n";
        echo "  Componente: {$row->componente}\n";
        echo "  Módulo: {$row->modulo}\n";
        echo "  Total Piezas: {$row->total_piezas}\n";
        echo "  Revisadas Piezas: {$row->revisadas_piezas}\n";
        echo "  Componentes Revisados: " . json_encode($row->componentes_revisados) . "\n";
        if ($row->componentes_revisados && is_array($row->componentes_revisados)) {
            echo "  ✅ Marcados: " . implode(", #", $row->componentes_revisados) . "\n";
        }
        echo "---\n\n";
    }
}
PHP;

// Crear archivo temporal con el código
$tmpfile = tempnam(sys_get_temp_dir(), 'laravel_');
file_put_contents($tmpfile, $code);

// Ejecutar como comando tinker
$exitCode = $kernel->call('tinker', ['--execute' => $code]);

echo "==============================================\n";

unlink($tmpfile);
exit(0);
