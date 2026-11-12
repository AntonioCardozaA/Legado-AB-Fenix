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

    public function test_all_lines_sums_each_machine_between_lines_and_keeps_line_values_separate(): void
    {
        $this->withoutMiddleware();
        $l04 = $this->line('L-04');
        $l05 = $this->line('L-05');
        $this->line('L-06');
        $this->import($l04, '2026-09-30', 50);
        $this->import($l04, '2026-10-31', 9);
        $latest = $this->import($l04, '2026-10-31', 4);
        $l05Import = $this->import($l05, '2026-10-20', 6);
        $this->import($l05, '2026-11-30', 100, 'failed');
        $this->import($this->line('L-99'), '2026-11-30', 100);

        $payload = $this->getJson(route('lef52124.data', ['scope' => 'all']))
            ->assertOk()
            ->assertJsonPath('all_lines', true)
            ->assertJsonPath('summary.machines_count', 4)
            ->assertJsonPath('summary.lines_count', 2)
            ->assertJsonCount(2, 'machines')
            ->assertJsonCount(3, 'comparison')
            ->json();

        $this->assertSame('TRANSPORTADOR', $payload['machines'][0]['name']);
        foreach (['value_52_weeks', 'value_12_weeks', 'value_4_weeks'] as $column) {
            $this->assertEquals(10, $payload['machines'][0][$column]);
            $this->assertEquals(5, $payload['machines'][1][$column]);
        }
        $this->assertSame('10.00', $payload['machines'][0]['formatted_52']);
        $this->assertSame('TRANSPORTADOR', $payload['summary']['top_52']['name']);
        $this->assertSame('10.00', $payload['summary']['top_52']['value']);
        $this->assertEquals(4, $payload['comparison'][0]['value_52_weeks']);
        $this->assertEquals(6, $payload['comparison'][1]['value_52_weeks']);
        $this->assertNull($payload['comparison'][2]['value_52_weeks']);
        $this->assertSame($latest->id, $payload['comparison'][0]['import_id']);
        $this->assertSame($l05Import->id, $payload['comparison'][1]['import_id']);

        $this->getJson(route('lef52124.data', ['linea_id' => $l04->id]))
            ->assertOk()
            ->assertJsonCount(2, 'machines')
            ->assertJsonPath('machines.0.name', 'TRANSPORTADOR')
            ->assertJsonPath('import.id', $latest->id);
        app('view')->share('errors', new \Illuminate\Support\ViewErrorBag);
        $this->get(route('lef52124.index', ['scope' => 'all']))
            ->assertOk()
            ->assertViewHas('selectedLineaId', 'all')
            ->assertSee('data-linea-id="all">Todas</button>', false);
    }

    public function test_machine_totals_include_zero_and_only_lines_with_that_machine_for_each_window(): void
    {
        $l04 = $this->import($this->line('L-04'), '2026-10-31', 4);
        $l05 = $this->import($this->line('L-05'), '2026-10-20', 8);
        $l06 = $this->import($this->line('L-06'), '2026-10-20', 0);
        $l04->items()->where('item_name', 'TRANSPORTADOR')->update([
            'value_12_weeks' => 6,
            'value_4_weeks' => 3,
        ]);
        $l05->items()->where('item_name', 'TRANSPORTADOR')->update([
            'item_name' => '  transportador  ',
            'value_12_weeks' => 12,
            'value_4_weeks' => 1,
        ]);
        $l06->items()->where('item_name', 'LAVADORA DE BOTELLAS')->delete();

        $service = app(Lef52124DataService::class);
        $payload = $service->dataPayload(['scope' => 'all']);
        $this->assertSame(5, $payload['summary']['machines_count']);
        $this->assertCount(2, $payload['machines']);
        $transporter = $payload['machines'][0];
        $this->assertSame('TRANSPORTADOR', $transporter['name']);
        $this->assertEquals(12, $transporter['value_52_weeks']);
        $this->assertEquals(18, $transporter['value_12_weeks']);
        $this->assertEquals(4, $transporter['value_4_weeks']);
        $this->assertSame('18.00', $transporter['formatted_12']);
        $this->assertSame('4.00', $transporter['formatted_4']);
        $this->assertEquals(6, $payload['machines'][1]['value_52_weeks']);

        $fourWeeks = $service->dataPayload(['scope' => 'all', 'period' => '4']);
        $this->assertSame('LAVADORA DE BOTELLAS', $fourWeeks['machines'][0]['name']);
        $this->assertSame('LAVADORA DE BOTELLAS', $fourWeeks['summary']['top_4']['name']);
        $this->assertSame('6.00', $fourWeeks['summary']['top_4']['value']);
        $this->assertEquals(4, $l04->items()->where('item_name', 'TRANSPORTADOR')->first()->value_52_weeks);
    }

    public function test_all_lines_respects_the_selected_date_and_returns_empty_for_missing_dates(): void
    {
        $this->withoutMiddleware();
        $l04 = $this->line('L-04');
        $l05 = $this->line('L-05');
        $this->import($l04, '2026-09-30', 2);
        $this->import($l04, '2026-10-31', 8);
        $this->import($l05, '2026-10-31', 10);

        $this->getJson(route('lef52124.data', ['scope' => 'all', 'data_date' => '2026-09-30', 'period' => '4']))
            ->assertOk()
            ->assertJsonPath('summary.machines_count', 2)
            ->assertJsonPath('summary.lines_count', 1)
            ->assertJsonPath('comparison.1.import_id', null);
        $this->getJson(route('lef52124.data', ['scope' => 'all', 'data_date' => '2020-01-01']))
            ->assertOk()
            ->assertJsonPath('has_data', false);

        $this->getJson(route('lef52124.periods', ['scope' => 'all']))
            ->assertOk()
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('items.0.data_date', '2026-10-31')
            ->assertJsonPath('items.2.data_date', '2026-09-30');
        $this->getJson(route('lef52124.periods'))->assertUnprocessable();
        $this->getJson(route('lef52124.data', ['scope' => 'invalid']))->assertUnprocessable();
    }

    public function test_lavadoras_dashboard_renders_latest_lef_period_and_both_charts(): void
    {
        $this->withoutMiddleware();
        $this->import($this->line('L-04'), '2026-10-31', 2.73);

        $this->get(route('dashboard.global.lavadoras'))
            ->assertOk()
            ->assertSee('Periodo de análisis: Octubre 2026')
            ->assertSee('id="lefDashboardWasherChart"', false)
            ->assertSee('Pareto LEF - Máquinas')
            ->assertSee('id="lefDashboardParetoChart"', false)
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
