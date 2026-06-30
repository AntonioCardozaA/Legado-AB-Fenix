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
