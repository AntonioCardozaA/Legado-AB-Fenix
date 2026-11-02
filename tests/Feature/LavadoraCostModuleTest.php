<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\CostAutomationRule;
use App\Models\CostCatalogItem;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LavadoraCostModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_summary_preserves_displayed_totals_and_rankings_without_loading_module_details(): void
    {
        $linea = $this->createLavadoraLinea();
        foreach ([['Servo', 100.25, 'manual'], ['Guia', 220.50, 'estado_cambiado'], ['Guia', 10.00, 'manual']] as [$component, $total, $source]) {
            LavadoraCostEntry::create([
                'linea_id' => $linea->id, 'source_type' => $source, 'cost_date' => now()->toDateString(),
                'quantity' => 1, 'unit_cost' => $total, 'total_cost' => $total,
                'component_snapshot' => $component, 'catalog_name_snapshot' => 'Cost fixture', 'sync_key' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }
        $service = app(\App\Services\LavadoraCostAnalyticsService::class);
        foreach ([[], ['linea_id' => $linea->id]] as $filters) {
            $full = $service->dashboardData($filters);
            \Illuminate\Support\Facades\DB::flushQueryLog();
            \Illuminate\Support\Facades\DB::enableQueryLog();
            $summary = $service->globalSummaryData($filters);
            $queries = \Illuminate\Support\Facades\DB::getQueryLog();
            \Illuminate\Support\Facades\DB::disableQueryLog();
            $this->assertSame(\Illuminate\Support\Arr::only($full['summary'], ['year_total', 'top_component', 'top_replacement']), $summary['summary']);
            $this->assertSame($full['by_lavadora']->all(), $summary['by_lavadora']->all());
            $this->assertSame(330.75, $summary['summary']['year_total']);
            $this->assertArrayHasKey('correction_summary', $full);
            $this->assertArrayHasKey('history', $full);
            $this->assertLessThanOrEqual($filters === [] ? 3 : 4, count($queries));
        }
    }

    public function test_summary_projection_preserves_metadata_labels_ties_corrections_and_date_filters(): void
    {
        $line = $this->createLavadoraLinea();
        $other = Linea::firstOrCreate(['nombre' => 'L-05'], ['tipo' => User::MODULE_LAVADORA, 'activo' => true]);
        foreach (range(0, 23) as $index) {
            LavadoraCostEntry::create([
                'linea_id' => $index % 2 ? $line->id : $other->id,
                'source_type' => $index % 3 ? 'manual' : 'estado_cambiado',
                'cost_date' => sprintf('%d-%02d-15', $index < 12 ? now()->year : now()->year - 1, $index % 12 + 1),
                'quantity' => 1, 'unit_cost' => 12.35, 'total_cost' => $index % 5 ? 12.35 : -12.35,
                'component_snapshot' => $index % 2 ? '' : 'RV200', 'catalog_name_snapshot' => 'Projection fixture',
                'notas' => str_repeat('Full note must remain available. ', 100),
                'metadata' => ['component_code' => 'SERVO_CHICO', 'estado_correccion' => $index % 4 ? null : AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO],
                'sync_key' => 'projection-' . $index,
            ]);
        }
        $service = app(\App\Services\LavadoraCostAnalyticsService::class);
        foreach ([[], ['preset' => 'mensual'], ['preset' => 'custom', 'from' => now()->copy()->subYear()->toDateString(), 'to' => now()->toDateString()],
            ['linea_id' => $line->id], ['linea_id' => $other->id], ['preset' => 'custom', 'from' => '2000-01-01', 'to' => '2000-01-02']] as $filters) {
            $full = $service->dashboardData($filters);
            $summary = $service->globalSummaryData($filters);
            $this->assertSame(\Illuminate\Support\Arr::only($full['summary'], ['year_total', 'top_component', 'top_replacement']), $summary['summary']);
            $this->assertSame($full['by_lavadora']->all(), $summary['by_lavadora']->all());
        }
        $this->assertStringContainsString('Full note must remain available.', LavadoraCostEntry::firstOrFail()->notas);
    }

    public function test_changed_state_generates_automatic_cost_entry(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_TEST_COMPONENT', 'Componente ZZ');
        $catalogItem = $this->createCatalogItem('CAT-ZZ-001', 'Costo de prueba por cambio', 1250.50, 'Pieza');

        CostAutomationRule::query()->create([
            'cost_catalog_item_id' => $catalogItem->id,
            'component_code' => 'ZZ_TEST_COMPONENT',
            'trigger_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'quantity' => 1,
            'priority' => 1,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-06',
            'numero_orden' => '10000001',
            'estado' => AnalisisLavadora::ESTADO_CAMBIADO,
            'actividad' => 'REEMPLAZO TOTAL DEL COMPONENTE',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $analysis = AnalisisLavadora::query()->where('numero_orden', '10000001')->firstOrFail();

        $this->assertDatabaseHas('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
            'source_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'component_snapshot' => 'Componente ZZ',
            'total_cost' => 1250.50,
        ]);
    }

    public function test_activity_keyword_generates_cost_entry_and_extracts_quantity(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_ACTIVITY_COMPONENT', 'Componente Actividad');
        $catalogItem = $this->createCatalogItem('CAT-ZZ-ACEITE', 'Aceite de prueba', 300.00, 'Litro');

        CostAutomationRule::query()->create([
            'cost_catalog_item_id' => $catalogItem->id,
            'component_code' => 'ZZ_ACTIVITY_COMPONENT',
            'trigger_type' => CostAutomationRule::TRIGGER_ACTIVIDAD_KEYWORD,
            'trigger_keyword' => 'ACEITE',
            'quantity' => 1,
            'priority' => 1,
            'activo' => true,
        ]);

        $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-06',
            'numero_orden' => '10000002',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'CAMBIO DE ACEITE 2 LITROS EN REDUCTOR',
        ])->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $entry = \App\Models\LavadoraCostEntry::query()
            ->where('catalog_item_id', $catalogItem->id)
            ->firstOrFail();

        $this->assertSame(2.0, (float) $entry->quantity);
        $this->assertSame(600.0, (float) $entry->total_cost);
    }

    public function test_technician_can_open_lavadora_costs_dashboard(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($user, [User::PERMISSION_ACCESS_LAVADORA_COSTS]);

        $this->actingAs($user)
            ->get(route('lavadora.costos.index'))
            ->assertOk()
            ->assertSee('Costos de Lavadoras');
    }

    public function test_only_admin_can_access_control_gastos(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $technician = $this->userWithRole(User::ROLE_TECNICO);

        $this->actingAs($admin)
            ->get(route('admin.costos.index'))
            ->assertOk()
            ->assertSee('Control de Gastos');

        $this->actingAs($technician)
            ->get(route('admin.costos.index'))
            ->assertForbidden();
    }

    public function test_manual_cost_can_be_added_and_persist_after_analysis_update(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($user, [
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
            User::PERMISSION_CREATE_LAVADORA_COSTS,
        ]);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_MANUAL_COMPONENT', 'Componente Manual');
        $catalogItem = $this->createCatalogItem('CAT-ZZ-MAN-001', 'Gasto manual de prueba', 450.25, 'Pieza');

        $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-06',
            'numero_orden' => '10000003',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'INSPECCION GENERAL',
        ])->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $analysis = AnalisisLavadora::query()->where('numero_orden', '10000003')->firstOrFail();

        $this->actingAs($user)->post(route('analisis-lavadora.costos.manual.store', ['analisislavadora' => $analysis->id]), [
            'items' => [
                $catalogItem->id => [
                    'selected' => 1,
                    'catalog_item_id' => $catalogItem->id,
                    'quantity' => 2,
                    'notas' => 'Carga manual para historico',
                ],
            ],
        ])->assertSessionHas('success');

        $manualEntry = LavadoraCostEntry::query()
            ->where('analisis_lavadora_id', $analysis->id)
            ->where('source_type', LavadoraCostEntry::SOURCE_MANUAL)
            ->firstOrFail();

        $this->assertSame(900.5, (float) $manualEntry->total_cost);

        $this->actingAs($user)->put(route('analisis-lavadora.update', $analysis->id), [
            'fecha_analisis' => '2026-07-06',
            'numero_orden' => '10000003',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'INSPECCION GENERAL AJUSTADA',
        ])->assertRedirect(route('analisis-lavadora.index'));

        $this->assertDatabaseHas('lavadora_cost_entries', [
            'id' => $manualEntry->id,
            'analisis_lavadora_id' => $analysis->id,
            'source_type' => LavadoraCostEntry::SOURCE_MANUAL,
            'total_cost' => 900.50,
        ]);
    }

    public function test_automatic_rule_can_be_disabled_and_restored_per_analysis(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($user, [
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
            User::PERMISSION_EDIT_LAVADORA_COSTS,
        ]);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_RULE_COMPONENT', 'Componente Regla');
        $catalogItem = $this->createCatalogItem('CAT-ZZ-RULE-001', 'Costo automatico reversible', 999.99, 'Pieza');

        $rule = CostAutomationRule::query()->create([
            'cost_catalog_item_id' => $catalogItem->id,
            'component_code' => 'ZZ_RULE_COMPONENT',
            'trigger_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'quantity' => 1,
            'priority' => 1,
            'activo' => true,
        ]);

        $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-06',
            'numero_orden' => '10000004',
            'estado' => AnalisisLavadora::ESTADO_CAMBIADO,
            'actividad' => 'REEMPLAZO TOTAL DEL COMPONENTE',
        ])->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $analysis = AnalisisLavadora::query()->where('numero_orden', '10000004')->firstOrFail();

        $this->assertDatabaseHas('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
            'source_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
        ]);

        $this->actingAs($user)->post(route('analisis-lavadora.costos.automatic.disable', [
            'analisislavadora' => $analysis->id,
            'rule' => $rule->id,
        ]))->assertSessionHas('success');

        $this->assertDatabaseHas('lavadora_cost_rule_exclusions', [
            'analisis_lavadora_id' => $analysis->id,
            'cost_automation_rule_id' => $rule->id,
        ]);

        $this->assertDatabaseMissing('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
            'source_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
        ]);

        $this->actingAs($user)->delete(route('analisis-lavadora.costos.automatic.enable', [
            'analisislavadora' => $analysis->id,
            'rule' => $rule->id,
        ]))->assertSessionHas('success');

        $this->assertDatabaseMissing('lavadora_cost_rule_exclusions', [
            'analisis_lavadora_id' => $analysis->id,
            'cost_automation_rule_id' => $rule->id,
        ]);

        $this->assertDatabaseHas('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
            'source_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'total_cost' => 999.99,
        ]);
    }

    public function test_historical_analysis_can_sync_missing_automatic_costs(): void
    {
        $user = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($user, [
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
            User::PERMISSION_EDIT_LAVADORA_COSTS,
        ]);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_SYNC_COMPONENT', 'Componente Historico');
        $catalogItem = $this->createCatalogItem('CAT-ZZ-SYNC-001', 'Costo historico recuperable', 777.77, 'Pieza');

        CostAutomationRule::query()->create([
            'cost_catalog_item_id' => $catalogItem->id,
            'component_code' => 'ZZ_SYNC_COMPONENT',
            'trigger_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'quantity' => 1,
            'priority' => 1,
            'activo' => true,
        ]);

        $analysis = AnalisisLavadora::query()->create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-01',
            'numero_orden' => 'OT-COST-005',
            'estado' => AnalisisLavadora::ESTADO_CAMBIADO,
            'actividad' => 'ANALISIS HISTORICO SIN COSTOS',
            'usuario_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
        ]);

        $this->actingAs($user)->post(route('analisis-lavadora.costos.automatic.sync', [
            'analisislavadora' => $analysis->id,
        ]))->assertSessionHas('success');

        $this->assertDatabaseHas('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'catalog_item_id' => $catalogItem->id,
            'source_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
            'total_cost' => 777.77,
        ]);
    }

    public function test_supervisor_can_close_damage_without_registering_cost_from_modal(): void
    {
        $supervisor = $this->userWithRole(User::ROLE_SUPERVISOR);
        $this->enableCustomPermissions($supervisor, [User::PERMISSION_CLOSE_LAVADORA_DAMAGE]);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_DAMAGE_COMPONENT', 'Componente Danado');
        $analysis = AnalisisLavadora::query()->create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-CLOSE-001',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'DANO DETECTADO EN COMPONENTE',
            'usuario_id' => $supervisor->id,
        ]);

        $this->actingAs($supervisor)->patch(route('analisis-lavadora.correccion.update', ['analisislavadora' => $analysis->id]), [
            'estado_correccion' => AnalisisLavadora::CORRECCION_CORREGIDO,
        ])->assertSessionHas('success');

        $analysis->refresh();

        $this->assertSame(AnalisisLavadora::CORRECCION_CORREGIDO, $analysis->estado_correccion);
        $this->assertSame(AnalisisLavadora::ESTADO_DANADO, $analysis->estado);
        $this->assertSame(AnalisisLavadora::ESTADO_BUENO, $analysis->estado_operativo);
        $this->assertSame($supervisor->id, $analysis->corregido_por);
        $this->assertNotNull($analysis->fecha_correccion);
        $this->assertSame(0.0, (float) $analysis->costo_total_intervencion);

        $this->assertDatabaseMissing('lavadora_cost_entries', [
            'analisis_lavadora_id' => $analysis->id,
            'componente_id' => $componente->id,
            'source_type' => LavadoraCostEntry::SOURCE_DAMAGE_CLOSURE,
        ]);
    }

    public function test_technician_cannot_close_damage_administratively(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);
        $linea = $this->createLavadoraLinea();
        $componente = $this->createComponente($linea, 'ZZ_FORBIDDEN_COMPONENT', 'Componente Bloqueado');
        $analysis = AnalisisLavadora::query()->create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-CLOSE-002',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'DANO DETECTADO EN COMPONENTE',
            'usuario_id' => $technician->id,
        ]);

        $this->actingAs($technician)->patch(route('analisis-lavadora.correccion.update', ['analisislavadora' => $analysis->id]), [
            'estado_correccion' => AnalisisLavadora::CORRECCION_CORREGIDO,
            'fecha_correccion' => '2026-07-12 10:30:00',
            'tipo_intervencion' => 'Reparacion mecanica',
        ])->assertForbidden();

        $this->assertSame(AnalisisLavadora::CORRECCION_PENDIENTE, $analysis->refresh()->estado_correccion);
    }

    private function createLavadoraLinea(): Linea
    {
        return Linea::query()->create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
    }

    private function createComponente(Linea $linea, string $codigo, string $nombre): Componente
    {
        return Componente::query()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'linea' => $linea->nombre,
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
    }

    private function createCatalogItem(string $sku, string $nombre, float $costo, string $unidad): CostCatalogItem
    {
        return CostCatalogItem::query()->create([
            'sku' => $sku,
            'nombre' => $nombre,
            'categoria' => 'Pruebas',
            'unidad_medida' => $unidad,
            'costo_unitario' => $costo,
            'activo' => true,
            'fecha_actualizacion' => now()->toDateString(),
        ]);
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
