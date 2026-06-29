<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_manager_does_not_see_or_open_pasteurizadora_module(): void
    {
        $user = $this->userWithRole(User::ROLE_GERENTE_MANTENIMIENTO);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pasteurizadoras')
            ->assertSee('data-coming-soon-message');

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_supervisor_keeps_admin_level_access_outside_pasteurizadora(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);

        $this->actingAs($user)
            ->get(route('lineas.index'))
            ->assertOk();
    }

    public function test_restricted_roles_can_view_pasteurizadora_action_plans_without_opening_restricted_modules(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Actividad de pasteurizadora',
            'tipo_equipo' => 'pasteurizadora',
        ]);

        foreach ([User::ROLE_GERENTE_MANTENIMIENTO, User::ROLE_TECNICO, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('reportes.index', ['tipo' => 'pasteurizadoras']))
                ->assertRedirect(route('dashboard'));

            $this->actingAs($user)
                ->get(route('pasteurizadora.dashboard'))
                ->assertRedirect(route('dashboard'));

            $this->actingAs($user)
                ->get(route('plan-accion.index', [
                    'tipo' => 'pasteurizadora',
                    'linea_id' => $linea->id,
                ]))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('plan-accion.show', $plan))
                ->assertOk()
                ->assertJsonPath('id', $plan->id);

            $this->actingAs($user)
                ->get(route('plan-accion.edit', [
                    'plan_accion' => $plan->id,
                    'tipo' => 'pasteurizadora',
                ]))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_all_plan_action_roles_can_view_legacy_pasteurizadora_plans_without_saved_type(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-04',
            'descripcion' => 'Pasteurizadora sin tipo guardado',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Actividad legado pasteurizadora',
            'tipo_equipo' => null,
        ]);

        foreach ([
            User::ROLE_ADMIN,
            User::ROLE_GERENTE_MANTENIMIENTO,
            User::ROLE_SUPERVISOR,
            User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO,
            User::ROLE_TECNICO,
            User::ROLE_INGENIERO_MANTENIMIENTO,
        ] as $role) {
            $user = $this->userWithRole($role);

            $response = $this->actingAs($user)
                ->get(route('plan-accion.index', [
                    'tipo' => 'pasteurizadora',
                    'linea_id' => $linea->id,
                ]))
                ->assertOk();

            $this->assertTrue(
                $response->viewData('planes')->getCollection()->contains('id', $plan->id),
                "The {$role} role should see legacy pasteurizadora action plans."
            );

            $this->actingAs($user)
                ->get(route('plan-accion.show', $plan))
                ->assertOk()
                ->assertJsonPath('id', $plan->id);
        }
    }

    public function test_admin_keeps_pasteurizadora_access(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertOk();
    }

    public function test_programador_de_mantenimiento_matches_supervisor_permissions_and_ui(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisorRole = Role::where('name', User::ROLE_SUPERVISOR)->firstOrFail();
        $programadorRole = Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)->firstOrFail();
        $user = $this->userWithRole(User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO);

        $this->assertEqualsCanonicalizing(
            $supervisorRole->permissions->pluck('name')->all(),
            $programadorRole->permissions->pluck('name')->all()
        );

        $this->assertFalse($user->usesTechnicianAccessProfile());
        $this->assertTrue($user->canEditAnalysisDate());
        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canAccessModule(User::MODULE_PASTEURIZADORA));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lavadoras')
            ->assertSee('Pasteurizadoras')
            ->assertSee('href="' . route('reportes.index') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('lineas.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_ingeniero_de_mantenimiento_matches_tecnico_permissions_and_access(): void
    {
        $this->seed(RoleSeeder::class);

        $technicianRole = Role::where('name', User::ROLE_TECNICO)->firstOrFail();
        $engineerRole = Role::where('name', User::ROLE_INGENIERO_MANTENIMIENTO)->firstOrFail();
        $user = $this->userWithRole(User::ROLE_INGENIERO_MANTENIMIENTO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->assertEqualsCanonicalizing(
            $technicianRole->permissions->pluck('name')->all(),
            $engineerRole->permissions->pluck('name')->all()
        );

        $this->assertTrue($user->usesTechnicianAccessProfile());
        $this->assertTrue($user->canEditAnalysisDate());
        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canAccessModule(User::MODULE_PASTEURIZADORA));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tecnico.dashboard'));

        $this->actingAs($user)
            ->get(route('tecnico.dashboard'))
            ->assertOk()
            ->assertSee($message, false)
            ->assertDontSee('href="' . route('reportes.index') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('tecnico.dashboard'))
            ->assertSessionHas('acceso_restringido', $message);

        $this->actingAs($user)
            ->get(route('lineas.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_shared_technician_dashboard_displays_authenticated_role_label(): void
    {
        foreach ([User::ROLE_TECNICO, User::ROLE_INGENIERO_MANTENIMIENTO] as $role) {
            $user = $this->userWithRole($role);
            $expectedRoleLabel = User::roleLabels()[$role];

            $response = $this->actingAs($user)
                ->get(route('tecnico.dashboard'))
                ->assertOk()
                ->assertSee('Dashboard ' . $expectedRoleLabel, false);

            if ($role === User::ROLE_INGENIERO_MANTENIMIENTO) {
                $response->assertDontSee('Dashboard ' . User::roleLabels()[User::ROLE_TECNICO], false);
            }
        }
    }

    public function test_technician_profile_roles_can_use_lavadora_trend_analysis_modules(): void
    {
        Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        foreach ([User::ROLE_TECNICO, User::ROLE_INGENIERO_MANTENIMIENTO] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.lavadora.analisis-30-14-7'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4'))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_technician_cannot_open_reportes_from_menu_or_direct_url(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->actingAs($user)
            ->get(route('tecnico.dashboard'))
            ->assertOk()
            ->assertSee($message, false)
            ->assertDontSee('href="' . route('reportes.index') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('tecnico.dashboard'))
            ->assertSessionHas('acceso_restringido', $message);
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
}
