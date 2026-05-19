<?php

namespace Tests\Feature;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasteurizadoraAnalysisCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_component_can_start_a_new_cycle_from_zero(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        foreach (AnalisisPasteurizadora::NIVELES as $nivel) {
            foreach (AnalisisPasteurizadora::LADOS as $lado) {
                $this->crearAnalisis($linea, [
                    'modulo' => 1,
                    'componente' => 'ANILLAS',
                    'nivel' => $nivel,
                    'lado' => $lado,
                    'numero_orden' => sprintf('OT-%s-%s', $nivel, $lado),
                    'componentes_revisados' => [1, 2, 3],
                    'cantidad_componentes_revisados' => 3,
                    'total_componentes' => 3,
                ]);
            }
        }

        $resumen = AnalisisPasteurizadora::getResumenCicloComponente($linea->id, 1, 'ANILLAS');

        $this->assertTrue($resumen['tiene_ciclo_completado']);
        $this->assertFalse($resumen['tiene_ciclo_activo']);
        $this->assertSame(3, AnalisisPasteurizadora::getCantidadComponentesPendientes($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $this->assertSame(0, AnalisisPasteurizadora::getCantidadComponentesRevisados($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => 'OT-NUEVO-001',
                'estado' => 'Buen estado',
                'actividad' => 'Inicio de un nuevo ciclo de revision',
                'componentes_revisados' => [1],
            ]
        );

        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => 'ANILLAS',
            'nivel' => 'SUPERIOR',
            'lado' => 'VAPOR',
            'numero_orden' => 'OT-NUEVO-001',
            'usuario_id' => $user->id,
        ]);

        $this->assertSame(2, AnalisisPasteurizadora::getCantidadComponentesPendientes($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $this->assertSame(1, AnalisisPasteurizadora::getCantidadComponentesRevisados($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));

        $analisis = AnalisisPasteurizadora::with('usuario')
            ->where('numero_orden', 'OT-NUEVO-001')
            ->firstOrFail();

        $this->assertTrue($analisis->usuario->is($user));
    }

    private function crearAnalisis(Linea $linea, array $overrides = []): AnalisisPasteurizadora
    {
        return AnalisisPasteurizadora::create(array_merge([
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-TEST',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de prueba',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => null,
            'resuelto_por_cambio' => false,
        ], $overrides));
    }
}
