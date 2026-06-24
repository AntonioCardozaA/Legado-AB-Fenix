<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\User;
use App\Services\AdminRecordNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRecordNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_notification_when_user_creates_lavadora_analysis(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $technician = $this->userWithRole(User::ROLE_TECNICO, [
            'name' => 'Tecnico Capturista',
        ]);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $this->actingAs($technician)
            ->post(route('analisis-lavadora.store'), [
                'linea_id' => $linea->id,
                'componente_codigo' => 'SERVO_CHICO',
                'reductor' => 'Reductor 1',
                'fecha_analisis' => '2026-06-24',
                'numero_orden' => 'OT-NOTIF-001',
                'estado' => AnalisisLavadora::ESTADO_BUENO,
                'actividad' => 'Registro para notificar administrador',
            ])
            ->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $analisis = AnalisisLavadora::where('numero_orden', 'OT-NOTIF-001')->firstOrFail();
        $notification = $admin->fresh()->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);
        $this->assertSame('admin_record_created', $notification->data['type']);
        $this->assertSame('Analisis de lavadora', $notification->data['record_label']);
        $this->assertSame($technician->name, $notification->data['actor_name']);
        $this->assertSame($linea->nombre, $notification->data['linea']);
        $this->assertSame(
            route('analisis-lavadora.show', ['analisislavadora' => $analisis->id]),
            $notification->data['url']
        );
        $this->assertArrayHasKey('created_at_display', $notification->data);
    }

    public function test_admin_receives_notification_when_report_is_generated(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $user = $this->userWithRole(User::ROLE_INGENIERO_MANTENIMIENTO, [
            'name' => 'Ingeniero Reportes',
        ]);
        $linea = Linea::create([
            'nombre' => 'L-05',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        app(AdminRecordNotificationService::class)->notifyReportGenerated(
            $user,
            'lavadoras',
            $linea,
            CarbonImmutable::parse('2026-06-01'),
            CarbonImmutable::parse('2026-06-24'),
            'pdf',
            route('reportes.index', ['linea_id' => $linea->id])
        );

        $notification = $admin->fresh()->notifications()->firstOrFail();

        $this->assertSame('admin_record_created', $notification->data['type']);
        $this->assertSame('reporte', $notification->data['record_type']);
        $this->assertSame('Reporte generado', $notification->data['record_label']);
        $this->assertSame($user->name, $notification->data['actor_name']);
        $this->assertSame($linea->nombre, $notification->data['linea']);
        $this->assertSame(route('reportes.index', ['linea_id' => $linea->id]), $notification->data['url']);
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
}
