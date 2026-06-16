<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
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

    public function test_it_creates_internal_notifications_for_upcoming_action_plans_without_duplicates(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        UserNotificationSetting::create([
            'user_id' => $targetUser->id,
            'days_before_notification' => 3,
        ]);

        UserNotificationSetting::create([
            'user_id' => $otherUser->id,
            'days_before_notification' => 3,
            'notify_only_my_lines' => true,
            'lines_to_notify' => [999],
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar componente danado',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-06-12',
            'completado' => false,
        ]);

        $closedPlan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Plan ya finalizado',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-06-12',
        ]);
        $closedPlan->forceFill(['completado' => true])->saveQuietly();

        $service = app(NotificationService::class);
        $referenceTime = CarbonImmutable::parse('2026-06-10 09:00:00', 'America/Mexico_City');

        $firstRun = $service->verificarYNotificarActividadesProximas($referenceTime);
        $secondRun = $service->verificarYNotificarActividadesProximas($referenceTime->addHour());

        $this->assertSame(1, $firstRun['sent']);
        $this->assertSame(1, count($firstRun['alerts']));
        $this->assertSame(0, $secondRun['sent']);
        $this->assertSame(1, $secondRun['skipped']);

        $targetUser->refresh();
        $otherUser->refresh();

        $this->assertSame(1, $targetUser->notifications()->count());
        $this->assertSame(0, $otherUser->notifications()->count());

        $notification = $targetUser->notifications()->firstOrFail();

        $this->assertSame('plan_accion_due', $notification->data['type']);
        $this->assertSame('L-04', $notification->data['linea']);
        $this->assertSame('PCM1', $notification->data['pcm']);
    }

    public function test_pasteurizadora_action_plan_notifications_include_area(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'tipo' => 'pasteurizadora',
            'activo' => true,
        ]);

        $targetUser = User::factory()->create();

        UserNotificationSetting::create([
            'user_id' => $targetUser->id,
            'days_before_notification' => 3,
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Revisar valvulas',
            'tipo_equipo' => 'pasteurizadora',
            'area_pasteurizadora' => 'central_hidraulica',
            'fecha_pcm1' => '2026-06-12',
            'completado' => false,
        ]);

        $service = app(NotificationService::class);
        $referenceTime = CarbonImmutable::parse('2026-06-10 09:00:00', 'America/Mexico_City');

        $result = $service->verificarYNotificarActividadesProximas($referenceTime);

        $this->assertSame('central_hidraulica', $result['alerts'][0]['area_pasteurizadora']);
        $this->assertSame('Hidraulica', $result['alerts'][0]['area_pasteurizadora_label']);

        $notification = $targetUser->notifications()->firstOrFail();

        $this->assertSame('central_hidraulica', $notification->data['area_pasteurizadora']);
        $this->assertSame('Hidraulica', $notification->data['area_pasteurizadora_label']);
        $this->assertStringContainsString('Parte: Hidraulica', $notification->data['message']);
    }
}
