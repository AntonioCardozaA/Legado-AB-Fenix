<?php

namespace Tests\Feature;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Services\Lef52124DataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Lef52124DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_latest_loaded_month_and_latest_import_per_line(): void
    {
        $l04 = $this->line('L-04');
        $l05 = $this->line('L-05');
        $l06 = $this->line('L-06');

        $this->import($l04, '2026-09-30', 1.0);
        $this->import($l06, '2026-09-30', 1.5);
        $this->import($l04, '2026-10-10', 2.0);
        $latestL04 = $this->import($l04, '2026-10-31', 2.73);
        Lef52124Import::create([
            'linea_id' => $l04->id,
            'data_date' => '2026-10-31',
            'source_filename' => 'empty.xlsx',
            'status' => 'success',
            'machines_count' => 0,
            'parts_count' => 0,
            'line_items_count' => 0,
        ]);
        $this->import($l05, '2026-10-20', 1.89);
        $this->import($l05, '2026-11-30', 9.0, 'failed');

        $payload = app(Lef52124DataService::class)->latestMonthDashboardPayload();
        $lines = collect($payload['lineas'])->keyBy('nombre');

        $this->assertSame('2026-10', $payload['period']);
        $this->assertSame('Octubre 2026', $payload['period_label']);
        $this->assertSame($latestL04->id, $lines['L-04']['import_id']);
        $this->assertEquals(1.365, $lines['L-04']['washer']['value_52_weeks']);
        $this->assertSame(['TRANSPORTADOR', 'LAVADORA DE BOTELLAS'], array_column($lines['L-04']['machines'], 'name'));
        $this->assertSame([false, true], array_column($lines['L-04']['machines'], 'highlight'));
        $this->assertSame($lines['L-04']['machines'][1]['id'], $lines['L-04']['washer']['id']);
        $this->assertEquals(0.945, $lines['L-05']['washer']['value_52_weeks']);
        $moduleData = app(Lef52124DataService::class)->dataPayload(['linea_id' => $l05->id]);
        $moduleWasherMachine = collect($moduleData['machines'])->firstWhere('name', 'LAVADORA DE BOTELLAS');
        $this->assertSame($moduleWasherMachine['value_52_weeks'], $lines['L-05']['washer']['value_52_weeks']);
        $this->assertNotEquals($moduleData['washer']['value_52_weeks'], $lines['L-05']['washer']['value_52_weeks']);
        $this->assertNull($lines['L-06']['import_id']);
        $this->assertNull($lines['L-06']['washer']);
        $this->assertSame([], $lines['L-06']['machines']);

        $this->import($l05, '2026-11-30', 3.0);
        $updated = app(Lef52124DataService::class)->latestMonthDashboardPayload();
        $this->assertSame('2026-11', $updated['period']);
        $this->assertNull(collect($updated['lineas'])->firstWhere('nombre', 'L-04')['import_id']);
    }

    public function test_dashboard_has_no_period_without_successful_imports(): void
    {
        $this->assertSame(
            ['period' => null, 'period_label' => null, 'lineas' => []],
            app(Lef52124DataService::class)->latestMonthDashboardPayload()
        );
    }

    public function test_lavadoras_dashboard_renders_latest_lef_period_and_both_charts(): void
    {
        $this->withoutMiddleware();
        $this->import($this->line('L-04'), '2026-10-31', 2.73);

        $this->get(route('dashboard.global.lavadoras'))
            ->assertOk()
            ->assertSee('Periodo de análisis: Octubre 2026')
            ->assertSee('Solo lavadora')
            ->assertSee('Pareto LEF - Máquinas')
            ->assertSee('lefDashboardLinea', false)
            ->assertDontSee('analisis52124Chart', false);
    }

    private function line(string $name): Linea
    {
        return Linea::create([
            'nombre' => $name,
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
    }

    private function import(Linea $line, string $date, float $value, string $status = 'success'): Lef52124Import
    {
        $import = Lef52124Import::create([
            'linea_id' => $line->id,
            'data_date' => $date,
            'source_filename' => 'lef-test.xlsx',
            'status' => $status,
            'machines_count' => 2,
            'parts_count' => 0,
            'line_items_count' => 1,
        ]);

        foreach ([
            [Lef52124Item::TYPE_LINE, 'LINEA COMPLETA', $value],
            [Lef52124Item::TYPE_MACHINE, 'LAVADORA DE BOTELLAS', $value / 2],
            [Lef52124Item::TYPE_MACHINE, 'TRANSPORTADOR', $value],
        ] as [$type, $name, $machineValue]) {
            Lef52124Item::create([
                'lef52124_import_id' => $import->id,
                'linea_id' => $line->id,
                'type' => $type,
                'item_name' => $name,
                'value_52_weeks' => $machineValue,
                'value_12_weeks' => $machineValue,
                'value_4_weeks' => $machineValue,
            ]);
        }

        return $import;
    }
}
