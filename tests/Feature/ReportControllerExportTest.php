<?php

namespace Tests\Feature;

use App\Exports\ReporteLavadoraGeneralExcel;
use App\Http\Controllers\ReporteController;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportControllerExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_and_line_downloads_keep_every_cell_and_filter(): void
    {
        $this->actingAs(User::factory()->withRole(User::ROLE_ADMIN)->create());
        $line = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $other = Linea::firstOrCreate(['nombre' => 'L-05'], ['activo' => true]);
        $component = Componente::create(['codigo' => 'EXPORT_FIXTURE', 'nombre' => 'Fixture', 'activo' => true]);
        foreach ([$line, $other] as $item) foreach (['2026-07-15', '2025-07-15'] as $date) {
            AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create(['linea_id' => $item->id, 'componente_id' => $component->id,
                'reductor' => 'Reductor 1', 'fecha_analisis' => $date, 'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'Export fixture']));
        }
        $temp = sys_get_temp_dir() . '/legado-controller-test-' . bin2hex(random_bytes(5));
        mkdir($temp);
        config(['excel.temporary_files.local_path' => $temp]);
        foreach (['completo', 'linea'] as $type) {
            $from = \Carbon\Carbon::parse('2026-07-01');
            $to = \Carbon\Carbon::parse('2026-07-31');
            $method = new \ReflectionMethod(ReporteController::class, 'exportarExcel');
            $response = $method->invoke(app(ReporteController::class), $type, $type === 'linea' ? $line->id : null, 'lavadoras', $from, $to);
            $expected = Excel::raw(new ReporteLavadoraGeneralExcel($from, $to, $type === 'linea' ? $line->id : null), \Maatwebsite\Excel\Excel::XLSX);
            $reference = $temp . '/reference.xlsx';
            file_put_contents($reference, $expected);
            try {
                $this->assertSame($this->cells($reference), $this->cells($response->getFile()->getPathname()));
            } finally {
                unlink($reference);
                unlink($response->getFile()->getPathname());
            }
        }
    }

    private function cells(string $path): array
    {
        $book = IOFactory::load($path);
        $data = [];
        foreach ($book->getAllSheets() as $sheet) $data[$sheet->getTitle()] = $sheet->toArray();
        $book->disconnectWorksheets();
        return $data;
    }
}
