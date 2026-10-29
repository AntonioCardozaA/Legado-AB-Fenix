<?php

namespace Tests\Feature;

use App\Models\AlertRecipient;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Services\ConfiguredAlertRecipientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_update_a_phone_recipient(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('admin.alert-recipients.store'), [
                'name' => 'Supervisor turno A',
                'phone_number' => '+52 498 123 4567',
                'channel' => AlertRecipient::CHANNEL_WHATSAPP,
                'alert_types' => [AlertRecipient::TYPE_COMPONENT],
                'active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $recipient = AlertRecipient::firstOrFail();
        $this->assertSame(['component_alert'], $recipient->alert_types);

        $this->actingAs($admin)
            ->put(route('admin.alert-recipients.update', $recipient), [
                'name' => 'Supervisor general',
                'phone_number' => '+52 498 123 4567',
                'channel' => AlertRecipient::CHANNEL_WHATSAPP,
                'alert_types' => [AlertRecipient::TYPE_ALL],
                'active' => 0,
            ])
            ->assertRedirect();

        $recipient->refresh();
        $this->assertFalse($recipient->active);
        $this->assertSame(['all'], $recipient->alert_types);
    }

    public function test_only_selected_alert_types_are_sent_and_dispatch_is_deduplicated(): void
    {
        Http::fake(fn () => Http::response(['sent' => 'true'], 200));
        config([
            'services.ultramsg.instance' => 'instance-test',
            'services.ultramsg.token' => 'token-test',
            'services.ultramsg.url' => 'https://api.ultramsg.test',
            'services.ultramsg.default_country_code' => '52',
        ]);

        $componentRecipient = AlertRecipient::create([
            'name' => 'Componentes',
            'phone_number' => '4981234567',
            'channel' => AlertRecipient::CHANNEL_WHATSAPP,
            'alert_types' => [AlertRecipient::TYPE_COMPONENT],
            'active' => true,
        ]);
        AlertRecipient::create([
            'name' => 'Todas',
            'phone_number' => '4987654321',
            'channel' => AlertRecipient::CHANNEL_WHATSAPP,
            'alert_types' => [AlertRecipient::TYPE_ALL],
            'active' => true,
        ]);

        $service = app(ConfiguredAlertRecipientService::class);
        $first = $service->dispatch('component_alert', 'Alerta de prueba', 'component:1');
        $second = $service->dispatch('component_alert', 'Alerta de prueba', 'component:1');
        $plan = $service->dispatch('plan_accion_due', 'Plan de prueba', 'plan:1');

        $this->assertSame(2, $first['sent']);
        $this->assertSame(2, $first['recipients']);
        $this->assertSame(2, $second['skipped']);
        $this->assertSame(1, $plan['sent']);
        $this->assertDatabaseCount('notification_dispatch_logs', 3);
        Http::assertSentCount(3);
        $this->assertTrue($componentRecipient->fresh()->active);
    }

    public function test_non_admin_cannot_manage_global_alert_recipients(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);

        $this->actingAs($user)
            ->get(route('admin.alert-recipients.index'))
            ->assertForbidden();
    }

    public function test_component_alerts_are_sent_for_all_attention_states(): void
    {
        Http::fake(fn () => Http::response(['sent' => 'true'], 200));
        config([
            'services.ultramsg.instance' => 'instance-test',
            'services.ultramsg.token' => 'token-test',
            'services.ultramsg.url' => 'https://api.ultramsg.test',
        ]);

        $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Línea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo de prueba',
            'codigo' => 'L04_ALERTA_TEST',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
        AlertRecipient::create([
            'name' => 'Alertas de componentes',
            'phone_number' => '4981234567',
            'channel' => AlertRecipient::CHANNEL_WHATSAPP,
            'alert_types' => [AlertRecipient::TYPE_COMPONENT],
            'active' => true,
        ]);

        foreach ([
            AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'Desgaste moderado',
            'Desgaste severo',
            AnalisisLavadora::ESTADO_DANADO,
        ] as $index => $estado) {
            AnalisisLavadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $componente->id,
                'reductor' => 'Reductor 1',
                'fecha_analisis' => '2026-10-29',
                'numero_orden' => 'ALERTA-' . $index,
                'estado' => $estado,
                'actividad' => 'Prueba de alerta',
            ]);
        }

        $this->assertSame(4, Http::recorded()->count());
        $this->assertSame(
            4,
            $this->app['db']->table('notifications')->where('data', 'like', '%component_alert%')->count()
        );
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
