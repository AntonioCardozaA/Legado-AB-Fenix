<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_screen(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $employee = $this->userWithRole(User::ROLE_TECNICO, [
            'name' => 'Tecnico Planta',
            'email' => 'tecnico.planta@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Gestion de usuarios')
            ->assertSee($employee->email);
    }

    public function test_non_admin_cannot_open_user_management_screen(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);

        $this->actingAs($technician)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_user_and_assign_role(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->ensureRoleExists(User::ROLE_TECNICO);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nuevo Tecnico',
                'email' => 'nuevo.tecnico@example.com',
                'cedula' => '99887766',
                'telefono' => '5551234567',
                'puesto' => 'Tecnico Operativo',
                'role' => User::ROLE_TECNICO,
                'activo' => '1',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $createdUser = User::where('email', 'nuevo.tecnico@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole(User::ROLE_TECNICO));
        $this->assertSame('Tecnico Operativo', $createdUser->puesto);
        $this->assertTrue((bool) $createdUser->activo);
    }

    public function test_admin_can_update_existing_user_role_and_status(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $employee = $this->userWithRole(User::ROLE_TECNICO, [
            'email' => 'operativo@example.com',
            'puesto' => 'Tecnico',
        ]);
        $this->ensureRoleExists(User::ROLE_SUPERVISOR);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $employee), [
                'name' => 'Operativo Actualizado',
                'email' => 'operativo@example.com',
                'cedula' => '111222333',
                'telefono' => '5550001234',
                'puesto' => 'Supervisor de Turno',
                'role' => User::ROLE_SUPERVISOR,
                'activo' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $employee->refresh();

        $this->assertSame('Operativo Actualizado', $employee->name);
        $this->assertSame('Supervisor de Turno', $employee->puesto);
        $this->assertFalse((bool) $employee->activo);
        $this->assertTrue($employee->hasRole(User::ROLE_SUPERVISOR));
    }

    public function test_admin_cannot_remove_own_admin_access(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, [
            'activo' => true,
        ]);
        $this->ensureRoleExists(User::ROLE_TECNICO);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => 'Administrador',
                'email' => $admin->email,
                'cedula' => '',
                'telefono' => '',
                'puesto' => 'Administrador',
                'role' => User::ROLE_TECNICO,
                'activo' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('self_protection');

        $admin->refresh();

        $this->assertTrue((bool) $admin->activo);
        $this->assertTrue($admin->hasRole(User::ROLE_ADMIN));
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $this->ensureRoleExists($role);

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    private function ensureRoleExists(string $role): void
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);
    }
}
