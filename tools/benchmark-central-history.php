<?php

declare(strict_types=1);

// Reuse the pre-migration SQLite :memory: guard. Discard its washer-only profile.
ob_start();
require __DIR__ . '/benchmark-history-response.php';
ob_end_clean();
$user->forceFill(['name' => 'History Fixture', 'email' => 'history-fixture@example.test'])->saveQuietly();
$user->syncRoles(App\Models\User::ROLE_ADMIN);
$user->unsetRelation('roles');
$user->getAllPermissions();
$line = App\Models\Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true]);
$configuration = App\Models\CentralHidraulicaConfiguracion::where('pasteurizador', 'P-03')
    ->where('piso', 'SUPERIOR')->whereHas('componente', fn ($query) => $query->where('codigo', 'ELECTROVALVULAS'))->firstOrFail();
$records = [];
for ($index = 0; $index < 501; $index++) {
    $records[] = [
        'linea_id' => $line->id, 'configuracion_id' => $configuration->id,
        'componente_id' => $configuration->componente_id, 'usuario_id' => $user->id,
        'piso' => $configuration->piso, 'lado' => $index % 2 === 0 ? 'LADO_1' : 'LADO_2',
        'fecha_analisis' => '2026-07-10', 'estado' => App\Models\AnalisisCentralHidraulica::ESTADO_BUENO,
        'tipo_registro' => 'quick', 'actividad' => 'History fixture ' . $index . ' ' . str_repeat('x', 600),
        'cantidad_componentes_revisados' => 1, 'total_componentes' => 3,
        'componentes_revisados' => '[1]', 'evidencia_fotos' => '[]',
        'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
    ];
}
foreach (array_chunk($records, 50) as $chunk) Illuminate\Support\Facades\DB::table('analisis_central_hidraulica')->insert($chunk);
$record = App\Models\AnalisisCentralHidraulica::orderBy('id')->firstOrFail();
foreach (['list', 'detail'] as $case) {
    $path = $case === 'list'
        ? route('pasteurizadora.central-hidraulica.historial', [], false)
        : route('pasteurizadora.central-hidraulica.show', $record->id, false);
    $request = Illuminate\Http\Request::create($path, 'GET', ['linea_id' => $line->id]);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session);
    $route = $app['router']->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);
    $app->instance('request', $request);
    $run = function () use ($app, $request, $case, $record): array {
        $controller = $app->make(App\Http\Controllers\AnalisisPasteurizadoraCentralHidraulicaController::class);
        $view = $case === 'list' ? $controller->historial($request) : $controller->show($record->id);
        $data = $view->getData();
        $visible = $data[$case === 'list' ? 'analisis' : 'historial'];
        $html = $view->render();
        return ['models' => $visible->count(), 'total' => $visible instanceof Illuminate\Contracts\Pagination\LengthAwarePaginator ? $visible->total() : $visible->count(), 'html_bytes' => strlen($html), 'html_sha' => hash('sha256', $html)];
    };
    $run();
    for ($sample = 0; $sample < 3; $sample++) {
        gc_collect_cycles();
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        $memory = memory_get_usage(false);
        memory_reset_peak_usage();
        $start = hrtime(true);
        $result = $run();
        $elapsed = (hrtime(true) - $start) / 1e6;
        $peak = memory_get_peak_usage(false) - $memory;
        $queries = Illuminate\Support\Facades\DB::getQueryLog();
        Illuminate\Support\Facades\DB::disableQueryLog();
        echo json_encode(['case' => $case, 'ms' => round($elapsed, 2), 'extra_peak_bytes' => $peak, 'sql' => count($queries)] + $result) . PHP_EOL;
    }
}
