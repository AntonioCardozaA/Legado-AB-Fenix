<?php

ob_start();
require __DIR__ . '/benchmark-history-response.php';
ob_end_clean(); // The shared bootstrap refuses databases other than SQLite :memory:.
ini_set('memory_limit', '512M');
$user->syncRoles([App\Models\User::ROLE_ADMIN]);
$batch = [];
for ($i = 165; $i < 1001; $i++) {
    $row = $rows[$i % count($rows)];
    $row['actividad'] = 'Controller fixture ' . $i;
    $row['evidencia_fotos'] = '[]';
    $batch[] = $row;
    if (count($batch) === 100 || $i === 1000) {
        Illuminate\Support\Facades\DB::table('analisis_componentes')->insert($batch);
        $batch = [];
    }
}
$temp = sys_get_temp_dir() . '/legado-controller-export-' . bin2hex(random_bytes(5));
mkdir($temp, 0777, true);
config(['excel.temporary_files.local_path' => $temp]);
$controller = app(App\Http\Controllers\ReporteController::class);
$method = new ReflectionMethod($controller, 'exportarExcel');
$from = Carbon\Carbon::parse('2026-07-01')->startOfDay();
$to = Carbon\Carbon::parse('2026-07-31')->endOfDay();
foreach (['completo', 'linea'] as $type) {
    for ($sample = -1; $sample < 3; $sample++) {
        gc_collect_cycles();
        memory_reset_peak_usage();
        $memory = memory_get_usage();
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        $start = hrtime(true);
        $response = $method->invoke($controller, $type, $type === 'linea' ? $linea->id : null, 'lavadoras', $from, $to);
        $ms = (hrtime(true) - $start) / 1e6;
        $peak = memory_get_peak_usage() - $memory;
        $sql = count(Illuminate\Support\Facades\DB::getQueryLog());
        Illuminate\Support\Facades\DB::disableQueryLog();
        $book = PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());
        $cells = [];
        foreach ($book->getAllSheets() as $sheet) $cells[$sheet->getTitle()] = $sheet->toArray(null, true, true, true);
        $hash = hash('sha256', json_encode($cells, JSON_PRESERVE_ZERO_FRACTION));
        $book->disconnectWorksheets();
        unset($book, $cells);
        unlink($response->getFile()->getPathname());
        if ($sample >= 0) echo json_encode(['type' => $type, 'rows' => 1001, 'ms' => round($ms, 2), 'sql' => $sql,
            'extra_peak_bytes' => $peak, 'cells_sha256' => $hash, 'database' => 'sqlite :memory:']) . PHP_EOL;
    }
}
