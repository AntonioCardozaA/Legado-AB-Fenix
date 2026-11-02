<?php

declare(strict_types=1);

// The included guard permits only SQLite :memory:, before any fixture insert.
require __DIR__ . '/benchmark-history-response.php';
ini_set('memory_limit', '512M');
$batch = [];
for ($i = 165; $i < 5001; $i++) {
    $row = $rows[$i % count($rows)];
    $row['actividad'] = 'General export fixture ' . $i . ' ' . str_repeat('x', 600);
    $row['evidencia_fotos'] = '[]';
    $batch[] = $row;
    if (count($batch) === 100 || $i === 5000) {
        Illuminate\Support\Facades\DB::table('analisis_componentes')->insert($batch);
        $batch = [];
    }
}
$temp = sys_get_temp_dir() . '/legado-general-export-' . bin2hex(random_bytes(5));
Illuminate\Support\Facades\File::ensureDirectoryExists($temp);
config(['excel.temporary_files.local_path' => $temp]);
Maatwebsite\Excel\Facades\Excel::getFacadeRoot();
gc_collect_cycles();
$baseline = memory_get_usage(false);
memory_reset_peak_usage();
$start = hrtime(true);
$exportClass = $exportClass ?? App\Exports\ReporteLavadoraGeneralExcel::class;
$export = new $exportClass('2026-07-01', '2026-07-31');
$xlsx = Maatwebsite\Excel\Facades\Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
echo json_encode(['case' => 'general_washer_xlsx', 'export_class' => $exportClass, 'fixture_rows' => 5001, 'ms' => round((hrtime(true) - $start) / 1e6, 2), 'extra_peak_bytes' => memory_get_peak_usage(false) - $baseline, 'xlsx_bytes' => strlen($xlsx), 'temporary_directory' => $temp, 'external_requests' => 0]) . PHP_EOL;
