<?php

namespace Tests\Feature;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
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
            ->assertDontSee('Pasteurizadoras')
            ->assertDontSee('Pasteurizadora');

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

    public function test_technician_cannot_open_reportes_from_menu_or_direct_url(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canAccessModule(User::MODULE_PASTEURIZADORA));

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
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_programador_de_mantenimiento_uses_the_technician_lavadora_only_profile(): void
    {
        $user = $this->userWithRole(User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->assertTrue($user->usesTechnicianAccessProfile());
        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canAccessModule(User::MODULE_PASTEURIZADORA));
        $this->assertFalse($user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tecnico.dashboard'));

        $this->actingAs($user)
            ->get(route('lavadora.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('tecnico.dashboard'))
            ->assertOk()
            ->assertSee($message, false)
            ->assertDontSee('href="' . route('reportes.index') . '"', false)
            ->assertDontSee('href="' . route('pasteurizadora.dashboard') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('tecnico.dashboard'))
            ->assertSessionHas('acceso_restringido', $message);

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('pasteurizadora.analisis-pasteurizadora.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('lineas.index'))
            ->assertForbidden();
    }

    public function test_programador_de_mantenimiento_permissions_match_technician_permissions(): void
    {
        $technicianRole = Role::where('name', User::ROLE_TECNICO)->firstOrFail();
        $programadorRole = Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)->firstOrFail();

        $technicianPermissions = $technicianRole->permissions->pluck('name')->all();
        $programadorPermissions = $programadorRole->permissions->pluck('name')->all();

        $this->assertEqualsCanonicalizing($technicianPermissions, $programadorPermissions);
        $this->assertContains(User::PERMISSION_ACCESS_LAVADORA, $programadorPermissions);
        $this->assertNotContains(User::PERMISSION_ACCESS_PASTEURIZADORA, $programadorPermissions);
        $this->assertNotContains(User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA, $programadorPermissions);
        $this->assertNotContains(User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA, $programadorPermissions);
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
