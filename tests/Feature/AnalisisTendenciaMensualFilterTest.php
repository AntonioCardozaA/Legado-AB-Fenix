<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisTendenciaMensualFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_lavadora_trend_analysis_filters_visible_rows_by_date_range(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'linea' => 'L-04',
            'nombre' => 'Guia inferior',
            'codigo' => 'GUI_INF_TANQUE_FEATURE_RANGE',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Lavadora',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-01-15');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-02-10');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-03-05');
        $this->crearAnalisis($linea, $componente, 'Desgaste severo', '2026-04-10');

        $this->actingAs($user)
            ->get(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4', [
                'linea_id' => $linea->id,
                'fecha_inicio' => '2026-02-01',
                'fecha_fin' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertSee('value="2026-02-01"', false)
            ->assertSee('value="2026-03-31"', false)
            ->assertSee('Febrero 2026')
            ->assertSee('Marzo 2026')
            ->assertDontSee('Enero 2026')
            ->assertDontSee('Abril 2026');
    }

    public function test_lavadora_trend_analysis_rejects_inverted_date_ranges(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->from(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4', ['linea_id' => $linea->id]))
            ->get(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4', [
                'linea_id' => $linea->id,
                'fecha_inicio' => '2026-04-01',
                'fecha_fin' => '2026-03-31',
            ]))
            ->assertRedirect(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4', ['linea_id' => $linea->id]))
            ->assertSessionHasErrors('fecha_fin');
    }

    private function crearAnalisis(
        Linea $linea,
        Componente $componente,
        string $estado,
        string $fecha
    ): void {
        static $orden = 1;

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => $fecha,
            'numero_orden' => 'OT-FEATURE-RANGE-' . str_pad((string) $orden++, 3, '0', STR_PAD_LEFT),
            'estado' => $estado,
            'actividad' => 'Registro de prueba',
        ]);
    }
}
