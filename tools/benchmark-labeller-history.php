<?php

declare(strict_types=1);

ob_start();
require __DIR__ . '/benchmark-history-response.php';
ob_end_clean();
$user->forceFill(['name' => 'History Fixture', 'email' => 'history-fixture@example.test'])->saveQuietly();
$user->getAllPermissions();
$components = [];
foreach (['A', 'B'] as $machine) {
    $components[$machine] = App\Models\Componente::create([
        'codigo' => 'ETQ_L04_' . $machine . '_PERF', 'nombre' => 'Component Fixture', 'linea' => $linea->nombre,
        'reductor' => App\Support\EtiquetadoraCatalog::maquinaLabel($machine), 'ubicacion' => 'Fixture group',
        'grupo' => 'Fixture group', 'mecanismo' => 'Fixture mechanism', 'cantidad_total' => 3,
        'cantidad_original' => '3*maquina', 'tipo_equipo' => App\Support\EtiquetadoraCatalog::TIPO_EQUIPO, 'activo' => true,
    ]);
}
$records = [];
for ($index = 0; $index < 501; $index++) {
    $machine = $index % 2 === 0 ? 'A' : 'B';
    $records[] = [
        'linea_id' => $linea->id, 'componente_id' => $components[$machine]->id, 'usuario_id' => $user->id,
        'reductor' => App\Support\EtiquetadoraCatalog::maquinaLabel($machine), 'maquina' => $machine,
        'fecha_analisis' => '2026-07-10', 'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
        'estado' => App\Models\AnalisisEtiquetadora::ESTADO_BUENO, 'actividad' => 'Fixture ' . $index . ' ' . str_repeat('x', 600),
        'total_componentes' => 3, 'cantidad_componentes_revisados' => 1, 'componentes_revisados' => json_encode([$index % 3 + 1]),
        'evidencia_fotos' => '[]',
    ];
}
foreach (array_chunk($records, 50) as $chunk) Illuminate\Support\Facades\DB::table('analisis_etiquetadora')->insert($chunk);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
foreach (['all', 'A', 'B'] as $machine) {
    $params = ['linea_id' => $linea->id] + ($machine === 'all' ? [] : ['maquina' => $machine]);
    $request = Illuminate\Http\Request::create(route('analisis-etiquetadora.historial', [], false), 'GET', $params);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session);
    $route = $app['router']->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);
    $app->instance('request', $request);
    $run = function () use ($app, $request): array {
        $view = $app->make(App\Http\Controllers\AnalisisEtiquetadoraController::class)->historial($request);
        $data = $view->getData();
        $payloadSha = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $html = $view->render();
        return ['models' => $data['analisis']->count(), 'payload_sha' => $payloadSha, 'html_bytes' => strlen($html), 'html_sha' => hash('sha256', $html)];
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
        echo json_encode(['case' => $machine, 'ms' => round($elapsed, 2), 'sql' => count($queries), 'extra_peak_bytes' => $peak] + $result) . PHP_EOL;
    }
}
