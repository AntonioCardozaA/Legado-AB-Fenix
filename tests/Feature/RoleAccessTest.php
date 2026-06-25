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

    public function test_restricted_roles_cannot_use_indirect_pasteurizadora_urls(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
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

        $this->actingAs($user)
            ->get(route('reportes.index', ['tipo' => 'pasteurizadoras']))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('plan-accion.show', $plan))
            ->assertForbidden();
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
