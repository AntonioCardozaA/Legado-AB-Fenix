<?php

namespace Tests\Feature;

use App\Models\AiInteractionLog;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiObservabilityDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_ai_observability_dashboard_with_operational_metrics(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $technician = $this->userWithRole(User::ROLE_TECNICO, [
            'name' => 'Tecnico Chat',
        ]);

        AiInteractionLog::query()->create([
            'user_id' => $technician->id,
            'action_type' => 'assistant_chat',
            'provider' => 'fake',
            'model' => 'observability-test-model',
            'status' => 'success',
            'response_time_ms' => 1200,
            'input_chars' => 800,
            'output_chars' => 220,
            'prompt_tokens' => 100,
            'completion_tokens' => 40,
            'total_tokens' => 140,
            'metadata' => [
                'question_excerpt' => 'Motor hace ruido en la lavadora L-99',
                'knowledge_count' => 2,
                'platform_query_matches' => 1,
                'page_context' => [
                    'module' => User::MODULE_LAVADORA,
                ],
            ],
        ]);

        AiInteractionLog::query()->create([
            'action_type' => 'washer_action_plan_generation',
            'provider' => 'fake',
            'model' => 'observability-test-model',
            'status' => 'failed',
            'response_time_ms' => 16000,
            'input_chars' => 1200,
            'output_chars' => 0,
            'metadata' => [
                'knowledge_sources_count' => 0,
            ],
            'error_message' => 'Proveedor IA no disponible',
        ]);

        $this->createAiPlan($admin);

        $this->actingAs($admin)
            ->get(route('admin.ai-observability.index'))
            ->assertOk()
            ->assertSee('Observabilidad IA')
            ->assertSee('Chatbot operativo')
            ->assertSee('Plan IA lavadora')
            ->assertSee('fake')
            ->assertSee('Motor hace ruido en la lavadora L-99')
            ->assertSee('Proveedor IA no disponible')
            ->assertSee('1,234.50');
    }

    public function test_user_without_ai_observability_permission_cannot_open_dashboard(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);

        $this->actingAs($technician)
            ->get(route('admin.ai-observability.index'))
            ->assertForbidden();
    }

    public function test_user_with_custom_ai_observability_permission_can_open_dashboard(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($technician, [User::PERMISSION_VIEW_AI_OBSERVABILITY]);

        $this->actingAs($technician)
            ->get(route('admin.ai-observability.index'))
            ->assertOk()
            ->assertSee('Observabilidad IA');
    }

    private function createAiPlan(User $reviewer): PlanAccion
    {
        $linea = Linea::query()->create([
            'nombre' => 'L-99',
            'descripcion' => 'Linea de prueba IA',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        return PlanAccion::query()->create([
            'linea_id' => $linea->id,
            'actividad' => 'Ajustar motor sugerido por IA',
            'source' => 'ai',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'estado' => 'approved',
            'completado' => true,
            'generated_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
            'confidence_level' => 0.91,
            'actual_cost_total' => 1234.50,
            'actual_hours' => 2.5,
            'effectiveness' => PlanAccion::EFFECTIVENESS_EFFECTIVE,
            'execution_result' => 'Se corrigio ruido y quedo estable.',
            'fecha_pcm1' => now()->addDay()->toDateString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(array_merge([
            'activo' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function enableCustomPermissions(User $user, array $permissions): void
    {
        foreach ([User::customAccessControlPermissionName(), ...$permissions] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo([User::customAccessControlPermissionName(), ...$permissions]);
    }
}
