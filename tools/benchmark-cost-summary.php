<?php

// This shared bootstrap refuses any DB other than SQLite :memory: before migrations.
ob_start();
require __DIR__ . '/benchmark-history-response.php';
ob_end_clean();
Illuminate\Support\Carbon::setTestNow('2026-10-31 12:00:00');
$rows = [];
for ($i = 0; $i < 5001; $i++) {
    $rows[] = [
        'linea_id' => $linea->id, 'source_type' => $i % 3 === 0 ? 'estado_cambiado' : 'manual',
        'cost_date' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 28) + 1),
        'quantity' => 1, 'unit_cost' => 123.45, 'total_cost' => 123.45,
        'component_snapshot' => $i % 2 ? 'Servo chico' : 'Guia inferior',
        'catalog_name_snapshot' => 'Fixture cost', 'catalog_sku_snapshot' => 'SKU-' . $i,
        'notas' => str_repeat('Historical note retained in the detail module. ', 80),
        'metadata' => json_encode(['component_code' => $i % 2 ? 'SERVO_CHICO' : 'GUI_INF_TANQUE']),
        'sync_key' => 'summary-fixture-' . $i, 'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
    ];
    if (count($rows) === 100 || $i === 5000) {
        Illuminate\Support\Facades\DB::table('lavadora_cost_entries')->insert($rows);
        $rows = [];
    }
}
$service = app(App\Services\LavadoraCostAnalyticsService::class);
$service->globalSummaryData();
for ($sample = 0; $sample < 3; $sample++) {
    gc_collect_cycles();
    memory_reset_peak_usage();
    $memory = memory_get_usage();
    Illuminate\Support\Facades\DB::flushQueryLog();
    Illuminate\Support\Facades\DB::enableQueryLog();
    $start = hrtime(true);
    $result = $service->globalSummaryData();
    $elapsed = (hrtime(true) - $start) / 1e6;
    $peak = memory_get_peak_usage() - $memory;
    $queries = count(Illuminate\Support\Facades\DB::getQueryLog());
    Illuminate\Support\Facades\DB::disableQueryLog();
    echo json_encode(['fixture_rows' => 5001, 'ms' => round($elapsed, 2), 'extra_peak_bytes' => $peak,
        'sql' => $queries, 'summary_sha256' => hash('sha256', json_encode($result, JSON_PRESERVE_ZERO_FRACTION)),
        'database' => 'sqlite :memory:']) . PHP_EOL;
    unset($result);
}
