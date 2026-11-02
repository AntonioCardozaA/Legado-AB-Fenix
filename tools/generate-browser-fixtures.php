<?php

// The included benchmark checks SQLite :memory: before migrations or seeding.
require __DIR__ . '/benchmark-history-response.php';
$user->forceFill(['name' => 'Performance Fixture', 'email' => 'fixture@example.test'])->saveQuietly();

$output = sys_get_temp_dir() . '/legado-browser-' . bin2hex(random_bytes(5));
mkdir($output, 0777, true);
view()->share('errors', new Illuminate\Support\ViewErrorBag);
$prepare = function (string $path, array $params = []) use ($app, $user, $session) {
    $request = Illuminate\Http\Request::create($path, 'GET', $params);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session);
    $route = $app['router']->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);
    $app->instance('request', $request);
    return $request;
};
$request = $prepare('/analisis-lavadora/historial', ['linea_id' => $linea->id, 'componente_id' => 'GUI_INF_TANQUE', 'reductor' => 'Reductor 1']);
file_put_contents($output . '/history.html', $app->make(App\Http\Controllers\AnalisisLavadoraController::class)->historial($request)->render());
$request = $prepare(route('analisis-lavadora.create-quick', [], false), ['linea_id' => $linea->id, 'componente_codigo' => 'GUI_INF_TANQUE', 'reductor' => 'Reductor 1']);
file_put_contents($output . '/form.html', $app->make(App\Http\Controllers\AnalisisLavadoraController::class)->createQuick($request)->render());
$prepare('/lavadoras/diagramas/l04-l09');
file_put_contents($output . '/diagrams.html', view('lavadoras.diagramas.l04-l09')->render());
$monitorAlerts = [
    ['reductor' => 'Reductor 1', 'componente' => 'Guia inferior fixture', 'componente_codigo' => 'GUI_INF_TANQUE', 'estado' => 'Dañado - Requiere cambio', 'severity' => 'red', 'nivel' => 'Critico', 'fecha' => '25/10/2026', 'observaciones' => 'Visibility fixture', 'lado' => 'VAPOR'],
    ['reductor' => 'Reductor 1', 'componente' => 'Guia superior fixture', 'componente_codigo' => 'GUI_SUP_TANQUE', 'estado' => 'Desgaste moderado', 'severity' => 'orange', 'nivel' => 'Desgaste', 'fecha' => '25/10/2026', 'observaciones' => 'Visibility fixture', 'lado' => 'VAPOR'],
];
file_put_contents($output . '/diagram-visibility.html', view('lavadoras.diagramas.l04-l09', ['monitorAlertas' => $monitorAlerts])->render());
$request = $prepare('/dashboard/lavadoras');
$secondLine = App\Models\Linea::firstOrCreate(['nombre' => 'L-05'], ['activo' => true, 'tipo' => 'lavadora']);
$secondComponent = App\Models\Componente::create(['codigo' => 'L05_reductor_1_SERVO_CHICO', 'nombre' => 'Servo fixture', 'linea' => 'L-05', 'activo' => true]);
App\Models\AnalisisLavadora::withoutEvents(fn () => App\Models\AnalisisLavadora::create([
    'linea_id' => $secondLine->id, 'componente_id' => $secondComponent->id, 'usuario_id' => $user->id,
    'reductor' => 'Reductor 1', 'fecha_analisis' => '2026-10-25', 'estado' => App\Models\AnalisisLavadora::ESTADO_DANADO,
    'actividad' => 'Second line critical fixture',
]));
file_put_contents($output . '/dashboard.html', $app->make(App\Http\Controllers\DashboardController::class)->lavadoraGlobal($request)->render());
$pasteurizerLine = App\Models\Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true, 'tipo' => 'pasteurizadora']);
$user->syncRoles([App\Models\User::ROLE_ADMIN]);
App\Models\AnalisisPasteurizadora::withoutEvents(fn () => App\Models\AnalisisPasteurizadora::create([
    'area' => App\Models\AnalisisPasteurizadora::AREA_MECANICA, 'linea_id' => $pasteurizerLine->id,
    'modulo' => 1, 'componente' => 'ANILLAS', 'nivel' => 'SUPERIOR', 'lado' => 'VAPOR',
    'fecha_analisis' => '2026-10-25', 'estado' => App\Models\AnalisisPasteurizadora::ESTADO_DANADO,
    'actividad' => 'Mechanical critical fixture', 'componentes_revisados' => [1],
    'cantidad_componentes_revisados' => 1, 'total_componentes' => 3, 'usuario_id' => $user->id,
]));
$centralConfig = App\Models\CentralHidraulicaConfiguracion::where('pasteurizador', 'P-03')
    ->where('piso', App\Models\CentralHidraulicaConfiguracion::PISO_SUPERIOR)
    ->whereHas('componente', fn ($query) => $query->where('codigo', 'ELECTROVALVULAS'))->firstOrFail();
App\Models\AnalisisCentralHidraulica::withoutEvents(fn () => App\Models\AnalisisCentralHidraulica::create([
    'linea_id' => $pasteurizerLine->id, 'configuracion_id' => $centralConfig->id, 'componente_id' => $centralConfig->componente_id,
    'piso' => $centralConfig->piso, 'lado' => App\Models\AnalisisCentralHidraulica::LADO_1,
    'fecha_analisis' => '2026-10-25', 'estado' => App\Models\AnalisisCentralHidraulica::ESTADO_DANADO,
    'actividad' => 'Hydraulic critical fixture', 'componentes_revisados' => [1],
    'cantidad_componentes_revisados' => 1, 'total_componentes' => $centralConfig->cantidad, 'usuario_id' => $user->id,
]));
foreach ([App\Models\AnalisisPasteurizadora::AREA_MECANICA, App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA] as $area) {
    App\Models\PlanAccion::withoutEvents(fn () => App\Models\PlanAccion::create([
        'linea_id' => $pasteurizerLine->id, 'tipo_equipo' => 'pasteurizadora', 'area_pasteurizadora' => $area,
        'actividad' => 'Pending plan fixture ' . $area, 'completado' => false, 'fecha_pcm1' => '2026-11-03',
    ]));
}
$request = $prepare('/dashboard/pasteurizadoras');
file_put_contents($output . '/pasteurizer.html', $app->make(App\Http\Controllers\DashboardController::class)->pasteurizadoraGlobal($request)->render());
$request = $prepare('/dashboard/pasteurizadoras', ['parte' => App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]);
file_put_contents($output . '/hydraulics.html', $app->make(App\Http\Controllers\DashboardController::class)->pasteurizadoraGlobal($request)->render());
$prepare('/login');
file_put_contents($output . '/login.html', view('auth.login')->render());
echo json_encode(['fixture_directory' => $output, 'database' => 'sqlite :memory:']) . PHP_EOL;
