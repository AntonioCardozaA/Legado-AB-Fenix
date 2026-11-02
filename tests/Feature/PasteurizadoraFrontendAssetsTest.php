<?php

namespace Tests\Feature;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PasteurizadoraFrontendAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_keeps_both_area_contracts_and_permission_guarded_routes(): void
    {
        Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true, 'tipo' => 'pasteurizadora']);
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        foreach ([true, false] as $allowPlans) {
            if (!$allowPlans) {
                foreach ([User::customAccessControlPermissionName(), 'ver planes accion'] as $permission) {
                    Permission::findOrCreate($permission, 'web');
                    $user->givePermissionTo($permission);
                }
            }
            $this->assertSame($allowPlans, $user->canViewPlanActionType(User::MODULE_PASTEURIZADORA));
            foreach ([AnalisisPasteurizadora::AREA_MECANICA, AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA] as $area) {
                $mechanical = $area === AnalisisPasteurizadora::AREA_MECANICA;
                $response = $this->actingAs($user)->get(route('dashboard.global.pasteurizadoras', ['parte' => $area]))->assertOk();
                preg_match('/<script type="application\/json" id="pasteurDashboardData">(.*?)<\/script>/s', $response->getContent(), $match);
                $this->assertArrayHasKey(1, $match);
                $config = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                $raw = $response->original->getData();
                $expected = [
                    'pasteurizadorasData' => $mechanical ? collect($raw['estadoPasteurizadoras'] ?? [])->values() : [],
                    'fallasPorLineaPasteurizadora' => $mechanical ? collect($raw['fallasPorLineaPasteurizadora'] ?? [])->values() : [],
                    'componentesDanadosPasteurizadora' => $mechanical ? collect($raw['componentesDanadosPasteurizadora'] ?? [])->values() : [],
                    'planesAccionDashboardPasteurizadora' => $mechanical ? ($raw['planesAccionDashboardPasteurizadora'] ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []]) : ['por_linea' => []],
                    'avanceRevisionPasteurizadora' => $mechanical ? ($raw['avanceRevisionPasteurizadora'] ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []]) : ['labels' => [], 'porcentajes' => []],
                    'analisis52124Pasteurizadora' => $mechanical ? ($raw['analisis52124Pasteurizadora'] ?? ['lineas' => [], 'criterios' => []]) : ['lineas' => []],
                    'analisis30147Pasteurizadora' => $mechanical ? ($raw['analisis30147Pasteurizadora'] ?? ['lineas' => [], 'criterios' => []]) : ['lineas' => []],
                    'centralesHidraulicasData' => !$mechanical ? collect($raw['estadoCentralHidraulicaPasteurizadora'] ?? [])->values() : [],
                    'fallasPorLineaCentralHidraulica' => !$mechanical ? collect($raw['fallasPorLineaCentralHidraulica'] ?? [])->values() : [],
                    'planesAccionDashboardCentralHidraulica' => !$mechanical ? ($raw['planesAccionDashboardCentralHidraulica'] ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []]) : ['por_linea' => []],
                    'avanceRevisionCentralHidraulica' => !$mechanical ? ($raw['avanceRevisionCentralHidraulica'] ?? ['labels' => [], 'porcentajes' => []]) : ['labels' => [], 'porcentajes' => []],
                    'analisis52124CentralHidraulica' => !$mechanical ? ($raw['analisis52124CentralHidraulica'] ?? ['lineas' => []]) : ['lineas' => []],
                    'analisis30147CentralHidraulica' => !$mechanical ? ($raw['analisis30147CentralHidraulica'] ?? ['lineas' => []]) : ['lineas' => []],
                ];
                foreach ($expected as $key => $value) $this->assertSame(json_encode($value), json_encode($config[$key]), $key);
                $this->assertSame($mechanical ? 'fallasPasteurizadoraChart' : '', $config['fallasCanvasId']);
                $this->assertSame($allowPlans ? route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') : null, $config['urls']['mechanicalPlans']);
                $this->assertSame(!$mechanical && $allowPlans ? route('plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA, 'area_pasteurizadora' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) : null, $config['urls']['centralPlans']);
                $this->assertSame(!$mechanical ? route('pasteurizadora.central-hidraulica.index') : null, $config['urls']['centralAnalyses']);
                $response->assertSee('dashboard-pasteurizadora-', false);
            }
        }
    }
}
