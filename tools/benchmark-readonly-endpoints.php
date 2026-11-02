<?php

declare(strict_types=1);

$mode = $argv[1] ?? 'dashboards';
if (!in_array($mode, ['dashboards', 'catalogues', 'histories', 'knowledge'], true)) throw new RuntimeException('Use dashboards, catalogues, histories or knowledge.');
require dirname(__DIR__) . '/vendor/autoload.php';
$environment = Dotenv\Dotenv::parse(file_get_contents(dirname(__DIR__) . '/.env'));
if (($environment['DB_CONNECTION'] ?? '') !== 'mysql' || !in_array($environment['DB_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Expected local MySQL; refusing another host.');
}
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
    $app['config']->set(['cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null', 'mail.default' => 'array', 'maintenance_ai.enabled' => false, 'maintenance_ai.history_index.auto' => false]);
});
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-10-30 10:00:00', 'America/Mexico_City'));
$connection = Illuminate\Support\Facades\DB::connection();
$pdo = $connection->getPdo();
$pdo->exec('SET SESSION MAX_EXECUTION_TIME=2500');
$pdo->exec('START TRANSACTION READ ONLY');
try {
    if ($mode === 'catalogues') {
        echo json_encode(['cost_catalog' => App\Models\CostCatalogItem::count(), 'cost_rules' => App\Models\CostAutomationRule::count(), 'alert_recipients' => App\Models\AlertRecipient::count()]) . PHP_EOL;
        return;
    }
    if ($mode === 'histories') {
        echo json_encode([
            'washer' => App\Models\AnalisisLavadora::count(),
            'pasteurizer' => App\Models\AnalisisPasteurizadora::count(),
            'hydraulic' => App\Models\AnalisisCentralHidraulica::count(),
            'labeller' => App\Models\AnalisisEtiquetadora::count(),
        ]) . PHP_EOL;
        return;
    }
    $user = App\Models\User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->orderBy('id')->firstOrFail();
    Illuminate\Support\Facades\Auth::guard('web')->setUser($user);
    $user->getAllPermissions();
    $session = new Illuminate\Session\Store('performance-memory', new Illuminate\Session\ArraySessionHandler(120));
    $session->start();
    if ($mode === 'knowledge') {
        config(['maintenance_ai.enabled' => false, 'maintenance_ai.knowledge.semantic_query_enabled' => false]);
        $search = $app->make(App\Services\Maintenance\AssistantKnowledgeSearchService::class);
        $run = fn () => $search->search('guia reductor cadena', ['module' => 'lavadora'], $user);
        $run();
        for ($i = 0; $i < 3; $i++) {
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            $start = hrtime(true);
            $data = $run();
            $elapsed = (hrtime(true) - $start) / 1e6;
            $queries = $connection->getQueryLog();
            $connection->disableQueryLog();
            echo json_encode(['case' => 'lexical_knowledge_only', 'ms' => round($elapsed, 2), 'sql' => count($queries), 'results' => count($data), 'sha' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))]) . PHP_EOL;
        }
        return;
    }
    $cases = [
        'global' => [App\Http\Controllers\DashboardController::class, 'lavadoraGlobal', '/dashboard/lavadoras', []],
        'operative' => [App\Http\Controllers\DashboardController::class, 'lavadoraOperativo', '/dashboard/lavadora/operativo', []],
        'washer_report' => [App\Http\Controllers\ReporteController::class, 'index', '/reportes', ['tipo' => 'lavadoras']],
    ];
    foreach ($cases as $name => [$class, $method, $path, $params]) {
        $request = Illuminate\Http\Request::create($path, 'GET', $params);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);
        $app->instance('request', $request);
        $run = fn () => $app->make($class)->{$method}($request)->getData();
        $run();
        for ($i = 0; $i < 3; $i++) {
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            $start = hrtime(true);
            $data = $run();
            $elapsed = (hrtime(true) - $start) / 1e6;
            $queries = $connection->getQueryLog();
            $connection->disableQueryLog();
            echo json_encode(['case' => $name, 'ms' => round($elapsed, 2), 'sql' => count($queries), 'sql_ms' => round(array_sum(array_column($queries, 'time')), 2), 'sha' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))]) . PHP_EOL;
        }
    }
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
