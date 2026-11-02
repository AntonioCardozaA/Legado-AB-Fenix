<?php

declare(strict_types=1);

ob_start();
require __DIR__ . '/benchmark-history-response.php';
ob_end_clean();
$user->forceFill(['name' => 'History Fixture', 'email' => 'history-fixture@example.test'])->saveQuietly();
$user->syncRoles(App\Models\User::ROLE_ADMIN);
$user->unsetRelation('roles');
$user->getAllPermissions();
$line = App\Models\Linea::firstOrCreate(['nombre' => 'P-06'], ['activo' => true]);
$records = [];
for ($index = 0; $index < 165; $index++) {
    $records[] = [
        'linea_id' => $line->id, 'usuario_id' => $user->id, 'area' => 'mecanica',
        'modulo' => 2, 'componente' => 'ANILLAS', 'lado' => ['VAPOR', 'PASILLO', null][$index % 3], 'nivel' => 'SUPERIOR',
        'fecha_analisis' => '2026-07-10', 'estado' => App\Models\AnalisisPasteurizadora::ESTADO_BUENO,
        'tipo_registro' => 'normal', 'actividad' => 'History fixture ' . $index . ' ' . str_repeat('x', 1000),
        'cantidad_componentes_revisados' => 1, 'total_componentes' => 5,
        'componentes_revisados' => json_encode([6 + $index % 5]),
        'evidencia_fotos' => $index % 7 === 0 ? '["fixture.jpg"]' : '[]',
        'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
    ];
}
foreach (array_chunk($records, 50) as $chunk) Illuminate\Support\Facades\DB::table('analisis_pasteurizadora')->insert($chunk);
$request = Illuminate\Http\Request::create(route('pasteurizadora.analisis-pasteurizadora.historial', [], false), 'GET', ['linea_id' => $line->id]);
$request->setUserResolver(fn () => $user);
$request->setLaravelSession($session);
$route = $app['router']->getRoutes()->match($request);
$request->setRouteResolver(fn () => $route);
$app->instance('request', $request);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$controllerClass = App\Http\Controllers\AnalisisPasteurizadoraController::class;
if (in_array('--baseline', $argv, true)) {
    $readGit = static function (string $path): string {
        $process = new Symfony\Component\Process\Process(['git', 'show', 'HEAD:' . $path], dirname(__DIR__));
        $process->mustRun();
        return $process->getOutput();
    };
    $oldController = $readGit('app/Http/Controllers/AnalisisPasteurizadoraController.php');
    $currentController = file_get_contents(app_path('Http/Controllers/AnalisisPasteurizadoraController.php'));
    $historyBody = static function (string $source): array {
        $start = strpos($source, '    public function historial(Request $request)');
        $end = strpos($source, '    public function planAccion(Request $request)', $start);
        if ($start === false || $end === false) throw new RuntimeException('Cannot isolate history method.');
        return [$start, $end, substr($source, $start, $end - $start)];
    };
    [, , $oldHistory] = $historyBody($oldController);
    if (str_contains($oldHistory, 'PasteurizadoraHistoryPages')) throw new RuntimeException('HEAD no longer contains the unpaginated baseline.');
    [$start, $end] = $historyBody($currentController);
    $reference = substr($currentController, 0, $start) . $oldHistory . substr($currentController, $end);
    $reference = str_replace('class AnalisisPasteurizadoraController extends Controller', 'class PerformanceOriginalPasteurizadoraController extends Controller', $reference);
    eval(substr($reference, 5));
    $controllerClass = App\Http\Controllers\PerformanceOriginalPasteurizadoraController::class;
    $views = sys_get_temp_dir() . '/legado-history-reference-' . bin2hex(random_bytes(5));
    Illuminate\Support\Facades\File::ensureDirectoryExists($views . '/pasteurizadora/analisis-pasteurizadora');
    file_put_contents($views . '/pasteurizadora/analisis-pasteurizadora/historial.blade.php', $readGit('resources/views/pasteurizadora/analisis-pasteurizadora/historial.blade.php'));
    view()->getFinder()->prependLocation($views);
}
$run = function () use ($app, $request, $controllerClass): array {
    $start = hrtime(true);
    $view = $app->make($controllerClass)->historial($request);
    $data = $view->getData();
    $html = $view->render();
    $elapsed = (hrtime(true) - $start) / 1e6;
    $renderPeak = memory_get_peak_usage(false);
    if (isset($data['historialSecciones'])) {
        $sections = $data['historialSecciones'];
    } else {
        // Execute only the original trusted PHP grouping block as a baseline oracle.
        $source = file_get_contents(view()->getFinder()->find('pasteurizadora.analisis-pasteurizadora.historial'));
        $start = strpos($source, '{{-- Agrupación de análisis --}}');
        if ($start === false || !preg_match('/@php(.*?)@endphp/s', substr($source, $start), $match)) throw new RuntimeException('Missing original grouping oracle.');
        $analisis = $data['analisis'];
        $sections = eval($match[1] . '; return $historialSecciones;');
    }
    $summary = $sections->map(fn ($section) => [
        'key' => $section['key'], 'total' => $section['total'],
        'groups' => $section['grupos']->map(fn ($group) => array_diff_key($group, ['registros' => true, 'paginador' => true]))->values()->all(),
    ])->all();
    return ['ms' => round($elapsed, 2), 'render_peak' => $renderPeak, 'models' => $data['analisis']->count(), 'summary_sha' => hash('sha256', json_encode($summary, JSON_THROW_ON_ERROR)), 'html_bytes' => strlen($html)];
};
if (in_array('--fixtures', $argv, true)) {
    $directory = sys_get_temp_dir() . '/legado-browser-' . bin2hex(random_bytes(5));
    Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
    file_put_contents($directory . '/pasteur-history.html', $app->make($controllerClass)->historial($request)->render());
    echo json_encode(['fixture_root' => $directory, 'baseline' => in_array('--baseline', $argv, true)]) . PHP_EOL;
    return;
}
$run();
for ($sample = 0; $sample < 3; $sample++) {
    gc_collect_cycles();
    Illuminate\Support\Facades\DB::flushQueryLog();
    Illuminate\Support\Facades\DB::enableQueryLog();
    $memory = memory_get_usage(false);
    memory_reset_peak_usage();
    $result = $run();
    $peak = $result['render_peak'] - $memory;
    unset($result['render_peak']);
    $queries = Illuminate\Support\Facades\DB::getQueryLog();
    Illuminate\Support\Facades\DB::disableQueryLog();
    echo json_encode(['variant' => in_array('--baseline', $argv, true) ? 'baseline' : 'current', 'extra_peak_bytes' => $peak, 'sql' => count($queries)] + $result) . PHP_EOL;
}
