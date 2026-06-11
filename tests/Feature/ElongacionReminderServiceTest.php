<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\ElongacionReminderNotification;
use App\Models\Linea;
use App\Models\User;
use App\Services\ElongacionReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElongacionReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'elongacion-alerts.timezone' => 'America/Mexico_City',
            'elongacion-alerts.interval_months' => 2,
            'elongacion-alerts.lead_days' => 3,
            'elongacion-alerts.whatsapp_recipients' => ['5550000001'],
            'services.ultramsg.instance' => 'instance-test',
            'services.ultramsg.token' => 'token-test',
            'services.ultramsg.url' => 'https://api.ultramsg.com',
            'services.ultramsg.default_country_code' => '521',
        ]);
    }

    public function test_it_sends_a_single_grouped_whatsapp_message_for_upcoming_and_overdue_lines(): void
    {
        Http::fake([
            'https://api.ultramsg.com/*' => Http::response(['sent' => 'true', 'id' => 'msg-1'], 200),
        ]);

        $this->crearLinea('L-04');
        $this->crearLinea('L-05');
        $this->crearLinea('L-06');

        $this->crearElongacion('L-04', '2026-03-30 18:00:00');
        $this->crearElongacion('L-05', '2026-03-24 18:00:00');
        $this->crearElongacion('L-06', '2026-04-20 18:00:00');

        $service = app(ElongacionReminderService::class);
        $results = $service->sendPendingAlerts(
            CarbonImmutable::parse('2026-05-27 09:00:00', 'America/Mexico_City')
        );

        $this->assertSame(2, $results['pending_lines']);
        $this->assertSame(1, $results['recipients']);
        $this->assertSame(1, $results['sent']);
        $this->assertCount(0, $results['failed']);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            parse_str($request->body(), $payload);

            $this->assertSame('5215550000001', $payload['to']);
            $this->assertStringContainsString('L-04', $payload['body']);
            $this->assertStringContainsString('L-05', $payload['body']);
            $this->assertStringNotContainsString('L-06', $payload['body']);
            $this->assertStringContainsString('Ultimo registro: 30/03/2026', $payload['body']);
            $this->assertStringContainsString('Ultimo registro: 24/03/2026', $payload['body']);
            $this->assertStringContainsString('Estado: faltan 3 dias', $payload['body']);
            $this->assertStringContainsString('Estado: vencida por 3 dias', $payload['body']);

            return true;
        });

        $this->assertTrue(
            ElongacionReminderNotification::query()
                ->whereDate('notification_date', '2026-05-27')
                ->where('recipient', '5215550000001')
                ->where('status', 'sent')
                ->exists()
        );
    }

    public function test_it_does_not_send_duplicate_alerts_on_the_same_day(): void
    {
        Http::fake([
            'https://api.ultramsg.com/*' => Http::response(['sent' => 'true', 'id' => 'msg-2'], 200),
        ]);

        $this->crearLinea('L-04');
        $this->crearElongacion('L-04', '2026-03-30 18:00:00');

        $service = app(ElongacionReminderService::class);
        $referenceTime = CarbonImmutable::parse('2026-05-27 09:00:00', 'America/Mexico_City');

        $firstRun = $service->sendPendingAlerts($referenceTime);
        $secondRun = $service->sendPendingAlerts($referenceTime->addHour());

        $this->assertSame(1, $firstRun['sent']);
        $this->assertSame(0, $secondRun['sent']);
        $this->assertSame(1, $secondRun['skipped']);

        Http::assertSentCount(1);
    }

    public function test_it_retries_failed_notifications_later_the_same_day(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts <= 3) {
                return Http::response(['error' => 'temporary'], 500);
            }

            return Http::response(['sent' => 'true', 'id' => 'msg-3'], 200);
        });

        $this->crearLinea('L-04');
        $this->crearElongacion('L-04', '2026-03-30 18:00:00');

        $service = app(ElongacionReminderService::class);
        $referenceTime = CarbonImmutable::parse('2026-05-27 09:00:00', 'America/Mexico_City');

        $firstRun = $service->sendPendingAlerts($referenceTime);
        $secondRun = $service->sendPendingAlerts($referenceTime->addHour());

        $this->assertCount(1, $firstRun['failed']);
        $this->assertSame(1, $secondRun['sent']);

        Http::assertSentCount(4);
        $this->assertTrue(
            ElongacionReminderNotification::query()
                ->whereDate('notification_date', '2026-05-27')
                ->where('recipient', '5215550000001')
                ->where('status', 'sent')
                ->exists()
        );
    }

    public function test_it_reports_configured_recipients_even_when_there_are_no_pending_lines(): void
    {
        $this->crearLinea('L-08');
        $this->crearElongacion('L-08', '2026-05-20 18:33:22');

        $service = app(ElongacionReminderService::class);
        $results = $service->sendPendingAlerts(
            CarbonImmutable::parse('2026-05-21 09:00:00', 'America/Mexico_City')
        );

        $this->assertSame(0, $results['pending_lines']);
        $this->assertSame(1, $results['recipients']);
        $this->assertSame(0, $results['sent']);
        $this->assertSame([], $results['alerts']);
    }

    public function test_it_can_run_in_dry_run_mode_without_sending_messages(): void
    {
        Http::fake();

        $this->crearLinea('L-04');
        $this->crearElongacion('L-04', '2026-03-30 18:00:00');

        $service = app(ElongacionReminderService::class);
        $results = $service->sendPendingAlerts(
            CarbonImmutable::parse('2026-05-27 09:00:00', 'America/Mexico_City'),
            true
        );

        $this->assertTrue($results['dry_run']);
        $this->assertSame(1, $results['simulated']);
        $this->assertSame(0, $results['sent']);
        $this->assertCount(1, $results['recipient_targets']);
        Http::assertNothingSent();
    }

    public function test_it_creates_a_single_internal_notification_on_the_alert_start_date(): void
    {
        $user = User::factory()->create();

        $this->crearLinea('L-04');
        $this->crearElongacion('L-04', '2026-03-30 18:00:00');

        $service = app(ElongacionReminderService::class);
        $referenceTime = CarbonImmutable::parse('2026-05-27 09:00:00', 'America/Mexico_City');

        $firstRun = $service->sendInternalNotifications($referenceTime);
        $secondRun = $service->sendInternalNotifications($referenceTime->addHour());

        $this->assertSame(1, $firstRun['sent']);
        $this->assertSame(1, $firstRun['pending_lines']);
        $this->assertSame(0, $secondRun['sent']);
        $this->assertSame(1, $secondRun['skipped']);

        $user->refresh();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(1, $user->unreadNotifications()->count());

        $notification = $user->notifications()->firstOrFail();

        $this->assertSame('elongacion_reminder', $notification->data['type']);
        $this->assertSame(['L-04'], $notification->data['lineas']);
    }

    private function crearLinea(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
    }

    private function crearElongacion(string $linea, string $createdAt): Elongacion
    {
        $lineaModel = Linea::where('nombre', $linea)->firstOrFail();
        $pasoInicial = Elongacion::getPasoInicial($linea);

        $ciclo = CadenaCiclo::create([
            'linea_id' => $lineaModel->id,
            'linea' => $linea,
            'codigo' => sprintf('%s-C001', $linea),
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor test',
            'paso_inicial' => $pasoInicial,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subDays(30),
            'activa' => true,
        ]);

        $elongacion = Elongacion::create([
            'linea_id' => $lineaModel->id,
            'linea' => $linea,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => 'Proveedor test',
            'seccion' => 'LAVADORA',
            'bombas_promedio' => $pasoInicial,
            'bombas_porcentaje' => 0,
            'vapor_promedio' => $pasoInicial,
            'vapor_porcentaje' => 0,
            'requiere_cambio' => false,
            'estado' => 'normal',
            'estado_detallado' => 'normal',
            'paso_inicial' => $pasoInicial,
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ]);

        $elongacion->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $elongacion;
    }
}
