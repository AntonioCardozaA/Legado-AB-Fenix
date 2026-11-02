<?php

declare(strict_types=1);

// Synthetic records only. The guard runs before migrations or seeding.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'AI_ENABLED' => 'false', 'AI_HISTORY_INDEX_AUTO' => 'false', 'LOG_CHANNEL' => 'null', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $name => $value) {
    putenv($name . '=' . $value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Refusing any database other than SQLite :memory:.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
config(['view.compiled' => sys_get_temp_dir() . '/legado-performance-views']);
Illuminate\Support\Facades\File::ensureDirectoryExists(config('view.compiled'));
Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-10-30 10:00:00'));
$user = App\Models\User::factory()->withRole(App\Models\User::ROLE_TECNICO)->create();
Illuminate\Support\Facades\Auth::guard('web')->setUser($user);
$linea = App\Models\Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
$component = App\Models\Componente::create(['codigo' => 'L04_reductor_1_GUI_INF_TANQUE', 'nombre' => 'Guia inferior', 'linea' => 'L-04', 'activo' => true]);
$rows = [];
for ($i = 0; $i < 165; $i++) {
    $rows[] = [
        'linea_id' => $linea->id, 'componente_id' => $component->id, 'usuario_id' => $user->id,
        'reductor' => 'Reductor 1', 'lado' => ['VAPOR', 'PASILLO', null][$i % 3],
        'fecha_analisis' => '2026-07-10', 'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
        'estado' => App\Models\AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'History fixture ' . $i,
        'evidencia_fotos' => $i % 7 === 0 ? '["fixture.jpg"]' : '[]',
    ];
}
foreach (array_chunk($rows, 50) as $chunk) Illuminate\Support\Facades\DB::table('analisis_componentes')->insert($chunk);
$session = new Illuminate\Session\Store('performance-memory', new Illuminate\Session\ArraySessionHandler(120));
$session->setId(str_repeat('a', 40));
$session->start();
$session->put('_token', str_repeat('b', 40));
$r = Illuminate\Http\Request::create(route('analisis-lavadora.historial', [], false), 'GET', ['linea_id' => $linea->id, 'componente_id' => 'GUI_INF_TANQUE', 'reductor' => 'Reductor 1']);
$r->setUserResolver(fn () => $user);
$r->setLaravelSession($session);
$route = $app['router']->getRoutes()->match($r);
$r->setRouteResolver(fn () => $route);
$app->instance('request', $r);
$run = function () use ($app, $r): array {
    $view = $app->make(App\Http\Controllers\AnalisisLavadoraController::class)->historial($r);
    $data = $view->getData();
    $html = $view->render();
    return ['visible_models' => $data['analisis']->count(), 'html_bytes' => strlen($html)];
};
$run();
for ($i = 0; $i < 3; $i++) {
    Illuminate\Support\Facades\DB::flushQueryLog();
    Illuminate\Support\Facades\DB::enableQueryLog();
    $t = hrtime(true);
    $result = $run();
    $ms = (hrtime(true) - $t) / 1e6;
    $queries = Illuminate\Support\Facades\DB::getQueryLog();
    Illuminate\Support\Facades\DB::disableQueryLog();
    echo json_encode($result + ['fixture_rows' => 165, 'ms' => round($ms, 2), 'sql' => count($queries), 'database' => 'sqlite :memory:']) . PHP_EOL;
}
