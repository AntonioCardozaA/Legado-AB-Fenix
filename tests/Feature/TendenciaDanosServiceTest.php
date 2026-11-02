<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Services\TendenciaDanosService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TendenciaDanosServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_damage_variants_and_reports_current_side_breakdown(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $componente = Componente::create([
            'linea' => 'L-04',
            'nombre' => 'Guia inferior',
            'codigo' => 'GUI_INF_TANQUE_TEST',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Lavadora',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, $componente, 'Danado - Requiere cambio', '2026-06-20', 'VAPOR');
        $this->crearAnalisis($linea, $componente, 'Dano - Requiere cambio', '2026-06-19', 'PASILLO');
        $this->crearAnalisis($linea, $componente, 'Danado - Cambiado', '2026-06-18', 'VAPOR');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-06-10');
        $this->crearAnalisis($linea, $componente, 'Desgaste moderado', '2026-05-30', 'PASILLO');
        $this->crearAnalisis($linea, $componente, 'Buen estado', '2026-06-17', 'VAPOR');

        $service = app(TendenciaDanosService::class);
        $referencia = Carbon::parse('2026-06-20');

        $analisis52124 = $service->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            $referencia,
            $service->ventanas52124()
        );
        $analisis30147 = $service->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            $referencia,
            $service->ventanas30147()
        );

        $ventana4Semanas = collect($analisis52124['ventanas'])->firstWhere('key', 'semanas_4');
        $ventana7Dias = collect($analisis30147['ventanas'])->firstWhere('key', 'dias_7');

        $this->assertSame(5, $ventana4Semanas['current']);
        $this->assertSame(3, $ventana7Dias['current']);
        $this->assertSame(['VAPOR' => 2, 'PASILLO' => 1], $ventana7Dias['current_lados']);
        $this->assertCount(5, $ventana4Semanas['current_eventos']);
        $this->assertSame('Reductor 1', $ventana4Semanas['current_eventos'][0]['reductor']);
    }

    public function test_monthly_rows_can_be_limited_to_a_date_range(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $componente = Componente::create([
            'linea' => 'L-04',
            'nombre' => 'Guia inferior',
            'codigo' => 'GUI_INF_TANQUE_RANGE_TEST',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Lavadora',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-01-15');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-02-10');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-03-05');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-03-20');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-04-10');

        $rows = app(TendenciaDanosService::class)->construirFilasMensuales(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            12,
            Carbon::parse('2026-03-15'),
            Carbon::parse('2026-02-01')
        );

        $this->assertSame(['Marzo 2026', 'Febrero 2026'], $rows->pluck('periodo')->all());
        $this->assertSame(2, $rows->firstWhere('periodo', 'Marzo 2026')->total_danos_52_semanas);
        $this->assertSame(1, $rows->firstWhere('periodo', 'Febrero 2026')->total_danos_52_semanas);
    }

    public function test_report_shared_events_match_independent_sources_including_short_window_last_failure(): void
    {
        $lines = collect(['L-04', 'L-05', 'L-06'])->map(fn ($name) => Linea::create(['nombre' => $name, 'activo' => true]));
        $component = Componente::create(['codigo' => 'SERVO_CHICO', 'nombre' => 'Servo', 'linea' => 'L-04', 'activo' => true]);
        foreach (['2024-10-30', '2025-12-31', '2026-08-01', '2026-10-01', '2026-10-30', '2026-10-31'] as $date) {
            $this->crearAnalisis($lines[0], $component, 'Desgaste severo', $date, 'VAPOR');
        }
        $this->crearAnalisis($lines[1], $component, 'Desgaste moderado', '2026-07-01');
        $service = app(TendenciaDanosService::class);
        $end = Carbon::parse('2026-10-30');
        foreach ([null, Carbon::parse('2026-09-15'), Carbon::parse('2026-10-31')] as $start) {
            $expected = [
                'mensual' => $lines->mapWithKeys(fn ($line) => [$line->id => $service->construirFilasMensuales($line, 'lavadoras', 12, $end, $start)]),
                '52124' => $service->calcularPorLineas($lines, 'lavadoras', $end, $service->ventanas52124()),
                '30147' => $service->calcularPorLineas($lines, 'lavadoras', $end, $service->ventanas30147()),
            ];
            \Illuminate\Support\Facades\DB::flushQueryLog();
            \Illuminate\Support\Facades\DB::enableQueryLog();
            $actual = $service->construirResumenReporte($lines, 'lavadoras', $end, 12, $start);
            $queries = \Illuminate\Support\Facades\DB::getQueryLog();
            \Illuminate\Support\Facades\DB::disableQueryLog();
            $this->assertSame(json_encode($expected), json_encode($actual));
            $this->assertCount(3, $queries);
            $this->assertNull($actual['30147'][$lines[1]->id]['resumen']['ultima_falla']);
            $this->assertNotNull($actual['52124'][$lines[1]->id]['resumen']['ultima_falla']);
        }
        $this->crearAnalisis($lines[1], $component, 'Desgaste severo', '2026-10-30');
        $fresh = $service->construirResumenReporte($lines, 'lavadoras', $end);
        $this->assertNotNull($fresh['30147'][$lines[1]->id]['resumen']['ultima_falla']);
        $empty = $service->construirResumenReporte(collect(), 'lavadoras', $end);
        $this->assertSame([], $empty['52124']);
        $this->assertTrue($empty['mensual']->isEmpty());
    }

    public function test_report_shared_events_preserve_both_pasteurizer_sources(): void
    {
        $line = Linea::create(['nombre' => 'P-03', 'activo' => true]);
        \App\Models\AnalisisPasteurizadora::withoutEvents(fn () => \App\Models\AnalisisPasteurizadora::create([
            'linea_id' => $line->id, 'modulo' => 1, 'componente' => 'ANILLAS', 'nivel' => 'SUPERIOR',
            'lado' => 'VAPOR', 'fecha_analisis' => '2026-10-01', 'estado' => 'Desgaste severo',
            'actividad' => 'Mechanical fixture', 'tipo_registro' => 'quick',
        ]));
        $component = Componente::create(['codigo' => 'HYD_REPORT', 'nombre' => 'Hydraulic fixture', 'linea' => 'P-03', 'activo' => true]);
        \App\Models\AnalisisCentralHidraulica::withoutEvents(fn () => \App\Models\AnalisisCentralHidraulica::create([
            'linea_id' => $line->id, 'componente_id' => $component->id, 'fecha_analisis' => '2026-10-20',
            'estado' => 'Desgaste moderado', 'actividad' => 'Hydraulic fixture', 'piso' => 'SUPERIOR',
        ]));
        $service = app(TendenciaDanosService::class);
        $lines = collect([$line]);
        $end = Carbon::parse('2026-10-30');
        $expected = [
            'mensual' => collect([$line->id => $service->construirFilasMensuales($line, 'pasteurizadoras', 12, $end)]),
            '52124' => $service->calcularPorLineas($lines, 'pasteurizadoras', $end, $service->ventanas52124()),
            '30147' => $service->calcularPorLineas($lines, 'pasteurizadoras', $end, $service->ventanas30147()),
        ];
        $actual = $service->construirResumenReporte($lines, 'pasteurizadoras', $end);
        $this->assertSame(json_encode($expected), json_encode($actual));
        $areas = collect($actual['30147'][$line->id]['ventanas'][0]['current_eventos'])->pluck('area')->unique()->sort()->values()->all();
        $this->assertSame(['central_hidraulica', 'mecanica'], $areas);
    }

    private function crearAnalisis(
        Linea $linea,
        Componente $componente,
        string $estado,
        string $fecha,
        ?string $lado = null
    ): void {
        static $orden = 1;

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'lado' => $lado,
            'fecha_analisis' => $fecha,
            'numero_orden' => 'OT-TEND-' . str_pad((string) $orden++, 3, '0', STR_PAD_LEFT),
            'estado' => $estado,
            'actividad' => 'Registro de prueba',
        ]);
    }
}
