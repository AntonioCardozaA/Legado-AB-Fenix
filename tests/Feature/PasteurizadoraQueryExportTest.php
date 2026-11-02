<?php

namespace Tests\Feature;

use App\Exports\AnalisisPasteurizadoraExport;
use App\Exports\AnalisisPasteurizadoraQueryExport;
use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PasteurizadoraQueryExportTest extends TestCase
{
    public function test_snapshot_guard_uses_legacy_variable_only_for_unknown_variable_errors(): void
    {
        $connection = \Mockery::mock(\Illuminate\Database\MySqlConnection::class)->makePartial();
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('isMaria')->andReturn(false);
        $pdoError = new \PDOException('Unknown system variable');
        $pdoError->errorInfo = ['HY000', 1193, 'Unknown system variable'];
        $queryError = new \Illuminate\Database\QueryException('mysql', 'SELECT @@transaction_isolation AS isolation_level', [], $pdoError);
        $connection->shouldReceive('selectOne')->with('SELECT @@transaction_isolation AS isolation_level')->once()->andThrow($queryError);
        $connection->shouldReceive('selectOne')->with('SELECT @@tx_isolation AS isolation_level')->once()->andReturn((object) ['isolation_level' => 'REPEATABLE-READ']);
        $method = new \ReflectionMethod(\App\Http\Controllers\AnalisisPasteurizadoraController::class, 'supportsChunkedExportSnapshot');
        $this->assertTrue($method->invoke(app(\App\Http\Controllers\AnalisisPasteurizadoraController::class), $connection));

        $connection = \Mockery::mock(\Illuminate\Database\MySqlConnection::class)->makePartial();
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('isMaria')->andReturn(false);
        $connection->shouldReceive('selectOne')->with('SELECT @@transaction_isolation AS isolation_level')->once()->andReturn((object) ['isolation_level' => 'READ-COMMITTED']);
        $this->assertFalse($method->invoke(app(\App\Http\Controllers\AnalisisPasteurizadoraController::class), $connection));
    }

    use RefreshDatabase;

    public function test_chunked_xlsx_preserves_every_cell_and_row_order_across_chunk_boundaries(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true]);
        $rows = [];
        for ($i = 1; $i <= 1003; $i++) {
            $rows[] = [
                'linea_id' => $linea->id, 'modulo' => 1, 'nivel' => 'SUPERIOR', 'lado' => 'VAPOR', 'componente' => 'ANILLAS',
                'fecha_analisis' => '2026-07-10', 'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
                'numero_orden' => str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Export fixture ' . $i, 'componentes_revisados' => '[1]', 'cantidad_componentes_revisados' => 1,
                'total_componentes' => 3, 'numeracion_version' => 2,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) DB::table('analisis_pasteurizadora')->insert($chunk);
        $query = AnalisisPasteurizadora::query()->with('linea')->where('linea_id', $linea->id)->latest('fecha_analisis')->latest('created_at');
        $expected = $this->workbookRows(new AnalisisPasteurizadoraExport((clone $query)->get()));
        DB::flushQueryLog();
        DB::enableQueryLog();
        $actual = $this->workbookRows(new AnalisisPasteurizadoraQueryExport($query));
        $selects = collect(DB::getQueryLog())->filter(fn ($row) => str_contains($row['query'], 'from "analisis_pasteurizadora"'));
        DB::disableQueryLog();
        $this->assertSame($expected, $actual);
        $this->assertCount(1004, $actual);
        $this->assertCount(12, $actual[0]);
        $this->assertSame('00001003', $actual[1003][7]);
        $this->assertCount(3, $selects);
        $this->assertTrue($selects->every(fn ($row) => str_contains($row['query'], 'limit 500')));
        $empty = (clone $query)->where('modulo', 99);
        $this->assertSame(
            $this->workbookRows(new AnalisisPasteurizadoraExport((clone $empty)->get())),
            $this->workbookRows(new AnalisisPasteurizadoraQueryExport($empty))
        );
    }

    private function workbookRows(object $export): array
    {
        $path = tempnam(sys_get_temp_dir(), 'legado-export-test-');
        if ($path === false) throw new \RuntimeException('Cannot create export fixture.');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load($path);
            $rows = $workbook->getActiveSheet()->toArray();
            $workbook->disconnectWorksheets();
            return $rows;
        } finally {
            unlink($path);
        }
    }
}
