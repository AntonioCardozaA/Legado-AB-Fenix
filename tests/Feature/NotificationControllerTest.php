<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'message' => 'Notificacion de prueba',
                ];
            }
        });

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'message' => 'Notificacion de prueba',
                ];
            }
        };

        $user->notify($notification);
        $user->notify($notification);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_manager_feed_hides_analysis_audit_notifications_only(): void
    {
        $user = $this->userWithRole(User::ROLE_GERENTE_MANTENIMIENTO);

        $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_type' => 'analisis_lavadora',
            'message' => 'Registro de analisis',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'admin_analysis_deleted',
            'message' => 'Analisis eliminado',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_type' => 'reporte',
            'message' => 'Reporte generado',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'component_alert',
            'estado' => 'Desgaste severo',
            'message' => 'Alerta de componente',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->json();

        $this->assertSame(2, $response['count']);
        $this->assertSame(2, $response['notifications_count']);
        $this->assertEqualsCanonicalizing(
            ['Reporte generado', 'Alerta de componente'],
            collect($response['items'])->pluck('message')->all()
        );
    }

    public function test_technician_feed_only_shows_operational_notification_types(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);

        $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_type' => 'analisis_lavadora',
            'message' => 'Registro de analisis',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'admin_analysis_deleted',
            'message' => 'Analisis eliminado',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_type' => 'reporte',
            'message' => 'Reporte generado',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'component_alert',
            'estado' => 'Desgaste moderado',
            'message' => 'Alerta de componente',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'plan_accion_due',
            'message' => 'Plan de accion',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'elongacion_reminder',
            'message' => 'Elongacion',
        ]);
        $this->notifyDatabase($user, [
            'type' => 'historico_revisados_alert',
            'message' => 'Historico revisados',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->json();

        $this->assertSame(4, $response['count']);
        $this->assertEqualsCanonicalizing(
            ['Alerta de componente', 'Plan de accion', 'Elongacion', 'Historico revisados'],
            collect($response['items'])->pluck('message')->all()
        );
    }

    public function test_hidden_notification_cannot_be_marked_as_read(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $hiddenNotificationId = $this->notifyDatabase($user, [
            'type' => 'admin_analysis_deleted',
            'message' => 'Analisis eliminado',
        ]);
        $visibleNotificationId = $this->notifyDatabase($user, [
            'type' => 'plan_accion_due',
            'message' => 'Plan de accion',
        ]);

        $this->actingAs($user)
            ->postJson(route('notifications.read', $hiddenNotificationId))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->notifications()->whereKey($hiddenNotificationId)->firstOrFail()->read_at);
        $this->assertNotNull($user->notifications()->whereKey($visibleNotificationId)->firstOrFail()->read_at);
    }

    public function test_notification_bar_hides_deleted_action_plan_targets(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $plan = PlanAccion::withoutEvents(fn () => PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Plan eliminado',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-07-02',
            'completado' => false,
        ]));

        $this->notifyDatabase($user, [
            'type' => 'plan_accion_due',
            'plan_id' => $plan->id,
            'message' => 'Plan eliminado',
        ]);

        PlanAccion::withoutEvents(fn () => $plan->delete());

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->json();

        $this->assertSame(0, $response['count']);
        $this->assertSame(0, $response['notifications_count']);
        $this->assertSame([], $response['items']);
    }

    public function test_notification_bar_hides_deleted_analysis_targets(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'L-05',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo prueba',
            'codigo' => 'L05_DELETED_SERVO',
            'linea' => 'L-05',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
        $analisis = AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-06-24',
            'numero_orden' => 'OT-DELETED-001',
            'estado' => 'Desgaste moderado',
            'actividad' => 'Analisis eliminado',
            'usuario_id' => $user->id,
        ]));

        $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_class' => AnalisisLavadora::class,
            'record_id' => $analisis->id,
            'message' => 'Analisis eliminado',
        ]);

        AnalisisLavadora::withoutEvents(fn () => $analisis->delete());

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->json();

        $this->assertSame(0, $response['count']);
        $this->assertSame(0, $response['notifications_count']);
        $this->assertSame([], $response['items']);
    }

    public function test_target_role_open_redirects_to_exact_lavadora_analysis(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo chico',
            'codigo' => 'L04_OPEN_SERVO',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
        $analisis = AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-06-24',
            'numero_orden' => 'OT-OPEN-001',
            'estado' => 'Desgaste moderado',
            'actividad' => 'Revision para apertura',
            'usuario_id' => $user->id,
        ]));

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'component_alert',
            'record_class' => AnalisisLavadora::class,
            'record_id' => $analisis->id,
            'url' => route('notifications.index'),
            'message' => 'Alerta de componente',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('analisis-lavadora.index', [
                'linea_id' => $linea->id,
                'open_analysis_id' => $analisis->id,
            ], false));

        $this->assertNotNull($user->notifications()->whereKey($notificationId)->firstOrFail()->read_at);
    }

    public function test_admin_open_keeps_original_analysis_detail_url(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo chico',
            'codigo' => 'L04_ADMIN_OPEN_SERVO',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
        $analisis = AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-06-24',
            'numero_orden' => 'OT-ADMIN-001',
            'estado' => 'Desgaste moderado',
            'actividad' => 'Revision para detalle admin',
            'usuario_id' => $user->id,
        ]));
        $detailUrl = route('analisis-lavadora.show', ['analisislavadora' => $analisis->id]);

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_class' => AnalisisLavadora::class,
            'record_id' => $analisis->id,
            'url' => $detailUrl,
            'message' => 'Nuevo analisis',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('analisis-lavadora.show', ['analisislavadora' => $analisis->id], false));
    }

    public function test_target_role_open_keeps_action_plan_notification_inside_current_app(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar rodamiento',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-07-02',
            'completado' => false,
        ]);
        $storedAbsoluteUrl = 'http://localhost/plan-accion/' . $plan->id . '/edit?tipo=lavadora';

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'plan_accion_due',
            'plan_id' => $plan->id,
            'url' => $storedAbsoluteUrl,
            'message' => 'Plan de accion por vencer',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('plan-accion.index', [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
                'open_plan_id' => $plan->id,
            ], false));
    }

    public function test_target_role_open_can_view_pasteurizadora_action_plan_notification(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'tipo' => 'pasteurizadora',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Revisar valvulas',
            'tipo_equipo' => 'pasteurizadora',
            'area_pasteurizadora' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'fecha_pcm1' => '2026-07-02',
            'completado' => false,
        ]);

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'plan_accion_due',
            'plan_id' => $plan->id,
            'tipo_equipo' => 'pasteurizadora',
            'linea_id' => $linea->id,
            'message' => 'Plan de accion de pasteurizadora',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('plan-accion.index', [
                'tipo' => 'pasteurizadora',
                'linea_id' => $linea->id,
                'open_plan_id' => $plan->id,
            ], false));
    }

    public function test_action_plan_notification_resolves_legacy_pasteurizadora_type_from_line_name(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-04',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Revisar bombas',
            'tipo_equipo' => null,
            'fecha_pcm1' => '2026-07-02',
            'completado' => false,
        ]);

        foreach ([User::ROLE_ADMIN, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->userWithRole($role);

            $notificationId = $this->notifyDatabase($user, [
                'type' => 'plan_accion_due',
                'plan_id' => $plan->id,
                'message' => 'Plan de accion legado',
            ]);

            $this->actingAs($user)
                ->get(route('notifications.open', $notificationId))
                ->assertRedirect(route('plan-accion.index', [
                    'tipo' => 'pasteurizadora',
                    'linea_id' => $linea->id,
                    'open_plan_id' => $plan->id,
                ], false));
        }
    }

    public function test_admin_open_action_plan_notification_uses_index_modal_like_other_roles(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'L-08',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Revisar servo',
            'tipo_equipo' => 'lavadora',
            'fecha_pcm1' => '2026-07-02',
            'completado' => false,
        ]);

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'admin_record_created',
            'record_type' => 'plan_accion',
            'record_class' => PlanAccion::class,
            'record_id' => $plan->id,
            'url' => route('plan-accion.edit', [
                'plan_accion' => $plan->id,
                'tipo' => 'lavadora',
            ]),
            'message' => 'Plan de accion creado',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('plan-accion.index', [
                'tipo' => 'lavadora',
                'linea_id' => $linea->id,
                'open_plan_id' => $plan->id,
            ], false));
    }

    public function test_target_role_open_shows_message_when_analysis_is_not_authorized(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'tipo' => 'pasteurizadora',
            'activo' => true,
        ]);
        $analisis = AnalisisPasteurizadora::withoutEvents(fn () => AnalisisPasteurizadora::create([
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-06-24',
            'numero_orden' => '123456',
            'estado' => 'Desgaste severo',
            'actividad' => 'Revision no autorizada',
            'usuario_id' => $user->id,
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'resuelto_por_cambio' => false,
        ]));

        $notificationId = $this->notifyDatabase($user, [
            'type' => 'component_alert',
            'record_class' => AnalisisPasteurizadora::class,
            'record_id' => $analisis->id,
            'message' => 'Alerta de pasteurizadora',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notificationId))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('notification_warning', 'No cuentas con autorizacion para visualizar este contenido.');

        $this->assertNotNull($user->notifications()->whereKey($notificationId)->firstOrFail()->read_at);
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyDatabase(User $user, array $data): string
    {
        $existingNotificationIds = $user->notifications()->pluck('id')->all();

        $user->notify(new class($data) extends Notification
        {
            /**
             * @param  array<string, mixed>  $data
             */
            public function __construct(private readonly array $data)
            {
            }

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(object $notifiable): array
            {
                return $this->data;
            }
        });

        return (string) $user->notifications()
            ->whereNotIn('id', $existingNotificationIds)
            ->firstOrFail()
            ->id;
    }
}
