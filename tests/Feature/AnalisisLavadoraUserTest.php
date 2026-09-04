<?php

namespace Tests\Feature;

use App\Jobs\SendLavadoraDamageWhatsApp;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalisisLavadoraUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_saves_authenticated_user_for_index_display(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => '12345678',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de prueba para usuario',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_componentes', [
            'linea_id' => $linea->id,
            'numero_orden' => '12345678',
            'usuario_id' => $user->id,
        ]);

        $analisis = AnalisisLavadora::with('usuario')
            ->where('numero_orden', '12345678')
            ->firstOrFail();

        $this->assertTrue($analisis->usuario->is($user));
    }

    public function test_store_allows_missing_order_number(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'estado' => 'Buen estado',
            'actividad' => 'Registro sin numero de orden',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_componentes', [
            'linea_id' => $linea->id,
            'numero_orden' => null,
            'usuario_id' => $user->id,
        ]);
    }

    public function test_store_rejects_order_number_that_is_not_exactly_eight_digits(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('analisis-lavadora.create', $linea->id))
            ->post(route('analisis-lavadora.store'), [
                'linea_id' => $linea->id,
                'componente_codigo' => 'SERVO_CHICO',
                'reductor' => 'Reductor 1',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => 'OT-12345',
                'estado' => 'Buen estado',
                'actividad' => 'Registro con numero de orden invalido',
            ]);

        $response->assertRedirect(route('analisis-lavadora.create', $linea->id));
        $response->assertSessionHasErrors('numero_orden');

        $this->assertDatabaseMissing('analisis_componentes', [
            'linea_id' => $linea->id,
            'actividad' => 'Registro con numero de orden invalido',
        ]);
    }

    public function test_quick_store_without_order_redirects_to_index_when_redirect_points_to_quick_create(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $quickCreateUrl = route('analisis-lavadora.create-quick', [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'estado' => 'Buen estado',
            'actividad' => 'Registro rapido sin numero de orden',
            'redirect_to' => $quickCreateUrl,
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_componentes', [
            'linea_id' => $linea->id,
            'numero_orden' => null,
            'usuario_id' => $user->id,
        ]);
    }

    public function test_store_queues_damage_whatsapp_alert(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => '87654321',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro de dano para alerta automatica',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        Queue::assertPushed(SendLavadoraDamageWhatsApp::class, function (SendLavadoraDamageWhatsApp $job) use ($linea): bool {
            return $job->number === '5214981239090'
                && str_contains($job->message, $linea->nombre)
                && ($job->context['linea_id'] ?? null) === $linea->id;
        });
    }

    public function test_index_searches_component_across_all_lavadoras_with_line_suffix_codes(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'codigo' => 'SERVO_CHICO_L_04',
            'nombre' => 'Servo Chico',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-LAV-SEARCH',
            'estado' => 'Buen estado',
            'actividad' => 'Registro visible en busqueda global',
            'usuario_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-lavadora.index', [
            'linea_id' => 'todas',
            'componente_id' => 'SERVO_CHICO',
        ]));

        $response->assertOk();
        $response->assertSee('OT-LAV-SEARCH');
        $response->assertSee('search-target-cell', false);
    }

    public function test_index_visual_data_uses_latest_analysis_only(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
        $componenteHistorico = Componente::create([
            'codigo' => 'L04_reductor_1_GUI_INT_TANQUE',
            'nombre' => 'Guia Intermedia',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);
        $componenteVigente = Componente::create([
            'codigo' => 'GUI_INT_TANQUE_L_04',
            'nombre' => 'Guia Intermedia',
            'linea' => 'L-04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteHistorico->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-LAV-OLD',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro historico danado',
            'usuario_id' => $user->id,
        ]);

        $vigente = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteVigente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-11',
            'numero_orden' => 'OT-LAV-NEW',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Registro vigente bueno',
            'usuario_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-lavadora.index', [
            'linea_id' => $linea->id,
        ]));

        $response->assertOk();

        $analisis = collect($response->viewData('analisis'));
        $estadisticas = $response->viewData('estadisticas');

        $this->assertSame([$vigente->id], $analisis->pluck('id')->all());
        $this->assertSame(2, (int) $analisis->first()->total_historial);
        $this->assertSame(1, $estadisticas['total']);
        $this->assertSame(1, $estadisticas['buen_estado']);
        $this->assertSame(0, $estadisticas['danado_requiere']);
    }

    public function test_washer_history_splits_side_based_components_by_side(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-07',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'codigo' => 'GUI_INT_TANQUE',
            'nombre' => 'Guia Intermedia',
            'linea' => $linea->nombre,
            'reductor' => 'Reductor 21',
            'ubicacion' => 'Reductor 21',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 21',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-08-20',
            'numero_orden' => '11112222',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Revision lado vapor',
            'usuario_id' => $user->id,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 21',
            'lado' => 'PASILLO',
            'fecha_analisis' => '2026-08-21',
            'numero_orden' => '33334444',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Revision lado pasillo',
            'usuario_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-lavadora.historial', [
            'linea_id' => $linea->id,
            'componente_id' => 'GUI_INT_TANQUE',
            'reductor' => 'Reductor 21',
        ]));

        $response->assertOk();
        $response->assertSee('grid grid-cols-1 xl:grid-cols-2 gap-6 items-start', false);
        $response->assertSee('Lado Vapor');
        $response->assertSee('Lado Pasillo');
        $response->assertSee('REVISION LADO VAPOR');
        $response->assertSee('REVISION LADO PASILLO');
        $this->assertTrue($response->viewData('separarHistorialPorLado'));

        $secciones = $response->viewData('historialSecciones');

        $this->assertSame(['VAPOR', 'PASILLO'], $secciones->pluck('key')->all());
        $this->assertSame('11112222', $secciones->firstWhere('key', 'VAPOR')['registros']->first()->numero_orden);
        $this->assertSame('33334444', $secciones->firstWhere('key', 'PASILLO')['registros']->first()->numero_orden);
    }

    public function test_index_history_count_for_side_based_components_includes_both_sides(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-07',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'codigo' => 'GUI_INT_TANQUE',
            'nombre' => 'Guia Intermedia',
            'linea' => $linea->nombre,
            'reductor' => 'Reductor 21',
            'ubicacion' => 'Reductor 21',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        foreach (['VAPOR', 'PASILLO'] as $index => $lado) {
            AnalisisLavadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $componente->id,
                'reductor' => 'Reductor 21',
                'lado' => $lado,
                'fecha_analisis' => '2026-08-' . (20 + $index),
                'numero_orden' => (string) (55550000 + $index),
                'estado' => AnalisisLavadora::ESTADO_BUENO,
                'actividad' => 'Revision ' . strtolower($lado),
                'usuario_id' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('analisis-lavadora.index', [
            'linea_id' => $linea->id,
        ]));

        $response->assertOk();

        $analisis = collect($response->viewData('analisis'));

        $this->assertCount(2, $analisis);
        $this->assertSame([2, 2], $analisis->pluck('total_historial')->map(fn ($total) => (int) $total)->all());
    }

    public function test_authorized_role_can_update_analysis_date_and_creates_audit_record(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => User::ROLE_TECNICO, 'guard_name' => 'web']);
        $user->assignRole(User::ROLE_TECNICO);

        $analisis = $this->crearAnalisisLavadora([
            'fecha_analisis' => '2026-01-10',
            'numero_orden' => 'OT-FECHA-001',
        ]);

        $response = $this->actingAs($user)->put(route('analisis-lavadora.update', $analisis->id), [
            'fecha_analisis' => '2026-02-15',
            'numero_orden' => 'OT-FECHA-001',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Correccion controlada de fecha',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index'));

        $this->assertSame('2026-02-15', $analisis->fresh()->fecha_analisis->toDateString());

        $cambioFecha = $analisis->cambiosFecha()->firstOrFail();

        $this->assertSame($user->id, $cambioFecha->usuario_id);
        $this->assertSame('2026-01-10', $cambioFecha->fecha_anterior->toDateString());
        $this->assertSame('2026-02-15', $cambioFecha->fecha_nueva->toDateString());
    }

    public function test_maintenance_engineer_can_update_analysis_date_like_technician(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => User::ROLE_INGENIERO_MANTENIMIENTO, 'guard_name' => 'web']);
        $user->assignRole(User::ROLE_INGENIERO_MANTENIMIENTO);

        $analisis = $this->crearAnalisisLavadora([
            'fecha_analisis' => '2026-01-10',
            'numero_orden' => 'OT-FECHA-002',
        ]);

        $response = $this->actingAs($user)->put(route('analisis-lavadora.update', $analisis->id), [
            'fecha_analisis' => '2026-02-15',
            'numero_orden' => 'OT-FECHA-002',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Correccion controlada de fecha por ingeniero',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index'));

        $this->assertSame('2026-02-15', $analisis->fresh()->fecha_analisis->toDateString());
        $this->assertDatabaseHas('analisis_lavadora_fecha_cambios', [
            'analisis_lavadora_id' => $analisis->id,
            'usuario_id' => $user->id,
        ]);
    }

    public function test_role_without_date_permission_cannot_update_analysis_date(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => User::ROLE_GERENTE_MANTENIMIENTO, 'guard_name' => 'web']);
        $user->assignRole(User::ROLE_GERENTE_MANTENIMIENTO);

        $analisis = $this->crearAnalisisLavadora([
            'fecha_analisis' => '2026-01-10',
            'numero_orden' => 'OT-FECHA-004',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('analisis-lavadora.update', $analisis->id), [
                'fecha_analisis' => '2026-02-15',
                'numero_orden' => 'OT-FECHA-004',
                'estado' => AnalisisLavadora::ESTADO_BUENO,
                'actividad' => 'Intento de cambio no autorizado',
            ]);

        $response->assertForbidden();

        $this->assertSame('2026-01-10', $analisis->fresh()->fecha_analisis->toDateString());

        $this->assertDatabaseMissing('analisis_lavadora_fecha_cambios', [
            'analisis_lavadora_id' => $analisis->id,
        ]);
    }

    public function test_invalid_analysis_date_format_is_rejected_on_update(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => User::ROLE_ADMIN, 'guard_name' => 'web']);
        $user->assignRole(User::ROLE_ADMIN);

        $analisis = $this->crearAnalisisLavadora([
            'fecha_analisis' => '2026-01-10',
            'numero_orden' => 'OT-FECHA-003',
        ]);

        $response = $this->actingAs($user)->from(route('analisis-lavadora.edit', $analisis->id))
            ->put(route('analisis-lavadora.update', $analisis->id), [
                'fecha_analisis' => '15/02/2026',
                'numero_orden' => 'OT-FECHA-003',
                'estado' => AnalisisLavadora::ESTADO_BUENO,
                'actividad' => 'Formato invalido',
            ]);

        $response->assertRedirect(route('analisis-lavadora.edit', $analisis->id));
        $response->assertSessionHasErrors('fecha_analisis');

        $this->assertSame('2026-01-10', $analisis->fresh()->fecha_analisis->toDateString());
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
            'numero_orden' => 'OT-LAV-TEST',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Registro de prueba',
        ], $overrides));
    }
}
