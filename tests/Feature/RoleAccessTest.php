<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\AnalisisPasteurizadora;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\NotificationRecipientService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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

    public function test_restricted_roles_can_view_pasteurizadora_action_plans_without_opening_restricted_modules(): void
    {
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

        foreach ([User::ROLE_GERENTE_MANTENIMIENTO, User::ROLE_TECNICO, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('reportes.index', ['tipo' => 'pasteurizadoras']))
                ->assertRedirect(route('dashboard'));

            $this->actingAs($user)
                ->get(route('pasteurizadora.dashboard'))
                ->assertRedirect(route('dashboard'));

            $this->actingAs($user)
                ->get(route('plan-accion.index', [
                    'tipo' => 'pasteurizadora',
                    'linea_id' => $linea->id,
                ]))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('plan-accion.show', $plan))
                ->assertOk()
                ->assertJsonPath('id', $plan->id);

            $this->actingAs($user)
                ->get(route('plan-accion.edit', [
                    'plan_accion' => $plan->id,
                    'tipo' => 'pasteurizadora',
                ]))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_all_plan_action_roles_can_view_legacy_pasteurizadora_plans_without_saved_type(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-04',
            'descripcion' => 'Pasteurizadora sin tipo guardado',
            'activo' => true,
        ]);
        $plan = PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Actividad legado pasteurizadora',
            'tipo_equipo' => null,
        ]);

        foreach ([
            User::ROLE_ADMIN,
            User::ROLE_GERENTE_MANTENIMIENTO,
            User::ROLE_SUPERVISOR,
            User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO,
            User::ROLE_TECNICO,
            User::ROLE_INGENIERO_MANTENIMIENTO,
        ] as $role) {
            $user = $this->userWithRole($role);

            $response = $this->actingAs($user)
                ->get(route('plan-accion.index', [
                    'tipo' => 'pasteurizadora',
                    'linea_id' => $linea->id,
                ]))
                ->assertOk();

            $this->assertTrue(
                $response->viewData('planes')->getCollection()->contains('id', $plan->id),
                "The {$role} role should see legacy pasteurizadora action plans."
            );

            $this->actingAs($user)
                ->get(route('plan-accion.show', $plan))
                ->assertOk()
                ->assertJsonPath('id', $plan->id);
        }
    }

    public function test_admin_keeps_pasteurizadora_access(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertOk();
    }

    public function test_capturista_excentricos_uses_exclusive_pasteurizadora_flow(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $user = $this->userWithRole(User::ROLE_CAPTURISTA_EXCENTRICOS);

        $this->assertTrue($user->usesPasteurizadoraExcentricosAccessProfile());
        $this->assertTrue($user->canAccessModule(User::MODULE_PASTEURIZADORA));
        $this->assertTrue($user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA));
        $this->assertFalse($user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA));
        $this->assertFalse($user->canUseCustomPermission('gestionar usuarios'));

        $exclusiveRoute = route('pasteurizadora.analisis-pasteurizadora.excentricos.index');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get($exclusiveRoute)
            ->assertOk()
            ->assertSee('Excentricos de Pasteurizadoras')
            ->assertSee($linea->nombre)
            ->assertSee(asset('images/Diagramas-Pasteurizadoras/linea3.png'), false)
            ->assertSee('TRABAJA CON NORMALIDAD')
            ->assertDontSee('Pendiente')
            ->assertSee('Modulo 1')
            ->assertSee('Capturar revision')
            ->assertDontSee('Todas las pasteurizadoras')
            ->assertSee(route('pasteurizadora.analisis-pasteurizadora.create-quick', [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'componente' => AnalisisPasteurizadora::COMPONENTE_EXCENTRICOS,
            ]))
            ->assertDontSee(route('admin.users.index'), false);

        $this->actingAs($user)
            ->get(route('pasteurizadora.analisis-pasteurizadora.index'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get(route('assistant-chat.index'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get(route('lavadoras.diagramas.l05-l12-l13'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get(route('elongaciones.index'))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->get(route('pasteurizadora.analisis-pasteurizadora.create-quick', [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'componente' => 'ANILLAS',
            ]))
            ->assertRedirect($exclusiveRoute);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');
    }

    public function test_capturista_excentricos_can_store_only_excentricos_without_order_number(): void
    {
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $user = $this->userWithRole(User::ROLE_CAPTURISTA_EXCENTRICOS);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => AnalisisPasteurizadora::COMPONENTE_EXCENTRICOS,
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => '1234ABCD',
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Revision de excentricos por capturista',
                'componentes_revisados' => json_encode([1]),
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.excentricos.index', [
            'linea_id' => $linea->id,
        ]));

        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => AnalisisPasteurizadora::COMPONENTE_EXCENTRICOS,
            'numero_orden' => null,
            'usuario_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('pasteurizadora.analisis-pasteurizadora.excentricos.index', [
                'linea_id' => $linea->id,
            ]))
            ->assertOk()
            ->assertSee('Indicador')
            ->assertSee(AnalisisPasteurizadora::ESTADO_BUENO)
            ->assertSee('Ver detalles')
            ->assertSee('Detalle del Analisis')
            ->assertSee('Piezas revisadas')
            ->assertSee('REVISION DE EXCENTRICOS POR CAPTURISTA');

        $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Intento de registrar otro componente',
                'componentes_revisados' => json_encode([1]),
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'componente' => 'ANILLAS',
            'actividad' => 'INTENTO DE REGISTRAR OTRO COMPONENTE',
        ]);
    }

    public function test_capturista_excentricos_role_is_available_in_user_management(): void
    {
        $this->seed(RoleSeeder::class);

        $role = Role::where('name', User::ROLE_CAPTURISTA_EXCENTRICOS)->firstOrFail();

        $this->assertTrue($role->hasPermissionTo(User::PERMISSION_CAPTURE_PASTEURIZADORA_EXCENTRICOS));

        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Capturista de Excentricos')
            ->assertSee('Captura Excentricos');
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

    public function test_ingeniero_de_mantenimiento_matches_tecnico_permissions_and_access(): void
    {
        $this->seed(RoleSeeder::class);

        $technicianRole = Role::where('name', User::ROLE_TECNICO)->firstOrFail();
        $engineerRole = Role::where('name', User::ROLE_INGENIERO_MANTENIMIENTO)->firstOrFail();
        $user = $this->userWithRole(User::ROLE_INGENIERO_MANTENIMIENTO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->assertEqualsCanonicalizing(
            $technicianRole->permissions->pluck('name')->all(),
            $engineerRole->permissions->pluck('name')->all()
        );

        $this->assertTrue($user->usesTechnicianAccessProfile());
        $this->assertTrue($user->canEditAnalysisDate());
        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canAccessModule(User::MODULE_PASTEURIZADORA));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tecnico.dashboard'));

        $this->actingAs($user)
            ->get(route('tecnico.dashboard'))
            ->assertOk()
            ->assertDontSee($message, false)
            ->assertDontSee('href="' . route('reportes.index') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('tecnico.dashboard'))
            ->assertSessionHas('acceso_restringido', $message);

        $this->actingAs($user)
            ->get(route('lineas.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('pasteurizadora.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_shared_technician_dashboard_displays_authenticated_role_label(): void
    {
        foreach ([User::ROLE_TECNICO, User::ROLE_INGENIERO_MANTENIMIENTO] as $role) {
            $user = $this->userWithRole($role);
            $expectedRoleLabel = User::roleLabels()[$role];

            $response = $this->actingAs($user)
                ->get(route('tecnico.dashboard'))
                ->assertOk()
                ->assertSee('Dashboard ' . $expectedRoleLabel, false);

            if ($role === User::ROLE_INGENIERO_MANTENIMIENTO) {
                $response->assertDontSee('Dashboard ' . User::roleLabels()[User::ROLE_TECNICO], false);
            }
        }
    }

    public function test_technician_profile_roles_can_use_lavadora_trend_analysis_modules(): void
    {
        Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        foreach ([User::ROLE_TECNICO, User::ROLE_INGENIERO_MANTENIMIENTO] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.lavadora.analisis-52-12-4'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.lavadora.analisis-30-14-7'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4'))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_technician_cannot_open_reportes_from_menu_or_direct_url(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $message = 'No cuentas con los permisos necesarios para visualizar los reportes.';

        $this->actingAs($user)
            ->get(route('tecnico.dashboard'))
            ->assertOk()
            ->assertDontSee($message, false)
            ->assertDontSee('href="' . route('reportes.index') . '"', false);

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('tecnico.dashboard'))
            ->assertSessionHas('acceso_restringido', $message);
    }

    public function test_custom_permissions_hide_only_the_restricted_main_dashboard_module(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($user, [
            User::PERMISSION_ACCESS_ETIQUETADORA,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lavadoras')
            ->assertDontSee('Etiquetadoras')
            ->assertSee('Pasteurizadoras');
    }

    public function test_custom_permissions_hide_only_restricted_lavadora_menu_cards(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($user, [
            'ver elongaciones',
            'ver historico revisados',
            'ver planes accion',
            'ver tendencias lavadora',
        ]);

        $this->actingAs($user)
            ->get(route('lavadora.dashboard'))
            ->assertOk()
            ->assertSee(route('analisis-lavadora.index'), false)
            ->assertDontSee(route('elongaciones.index'), false)
            ->assertDontSee(route('historico-revisados.index'), false)
            ->assertDontSee(route('plan-accion.index'), false)
            ->assertDontSee(route('analisis-tendencia-mensual.lavadora.index'), false);
    }

    public function test_custom_permission_restriction_blocks_direct_module_access_without_showing_error_code(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($user, [
            User::PERMISSION_ACCESS_ETIQUETADORA,
        ]);

        $this->actingAs($user)
            ->get(route('etiquetadora.dashboard'))
            ->assertForbidden()
            ->assertSee('Acceso restringido')
            ->assertDontSee('Error 403');
    }

    public function test_custom_permissions_hide_and_block_restricted_admin_module_access(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->enableCustomPermissions($admin, [
            User::PERMISSION_ACCESS_ETIQUETADORA,
        ]);

        $this->assertFalse($admin->fresh()->canAccessModule(User::MODULE_ETIQUETADORA));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Etiquetadoras')
            ->assertDontSee(route('etiquetadora.dashboard'), false);

        $this->actingAs($admin)
            ->get(route('etiquetadora.dashboard'))
            ->assertForbidden();
    }

    public function test_custom_permissions_hide_restricted_admin_sidebar_links(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->enableCustomPermissions($admin, [
            'gestionar usuarios',
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.costos.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.costos.index'))
            ->assertForbidden();
    }

    public function test_custom_permissions_filter_internal_notifications_by_module(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($user, ['ver planes accion']);

        $service = app(NotificationRecipientService::class);

        $planRecipients = $service->getInternalRecipients('plan_accion_due');
        $elongacionRecipients = $service->getInternalRecipients('elongacion_reminder');

        $this->assertFalse($planRecipients->contains(fn (array $recipient): bool => $recipient['user']->is($user)));
        $this->assertTrue($elongacionRecipients->contains(fn (array $recipient): bool => $recipient['user']->is($user)));
    }

    public function test_custom_permissions_can_disable_all_internal_notifications(): void
    {
        $user = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($user, ['ver notificaciones']);

        $recipients = app(NotificationRecipientService::class)->getInternalRecipients();

        $this->assertFalse($recipients->contains(fn (array $recipient): bool => $recipient['user']->is($user)));
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

    private function enableCustomPermissions(User $user, array $permissions): void
    {
        foreach ([User::customAccessControlPermissionName(), ...$permissions] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo([User::customAccessControlPermissionName(), ...$permissions]);
    }
}
