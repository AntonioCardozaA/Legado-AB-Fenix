<?php

namespace Tests\Feature;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPasteurizadoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_dashboard_omits_quick_actions_and_counts_only_active_mechanical_failures(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $lineaActiva = $this->crearLineaPasteurizadora('P-03');
        $lineaResuelta = $this->crearLineaPasteurizadora('P-04');

        $vigente = $this->crearAnalisis($lineaActiva, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Cambio vigente de anillas',
            'numero_orden' => 'OT-ACTIVA',
        ]);

        $this->crearAnalisis($lineaResuelta, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Cambio ya resuelto',
            'numero_orden' => 'OT-RESUELTA',
            'resuelto_por_cambio' => true,
        ]);

        $this->crearAnalisis($lineaActiva, [
            'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Daño de central hidraulica',
            'numero_orden' => 'OT-CENTRAL',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.global.pasteurizadoras', [
            'trend_52124_desde' => '2026-07-01',
            'trend_52124_hasta' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee(route('pasteurizadora.analisis-pasteurizadora.index'), false);
        $response->assertSee(route('pasteurizadora.analisis-pasteurizadora.plan-accion.index'), false);
        $response->assertDontSee('quick-actions-grid', false);
        $response->assertDontSee(route('pasteurizadora.central-hidraulica.index'), false);
        $response->assertDontSee(route('pasteurizadora.central-hidraulica.historico-revisados'), false);
        $response->assertSee('trend_52124_desde', false);
        $response->assertSee('trend_30147_desde', false);

        $fallas = collect($response->viewData('fallasPorLineaPasteurizadora'))->keyBy('linea');
        $componentes = collect($response->viewData('componentesDanadosPasteurizadora'));
        $planes = collect($response->viewData('planesPendientesPasteurizadora'));

        $this->assertSame(1, $fallas->get('P-03')['total_fallas']);
        $this->assertSame(0, $fallas->get('P-04')['total_fallas']);
        $this->assertSame(1, $componentes->sum('total_danios'));
        $this->assertTrue($planes->contains(fn (AnalisisPasteurizadora $analisis) => $analisis->is($vigente)));
        $this->assertCount(1, $planes);
    }

    public function test_global_dashboard_renders_central_hidraulica_as_independent_view(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = $this->crearLineaPasteurizadora('P-03');
        $config = $this->configuracionCentral('P-03', CentralHidraulicaConfiguracion::PISO_SUPERIOR, 'ELECTROVALVULAS');

        $this->crearAnalisis($linea, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Dano mecanico que no debe renderizarse en la vista hidraulica',
            'numero_orden' => 'OT-MECANICA',
        ]);

        AnalisisCentralHidraulica::create([
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'componente_id' => $config->componente_id,
            'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
            'lado' => AnalisisCentralHidraulica::LADO_1,
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-HIDRAULICA',
            'estado' => AnalisisCentralHidraulica::ESTADO_DANADO,
            'actividad' => 'Fuga en central hidraulica',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => $config->cantidad,
            'resuelto_por_cambio' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.global.pasteurizadoras', [
            'parte' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
        ]));

        $response->assertOk();
        $response->assertViewHas('dashboardPasteurizadoraParte', AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA);
        $response->assertSee('Central Hidraulica', false);
        $response->assertSee('fallasCentralHidraulicaChart', false);
        $response->assertSee(route('pasteurizadora.central-hidraulica.index'), false);
        $response->assertDontSee('fallasPasteurizadoraChart', false);

        $fallasCentral = collect($response->viewData('fallasPorLineaCentralHidraulica'))->keyBy('linea');
        $resumenCentral = $response->viewData('resumenCentralHidraulicaPasteurizadora');
        $tendenciaCentral = $response->viewData('analisis52124CentralHidraulica');

        $this->assertSame(1, $fallasCentral->get('P-03')['total_fallas']);
        $this->assertSame(1, $resumenCentral['alertas_criticas']);
        $this->assertSame(1, data_get($tendenciaCentral, 'global.total_fallas'));
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function crearLineaPasteurizadora(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
    }

    private function crearAnalisis(Linea $linea, array $overrides = []): AnalisisPasteurizadora
    {
        return AnalisisPasteurizadora::create(array_merge([
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-DASH',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de dashboard',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => null,
            'resuelto_por_cambio' => false,
        ], $overrides));
    }

    private function configuracionCentral(string $pasteurizador, string $piso, string $codigo): CentralHidraulicaConfiguracion
    {
        $componente = CentralHidraulicaComponente::where('codigo', $codigo)->firstOrFail();

        return CentralHidraulicaConfiguracion::where('pasteurizador', $pasteurizador)
            ->where('piso', $piso)
            ->where('componente_id', $componente->id)
            ->firstOrFail();
    }
}
