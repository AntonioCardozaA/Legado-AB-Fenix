<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\AnalysisDeletionLog;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalysisDeletionPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_analysis_permission_is_not_assigned_to_supervisor_roles_by_default(): void
    {
        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('name', User::ROLE_ADMIN)->firstOrFail();
        $supervisorRole = Role::where('name', User::ROLE_SUPERVISOR)->firstOrFail();
        $programadorRole = Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)->firstOrFail();

        $this->assertTrue($adminRole->hasPermissionTo(User::PERMISSION_DELETE_ANALYSIS));
        $this->assertFalse($supervisorRole->hasPermissionTo(User::PERMISSION_DELETE_ANALYSIS));
        $this->assertFalse($programadorRole->hasPermissionTo(User::PERMISSION_DELETE_ANALYSIS));
    }

    public function test_admin_can_assign_and_revoke_delete_analysis_permission_to_supervisor_user(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $supervisor = $this->userWithRole(User::ROLE_SUPERVISOR, [
            'email' => 'supervisor.permiso@example.com',
            'puesto' => 'Supervisor',
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', ['user' => $supervisor]), [
                'name' => 'Supervisor con Permiso',
                'email' => $supervisor->email,
                'cedula' => '',
                'telefono' => '',
                'puesto' => 'Supervisor',
                'role' => User::ROLE_SUPERVISOR,
                'activo' => '1',
                'can_delete_analysis' => '1',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.edit', ['user' => $supervisor]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($supervisor->refresh()->hasDirectAnalysisDeletionPermission());

        $this->actingAs($admin)
            ->put(route('admin.users.update', ['user' => $supervisor]), [
                'name' => 'Supervisor sin Permiso',
                'email' => $supervisor->email,
                'cedula' => '',
                'telefono' => '',
                'puesto' => 'Supervisor',
                'role' => User::ROLE_SUPERVISOR,
                'activo' => '1',
                'can_delete_analysis' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($supervisor->refresh()->hasDirectAnalysisDeletionPermission());
    }

    public function test_supervisor_without_special_permission_cannot_delete_lavadora_analysis_or_see_delete_action(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = $this->userWithRole(User::ROLE_SUPERVISOR);
        $analisis = $this->crearAnalisisLavadora();

        $this->actingAs($supervisor)
            ->get(route('analisis-lavadora.show', ['analisislavadora' => $analisis->id]))
            ->assertOk()
            ->assertDontSee('delete-analysis-button');

        $this->actingAs($supervisor)
            ->delete(route('analisis-lavadora.destroy', ['analisislavadora' => $analisis->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('analisis_componentes', [
            'id' => $analisis->id,
        ]);
        $this->assertSame(0, AnalysisDeletionLog::count());
    }

    public function test_supervisor_with_direct_special_permission_can_delete_lavadora_analysis_and_audit_is_recorded(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = $this->userWithRole(User::ROLE_SUPERVISOR);
        $supervisor->givePermissionTo(Permission::firstOrCreate([
            'name' => User::PERMISSION_DELETE_ANALYSIS,
            'guard_name' => 'web',
        ]));

        $analisis = $this->crearAnalisisLavadora([
            'numero_orden' => 'OT-DELETE-001',
        ]);

        $this->actingAs($supervisor)
            ->delete(route('analisis-lavadora.destroy', ['analisislavadora' => $analisis->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('analisis_componentes', [
            'id' => $analisis->id,
        ]);

        $this->assertDatabaseHas('analysis_deletion_logs', [
            'user_id' => $supervisor->id,
            'analysis_type' => 'lavadora',
            'analysis_table' => 'analisis_componentes',
            'deleted_record_id' => $analisis->id,
            'linea_nombre' => 'L-04',
            'tipo_analisis' => 'Analisis Lavadora',
        ]);
    }

    public function test_admin_receives_notification_when_supervisor_deletes_lavadora_analysis(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = $this->userWithRole(User::ROLE_SUPERVISOR, [
            'name' => 'Supervisor Alertas',
        ]);
        $supervisor->givePermissionTo(Permission::firstOrCreate([
            'name' => User::PERMISSION_DELETE_ANALYSIS,
            'guard_name' => 'web',
        ]));

        $analisis = $this->crearAnalisisLavadora([
            'numero_orden' => 'OT-DELETE-NOTIFY',
        ]);
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($supervisor)
            ->delete(route('analisis-lavadora.destroy', ['analisislavadora' => $analisis->id]))
            ->assertRedirect();

        $notification = $admin->fresh()->notifications()->firstOrFail();

        $this->assertSame('admin_analysis_deleted', $notification->data['type']);
        $this->assertSame('Analisis Lavadora', $notification->data['record_label']);
        $this->assertSame($analisis->id, $notification->data['record_id']);
        $this->assertSame($supervisor->name, $notification->data['actor_name']);
        $this->assertSame('L-04', $notification->data['linea']);
        $this->assertSame('alta', $notification->data['prioridad']);
        $this->assertSame(
            route('analisis-lavadora.index', ['linea_id' => $analisis->linea_id]),
            $notification->data['url']
        );
        $this->assertStringContainsString('elimino Analisis Lavadora', $notification->data['message']);
        $this->assertArrayHasKey('deleted_at_display', $notification->data);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    private function crearAnalisisLavadora(array $overrides = []): AnalisisLavadora
    {
        $linea = Linea::create([
            'nombre' => $overrides['linea_nombre'] ?? 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $componente = Componente::create([
            'codigo' => $overrides['componente_codigo'] ?? 'SERVO_CHICO',
            'nombre' => 'Servo Chico',
            'linea' => $linea->nombre,
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        return AnalisisLavadora::create(array_merge([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-LAV-DELETE',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Registro para probar eliminacion',
        ], $overrides));
    }
}
