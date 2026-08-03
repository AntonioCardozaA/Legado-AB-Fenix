<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanAccionTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_records_registered_user_and_responsible_user(): void
    {
        $registrador = $this->adminUser();
        $responsable = User::factory()->create();
        $linea = $this->lavadoraLinea();

        $this->actingAs($registrador)
            ->post(route('plan-accion.store'), [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
                'actividad' => 'Revisar cadena principal',
                'responsable_id' => $responsable->id,
                'fecha_pcm1' => '2026-07-01',
            ])
            ->assertRedirect(route('plan-accion.index', [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
            ]));

        $plan = PlanAccion::firstOrFail();

        $this->assertSame($registrador->id, $plan->registrado_por_id);
        $this->assertSame($responsable->id, $plan->responsable_id);
        $this->assertNull($plan->ejecutado_por_id);
        $this->assertNull($plan->fecha_ejecucion);
    }

    public function test_checklist_records_execution_user_and_date(): void
    {
        $registrador = $this->adminUser();
        $ejecutor = $this->adminUser();
        $linea = $this->lavadoraLinea();

        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar componente danado',
            'tipo_equipo' => 'lavadora',
            'registrado_por_id' => $registrador->id,
            'responsable_id' => $registrador->id,
            'fecha_pcm1' => '2026-07-01',
            'completado' => false,
        ]);

        $this->actingAs($ejecutor)
            ->postJson("/plan-accion/{$plan->id}/checklist")
            ->assertOk()
            ->assertJsonPath('completado', true)
            ->assertJsonPath('ejecutado_por.id', $ejecutor->id);

        $plan->refresh();

        $this->assertTrue($plan->completado);
        $this->assertSame($ejecutor->id, $plan->ejecutado_por_id);
        $this->assertNotNull($plan->fecha_ejecucion);
    }

    public function test_checklist_records_execution_feedback_for_ai_learning(): void
    {
        $registrador = $this->adminUser();
        $ejecutor = $this->adminUser();
        $linea = $this->lavadoraLinea();

        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Ajustar tension de cadena',
            'tipo_equipo' => 'lavadora',
            'registrado_por_id' => $registrador->id,
            'responsable_id' => $registrador->id,
            'fecha_pcm1' => '2026-07-01',
            'completado' => false,
            'estimated_cost_total' => 1000,
            'estimated_hours' => 2,
        ]);

        $this->actingAs($ejecutor)
            ->postJson("/plan-accion/{$plan->id}/checklist", [
                'actual_cost_total' => 1250.75,
                'actual_hours' => 2.5,
                'effectiveness' => PlanAccion::EFFECTIVENESS_EFFECTIVE,
                'execution_result' => 'Se ajusto tension, se valido operacion y no quedo vibracion.',
            ])
            ->assertOk()
            ->assertJsonPath('completado', true)
            ->assertJsonPath('actual_cost_total', 1250.75)
            ->assertJsonPath('actual_hours', 2.5)
            ->assertJsonPath('effectiveness', PlanAccion::EFFECTIVENESS_EFFECTIVE)
            ->assertJsonPath('effectiveness_label', 'Efectivo');

        $plan->refresh();

        $this->assertTrue($plan->completado);
        $this->assertSame($ejecutor->id, $plan->ejecutado_por_id);
        $this->assertSame(1250.75, $plan->actual_cost_total);
        $this->assertSame(2.5, $plan->actual_hours);
        $this->assertSame(PlanAccion::EFFECTIVENESS_EFFECTIVE, $plan->effectiveness);
        $this->assertStringContainsString('se valido operacion', $plan->execution_result);
        $this->assertSame('quick_complete', $plan->review_history[0]['action'] ?? null);

        $this->actingAs($ejecutor)
            ->getJson(route('plan-accion.show', ['plan_accion' => $plan->id]))
            ->assertOk()
            ->assertJsonPath('execution_feedback.has_feedback', true)
            ->assertJsonPath('execution_feedback.effectiveness_label', 'Efectivo');
    }

    public function test_update_records_execution_feedback_from_edit_form(): void
    {
        $registrador = $this->adminUser();
        $ejecutor = $this->adminUser();
        $linea = $this->lavadoraLinea();

        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Revisar chumaceras',
            'tipo_equipo' => 'lavadora',
            'registrado_por_id' => $registrador->id,
            'responsable_id' => $registrador->id,
            'fecha_pcm1' => '2026-07-01',
            'completado' => false,
        ]);

        $this->actingAs($ejecutor)
            ->put(route('plan-accion.update', ['plan_accion' => $plan->id]), [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
                'actividad' => 'Revisar chumaceras y lubricar',
                'responsable_id' => $registrador->id,
                'fecha_pcm1' => '2026-07-02',
                'completado' => '1',
                'actual_cost_total' => '300.50',
                'actual_hours' => '1.25',
                'effectiveness' => PlanAccion::EFFECTIVENESS_PARTIALLY_EFFECTIVE,
                'execution_result' => 'Se lubrico, pero queda seguimiento por ruido leve.',
            ])
            ->assertRedirect(route('plan-accion.index', [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
            ]));

        $plan->refresh();

        $this->assertTrue($plan->completado);
        $this->assertSame($ejecutor->id, $plan->ejecutado_por_id);
        $this->assertSame(300.5, $plan->actual_cost_total);
        $this->assertSame(1.25, $plan->actual_hours);
        $this->assertSame(PlanAccion::EFFECTIVENESS_PARTIALLY_EFFECTIVE, $plan->effectiveness);
        $this->assertSame('manual_update', $plan->review_history[0]['action'] ?? null);
    }

    private function adminUser(): User
    {
        Role::firstOrCreate([
            'name' => User::ROLE_ADMIN,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function lavadoraLinea(): Linea
    {
        $linea = Linea::query()->find(4);

        if ($linea) {
            return $linea;
        }

        return Linea::forceCreate([
            'id' => 4,
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
    }
}
