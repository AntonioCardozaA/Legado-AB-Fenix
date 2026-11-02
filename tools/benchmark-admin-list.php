<?php

// Reuses the benchmark's SQLite :memory: guard before creating synthetic data.
require __DIR__ . '/benchmark-history-response.php';
$user->syncRoles([App\Models\User::ROLE_ADMIN]);
$user->forceFill(['name' => 'ZZZ Fixture Admin'])->saveQuietly();
for ($i = 1; $i <= 164; $i++) {
    App\Models\User::factory()->withRole(App\Models\User::ROLE_TECNICO)->create(['name' => sprintf('Fixture %05d', $i)]);
}
view()->share('errors', new Illuminate\Support\ViewErrorBag);
$request = Illuminate\Http\Request::create(route('admin.users.index', [], false), 'GET');
$request->setUserResolver(fn () => $user);
$request->setLaravelSession($session);
$request->setRouteResolver(fn () => $app['router']->getRoutes()->match($request));
$app->instance('request', $request);
$run = function () use ($request) {
    $view = app(App\Http\Controllers\AdminUserController::class)->index($request);
    $rows = $view->getData()['users']->count();
    return ['visible_users' => $rows, 'html_bytes' => strlen($view->render())];
};
$run();
for ($i = 0; $i < 3; $i++) {
    Illuminate\Support\Facades\DB::flushQueryLog();
    Illuminate\Support\Facades\DB::enableQueryLog();
    $start = hrtime(true);
    $result = $run();
    $queries = Illuminate\Support\Facades\DB::getQueryLog();
    Illuminate\Support\Facades\DB::disableQueryLog();
    echo json_encode($result + ['case' => 'admin_directory', 'ms' => round((hrtime(true) - $start) / 1e6, 2), 'sql' => count($queries), 'fixture_users' => 165]) . PHP_EOL;
}
