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
