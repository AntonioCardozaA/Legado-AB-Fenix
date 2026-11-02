<?php

declare(strict_types=1);

// All records/session are synthetic; the included script guards SQLite before migration.
require __DIR__ . '/benchmark-history-response.php';
ini_set('memory_limit', '512M');
set_time_limit(60);
$user->syncRoles([App\Models\User::ROLE_ADMIN]);
$user->forceFill(['name' => 'PDF Fixture', 'email' => 'pdf.fixture@example.test'])->saveQuietly();
$subsetFonts = in_array('--subset-fonts', $argv ?? [], true);
$temp = sys_get_temp_dir() . '/legado-report-pdf-' . bin2hex(random_bytes(5));
foreach (['fonts', 'cache'] as $folder) Illuminate\Support\Facades\File::ensureDirectoryExists($temp . '/' . $folder);
config([
    'dompdf.options.font_dir' => $temp . '/fonts',
    'dompdf.options.font_cache' => $temp . '/fonts',
    'dompdf.options.temp_dir' => $temp . '/cache',
    'dompdf.options.log_output_file' => null,
    'dompdf.options.enable_remote' => false,
    'dompdf.options.isRemoteEnabled' => false,
    'dompdf.options.enable_font_subsetting' => $subsetFonts,
]);
$request = Illuminate\Http\Request::create('/fixture-report-pdf', 'GET');
$request->setUserResolver(fn () => $user);
$request->setLaravelSession($session);
$app->instance('request', $request);
Barryvdh\DomPDF\Facade\Pdf::getFacadeRoot();
gc_collect_cycles();
$baseline = memory_get_usage(false);
memory_reset_peak_usage();
$start = hrtime(true);
$method = new ReflectionMethod(App\Http\Controllers\ReporteController::class, 'exportarPDF');
$response = $method->invoke(app(App\Http\Controllers\ReporteController::class), 'completo', null, 'lavadoras', Carbon\Carbon::parse('2026-07-01')->startOfDay(), Carbon\Carbon::parse('2026-07-31')->endOfDay());
$pdf = $response->getContent();
if (!str_starts_with($pdf, '%PDF-')) throw new RuntimeException('Expected PDF output.');
$elapsed = (hrtime(true) - $start) / 1e6;
$extraPeak = memory_get_peak_usage(false) - $baseline;
file_put_contents($temp . '/report.pdf', $pdf);
$canvas = Barryvdh\DomPDF\Facade\Pdf::getDomPDF()->getCanvas();
$cpdf = $canvas->get_cpdf();
$objects = (new ReflectionProperty($cpdf, 'objects'))->getValue($cpdf);
$commandHash = hash_init('sha256');
foreach ($objects as $object) {
    if (($object['t'] ?? null) !== 'page') continue;
    foreach ($object['info']['contents'] ?? [] as $id) {
        $commands = $objects[$id]['c'] ?? '';
        hash_update($commandHash, strlen($commands) . ':' . $commands);
    }
}
$text = (new Smalot\PdfParser\Parser)->parseContent($pdf)->getText();
echo json_encode(['case' => 'general_washer_pdf', 'subset_fonts' => $subsetFonts, 'fixture_rows' => 165, 'ms' => round($elapsed, 2), 'extra_peak_bytes' => $extraPeak, 'pdf_bytes' => strlen($pdf), 'pages' => $canvas->get_page_count(), 'text_sha' => hash('sha256', $text), 'page_commands_sha' => hash_final($commandHash), 'temporary_directory' => $temp, 'external_requests' => 0]) . PHP_EOL;
