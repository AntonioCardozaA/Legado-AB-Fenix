<?php

namespace Tests\Feature;

use App\Models\AnalisisPasteurizadora;
use App\Models\HistoricoRevisados;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasteurizadoraAnalysisCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_component_can_start_a_new_cycle_from_zero(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        foreach (AnalisisPasteurizadora::NIVELES as $nivel) {
            foreach (AnalisisPasteurizadora::LADOS as $lado) {
                $this->crearAnalisis($linea, [
                    'modulo' => 1,
                    'componente' => 'ANILLAS',
                    'nivel' => $nivel,
                    'lado' => $lado,
                    'numero_orden' => sprintf('OT-%s-%s', $nivel, $lado),
                    'componentes_revisados' => [1, 2, 3],
                    'cantidad_componentes_revisados' => 3,
                    'total_componentes' => 3,
                ]);
            }
        }

        $resumen = AnalisisPasteurizadora::getResumenCicloComponente($linea->id, 1, 'ANILLAS');

        $this->assertTrue($resumen['tiene_ciclo_completado']);
        $this->assertFalse($resumen['tiene_ciclo_activo']);
        $this->assertSame(3, AnalisisPasteurizadora::getCantidadComponentesPendientes($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $this->assertSame(0, AnalisisPasteurizadora::getCantidadComponentesRevisados($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => '1001',
                'estado' => 'Buen estado',
                'actividad' => 'Inicio de un nuevo ciclo de revision',
                'componentes_revisados' => [1],
            ]
        );

        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => 'ANILLAS',
            'nivel' => 'SUPERIOR',
            'lado' => 'VAPOR',
            'numero_orden' => '1001',
            'usuario_id' => $user->id,
        ]);

        $this->assertSame(2, AnalisisPasteurizadora::getCantidadComponentesPendientes($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $this->assertSame(1, AnalisisPasteurizadora::getCantidadComponentesRevisados($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));

        $analisis = AnalisisPasteurizadora::with('usuario')
            ->where('numero_orden', '1001')
            ->firstOrFail();

        $this->assertTrue($analisis->usuario->is($user));
    }

    public function test_pasteurizadora_evidence_is_stored_in_pasteurizadora_public_folder(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => '2001',
                'estado' => 'Buen estado',
                'actividad' => 'Registro con evidencia fotografica',
                'componentes_revisados' => [1],
                'evidencia_fotos' => [
                    UploadedFile::fake()->image('evidencia.jpg', 800, 600),
                ],
            ]
        );

        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $analisis = AnalisisPasteurizadora::where('numero_orden', '2001')->firstOrFail();
        $this->assertCount(1, $analisis->evidencia_fotos);
        $this->assertStringStartsWith('analisis-pasteurizadora/', $analisis->evidencia_fotos[0]);

        $rutaPublica = public_path('storage/' . $analisis->evidencia_fotos[0]);
        $this->assertFileExists($rutaPublica);

        @unlink($rutaPublica);
    }

    public function test_pasteurizadora_analysis_can_be_stored_without_order_number(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => '',
                'estado' => 'Buen estado',
                'actividad' => 'Registro sin numero de orden',
                'componentes_revisados' => [1],
            ]
        );

        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => 'ANILLAS',
            'numero_orden' => null,
            'usuario_id' => $user->id,
        ]);
    }

    public function test_quick_mechanical_analysis_syncs_historico_revisados(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Revision rapida para alimentar reporte',
                'componentes_revisados' => json_encode([1, 2]),
            ]
        );

        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $analisis = AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('componente', 'ANILLAS')
            ->firstOrFail();

        $this->assertTrue($analisis->es_registro_quick);
        $this->assertSame([1, 2], $analisis->componentes_revisados_lista);
        $this->assertDatabaseHas('historico_revisados', [
            'linea_id' => $linea->id,
            'componente' => 'ANILLAS',
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'cantidad_revisada' => 2,
            'estado' => 'en_progreso',
        ]);
        $this->assertSame(1, HistoricoRevisados::where('linea_id', $linea->id)
            ->where('componente', 'ANILLAS')
            ->where('area', AnalisisPasteurizadora::AREA_MECANICA)
            ->count());
    }

    public function test_quick_analysis_stores_start_and_final_dates(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $fechaInicio = now()->subDays(3)->toDateString();
        $fechaFin = now()->toDateString();

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Revision rapida con rango de fechas',
                'componentes_revisados' => json_encode([1]),
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $analisis = AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('componente', 'ANILLAS')
            ->firstOrFail();

        $this->assertTrue($analisis->es_registro_quick);
        $this->assertSame($fechaInicio, $analisis->fecha_inicio?->toDateString());
        $this->assertSame($fechaFin, $analisis->fecha_fin?->toDateString());
        $this->assertSame($fechaFin, $analisis->fecha_analisis?->toDateString());
        $modalResponse = $this->actingAs($user)->get(route('pasteurizadora.analisis-pasteurizadora.index', [
            'linea_id' => $linea->id,
            'open_analysis_id' => $analisis->id,
        ]));

        $modalResponse->assertOk();
        $modalResponse->assertViewHas('openAnalysisData', function ($data) use ($analisis) {
            return is_array($data)
                && $data['fecha_inicio'] === $analisis->fecha_inicio?->format('d/m/Y')
                && $data['fecha_fin'] === $analisis->fecha_fin?->format('d/m/Y');
        });
        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'componente' => 'ANILLAS',
        ]);
    }

    public function test_quick_follow_up_can_reinspect_component_damaged_in_programmed_review(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_NORMAL,
            'numero_orden' => '3001',
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Dano detectado en revision programada',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'estado' => AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION,
                'actividad' => 'Seguimiento al dano de la revision programada',
                'componentes_revisados' => json_encode([1]),
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => 'ANILLAS',
            'nivel' => 'SUPERIOR',
            'lado' => 'VAPOR',
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_NORMAL,
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
        ]);
        $this->assertDatabaseHas('analisis_pasteurizadora', [
            'linea_id' => $linea->id,
            'modulo' => 1,
            'componente' => 'ANILLAS',
            'nivel' => 'SUPERIOR',
            'lado' => 'VAPOR',
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'estado' => AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION,
        ]);
        $this->assertSame(2, AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('modulo', 1)
            ->where('componente', 'ANILLAS')
            ->where('nivel', 'SUPERIOR')
            ->where('lado', 'VAPOR')
            ->count());
    }

    public function test_quick_follow_up_can_reinspect_when_latest_quick_state_requires_attention(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'estado' => 'Desgaste severo',
            'actividad' => 'Seguimiento inicial con desgaste severo',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Seguimiento posterior al desgaste',
                'componentes_revisados' => json_encode([1]),
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));

        $this->assertSame(2, AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('modulo', 1)
            ->where('componente', 'ANILLAS')
            ->where('nivel', 'SUPERIOR')
            ->where('lado', 'VAPOR')
            ->quick()
            ->count());
    }

    public function test_quick_follow_up_still_blocks_repeat_when_latest_state_is_good(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
            'actividad' => 'Seguimiento cerrado sin dano',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store-quick'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
                'actividad' => 'Intento de repetir componente ya sano',
                'componentes_revisados' => json_encode([1]),
            ]
        );

        $response->assertSessionHasErrors('componentes_revisados');
        $this->assertSame(1, AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('modulo', 1)
            ->where('componente', 'ANILLAS')
            ->where('nivel', 'SUPERIOR')
            ->where('lado', 'VAPOR')
            ->quick()
            ->count());
    }

    public function test_programmed_review_keeps_existing_component_selection_behavior(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_NORMAL,
            'numero_orden' => '4001',
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
        ]);

        $response = $this->actingAs($user)->post(
            route('pasteurizadora.analisis-pasteurizadora.store'),
            [
                'linea_id' => $linea->id,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'componente' => 'ANILLAS',
                'lado' => 'VAPOR',
                'fecha_analisis' => now()->toDateString(),
                'numero_orden' => '4002',
                'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                'actividad' => 'Revision programada posterior',
                'componentes_revisados' => [1],
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));
        $this->assertSame(2, AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('modulo', 1)
            ->where('componente', 'ANILLAS')
            ->where('nivel', 'SUPERIOR')
            ->where('lado', 'VAPOR')
            ->normal()
            ->count());
    }

    public function test_pasteurizadora_report_includes_quick_audit_history_changes(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN, [
            'name' => 'Inspector Test',
        ]);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'fecha_analisis' => now()->subDay()->toDateString(),
            'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
            'actividad' => 'Inspeccion inicial',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'evidencia_fotos' => ['analisis-pasteurizadora/evidencia-auditoria.jpg'],
            'usuario_id' => $user->id,
        ]);

        $this->crearAnalisis($linea, [
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_QUICK,
            'fecha_analisis' => now()->toDateString(),
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Ajuste correctivo aplicado',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'usuario_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reportes.show', [
            'lineaId' => $linea->id,
            'tipo' => 'pasteurizadoras',
            'fecha_inicio' => now()->subDays(2)->toDateString(),
            'fecha_fin' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Estado: ' . AnalisisPasteurizadora::ESTADO_BUENO . ' -> ' . AnalisisPasteurizadora::ESTADO_DANADO);
        $response->assertSee('Inspector Test');
        $response->assertSee('#1');
        $response->assertSee('Cambio requerido');
        $response->assertSee('AJUSTE CORRECTIVO APLICADO');
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

    private function crearAnalisis(Linea $linea, array $overrides = []): AnalisisPasteurizadora
    {
        return AnalisisPasteurizadora::create(array_merge([
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-TEST',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de prueba',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => null,
            'resuelto_por_cambio' => false,
        ], $overrides));
    }
}
