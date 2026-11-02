<?php

declare(strict_types=1);

// Synthetic data in a guarded in-memory SQLite database. Never uses operational tables.
$mode = $argv[1] ?? '';
if (!in_array($mode, ['collection', 'query'], true)) throw new RuntimeException('Use collection or query.');
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'AI_ENABLED' => 'false', 'AI_HISTORY_INDEX_AUTO' => 'false', 'LOG_CHANNEL' => 'null', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $name => $value) {
    putenv($name . '=' . $value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
ini_set('memory_limit', '512M');
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Refusing to migrate or seed any database other than SQLite :memory:.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$linea = App\Models\Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true]);
$count = 5001;
$batch = [];
for ($i = 1; $i <= $count; $i++) {
    $batch[] = [
        'linea_id' => $linea->id, 'modulo' => 1, 'nivel' => 'SUPERIOR', 'lado' => 'VAPOR', 'componente' => 'ANILLAS',
        'fecha_analisis' => '2026-07-10', 'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
        'numero_orden' => str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'estado' => App\Models\AnalisisPasteurizadora::ESTADO_BUENO,
        'actividad' => 'Export fixture ' . $i . ' ' . str_repeat('x', 600), 'componentes_revisados' => '[1]',
        'cantidad_componentes_revisados' => 1, 'total_componentes' => 3, 'numeracion_version' => 2,
    ];
    if (count($batch) === 100 || $i === $count) {
        Illuminate\Support\Facades\DB::table('analisis_pasteurizadora')->insert($batch);
        $batch = [];
    }
}
$query = App\Models\AnalisisPasteurizadora::query()->with('linea')->where('linea_id', $linea->id)->latest('fecha_analisis')->latest('created_at');
Maatwebsite\Excel\Facades\Excel::getFacadeRoot();
gc_collect_cycles();
$baseline = memory_get_usage(false);
memory_reset_peak_usage();
$start = hrtime(true);
$export = $mode === 'query'
    ? new App\Exports\AnalisisPasteurizadoraQueryExport($query)
    : new App\Exports\AnalisisPasteurizadoraExport($query->get());
$data = Maatwebsite\Excel\Facades\Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
echo json_encode([
    'variant' => $mode, 'rows' => $count, 'ms' => round((hrtime(true) - $start) / 1e6, 2),
    'peak_extra_bytes' => memory_get_peak_usage(false) - $baseline, 'xlsx_bytes' => strlen($data),
    'database' => 'sqlite :memory:', 'php' => PHP_VERSION,
], JSON_UNESCAPED_SLASHES) . PHP_EOL;
