<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendActivityNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'elongacion-alerts.timezone' => 'America/Mexico_City',
        ]);
    }

    public function test_command_supports_date_simulation_and_dry_run_mode(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $user = User::factory()->create();

        UserNotificationSetting::create([
            'user_id' => $user->id,
            'days_before_notification' => 3,
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar componente danado',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-06-12',
            'completado' => false,
        ]);

        $this->artisan('notifications:send-activities', [
            '--date' => '2026-06-10 09:00:00',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Fecha de referencia: 2026-06-10 09:00:00')
            ->expectsOutputToContain('Modo simulacion activo')
            ->expectsOutputToContain('Cambiar componente danado')
            ->assertSuccessful();

        $this->assertSame(0, $user->notifications()->count());
    }
}
