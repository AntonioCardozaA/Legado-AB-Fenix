<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardLavadoraFrontendAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_panels_keep_the_same_payload_and_permission_guarded_plan_url(): void
    {
        Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        foreach ([true, false] as $allowed) {
            if (!$allowed) {
                foreach ([User::customAccessControlPermissionName(), 'ver planes accion'] as $permission) {
                    Permission::findOrCreate($permission, 'web');
                    $user->givePermissionTo($permission);
                }
            }
            $this->assertSame($allowed, $user->canViewPlanActionType(User::MODULE_LAVADORA));
            $response = $this->actingAs($user)->get(route('dashboard.global.lavadoras'))->assertOk();
            preg_match('/<script type="application\/json" id="lavadoraPanelsData">(.*?)<\/script>/s', $response->getContent(), $match);
            $this->assertArrayHasKey(1, $match);
            $config = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            $lines = $response->viewData('lineasLavadora')->map(fn ($line) => ['id' => $line->id, 'nombre' => $line->nombre])->values();
            $this->assertSame(json_encode($lines), json_encode($config['lineas']));
            foreach (['fallas' => 'fallasPorLinea', 'planes' => 'planesAccionDashboard', 'ranking' => 'rankingDanos', 'elongaciones' => 'evolucionElongaciones', 'historico' => 'historicoRevisiones'] as $key => $viewKey) {
                $this->assertSame(json_encode($response->viewData($viewKey)), json_encode($config[$key]), $key);
            }
            $this->assertSame($allowed ? route('plan-accion.lavadora.index') : null, $config['planesUrl']);
            $response->assertSee('dashboard-lavadora-panels-', false);
        }
    }
}
