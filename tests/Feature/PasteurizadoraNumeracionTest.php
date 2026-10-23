<?php

namespace Tests\Feature;

use App\Exports\AnalisisPasteurizadoraExport;
use App\Models\AnalisisPasteurizadora;
use App\Models\HistoricoRevisados;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasteurizadoraNumeracionTest extends TestCase
{
    use RefreshDatabase;

    private function equipo(string $nombre = 'P-06'): Linea
    {
        return Linea::create(['nombre' => $nombre, 'descripcion' => 'Equipo de prueba', 'activo' => true]);
    }

    private function usuario(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');
        $usuario = User::factory()->create();
        $usuario->assignRole(User::ROLE_ADMIN);

        return $usuario;
    }

    private function datos(Linea $linea, array $cambios = []): array
    {
        return array_merge([
            'linea_id' => $linea->id, 'modulo' => 2, 'componente' => 'ANILLAS',
            'nivel' => 'SUPERIOR', 'lado' => 'VAPOR', 'fecha_analisis' => now()->toDateString(),
            'estado' => AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Inspeccion del componente fisico', 'componentes_revisados' => [6],
        ], $cambios);
    }

    public function test_captura_consulta_edicion_y_exportacion_conservan_el_numero_global(): void
    {
        $linea = $this->equipo();
        $this->actingAs($this->usuario());
        $this->post(route('pasteurizadora.analisis-pasteurizadora.store'), $this->datos($linea))->assertSessionHasNoErrors();
        $registro = AnalisisPasteurizadora::firstOrFail();
        $this->assertSame([6], $registro->componentes_revisados);
        $this->assertSame(5, $registro->total_componentes);
        $this->assertSame([6, 7, 8, 9, 10], AnalisisPasteurizadora::getComponentesDisponiblesSeguimiento($linea->id, 2, 'ANILLAS', 'VAPOR', 'SUPERIOR'));

        $this->get(route('pasteurizadora.analisis-pasteurizadora.edit', $registro->id))
            ->assertOk()->assertSee('value="6"', false)->assertSee('value="10"', false);
        $this->put(route('pasteurizadora.analisis-pasteurizadora.update', $registro->id), [
            'componentes_revisados' => [7], 'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
        ])->assertSessionHasNoErrors();
        $registro->refresh();
        $this->assertSame([7], $registro->componentes_revisados_lista);
        $this->assertSame(20.0, $registro->porcentaje_avance);
        $this->get(route('pasteurizadora.analisis-pasteurizadora.show', $registro->id))->assertOk()->assertSee('#7');
        $this->get(route('pasteurizadora.analisis-pasteurizadora.historial', ['linea_id' => $linea->id]))->assertOk()->assertSee('#7');
        $indice = $this->get(route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]));
        $indice->assertOk();
        $this->assertStringContainsString('Ubicacion: #7 Vapor - Superior', preg_replace('/\s+/', ' ', strip_tags($indice->getContent())));
        $this->assertSame('7', (new AnalisisPasteurizadoraExport(collect([$registro])))->map($registro)[11]);
        $this->assertDatabaseCount('analisis_pasteurizadora', 1);
    }

    public function test_rechaza_numeros_de_otro_modulo_y_no_crea_registros_parciales(): void
    {
        $linea = $this->equipo();
        $this->actingAs($this->usuario());
        foreach ([[1], [6, 11], [6, 0], [6, 6.5]] as $seleccion) {
            $this->post(route('pasteurizadora.analisis-pasteurizadora.store'), $this->datos($linea, [
                'componentes_revisados' => $seleccion,
            ]))->assertSessionHasErrors('componentes_revisados');
        }
        $this->assertDatabaseCount('analisis_pasteurizadora', 0);
    }

    public function test_ciclos_y_reinspeccion_operan_en_el_ultimo_modulo(): void
    {
        $linea = $this->equipo();
        foreach (AnalisisPasteurizadora::NIVELES as $nivel) {
            foreach (AnalisisPasteurizadora::LADOS as $lado) {
                AnalisisPasteurizadora::create($this->datos($linea, [
                    'modulo' => 16, 'nivel' => $nivel, 'lado' => $lado,
                    'componentes_revisados' => [76, 77, 78, 79, 80], 'total_componentes' => 5,
                    'estado' => AnalisisPasteurizadora::ESTADO_BUENO,
                ]));
            }
        }
        $this->assertTrue(AnalisisPasteurizadora::getResumenCicloComponente($linea->id, 16, 'ANILLAS')['tiene_ciclo_completado']);
        $this->assertSame([76, 77, 78, 79, 80], AnalisisPasteurizadora::getComponentesPendientes($linea->id, 16, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $registro = AnalisisPasteurizadora::create($this->datos($linea, [
            'modulo' => 16, 'componentes_revisados' => [80], 'total_componentes' => 5,
        ]));
        $this->assertSame([80], AnalisisPasteurizadora::getComponentesReinspeccionSeguimiento($linea->id, 16, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $registro->update(['estado' => AnalisisPasteurizadora::ESTADO_BUENO]);
        $this->assertSame([], AnalisisPasteurizadora::getComponentesReinspeccionSeguimiento($linea->id, 16, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $this->assertSame([76, 77, 78, 79], AnalisisPasteurizadora::getComponentesDisponiblesSeguimiento($linea->id, 16, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
    }

    public function test_migracion_es_idempotente_reversible_y_preserva_identidad_historial_y_eliminados(): void
    {
        $linea = $this->equipo();
        $registro = AnalisisPasteurizadora::create($this->datos($linea, ['componentes_revisados' => [1, 5], 'total_componentes' => 5]));
        $resolutor = AnalisisPasteurizadora::create($this->datos($linea, ['componentes_revisados' => [5], 'total_componentes' => 5]));
        $registro->marcarComoResuelto($resolutor, 'Conservar vinculo');
        $resolutor->delete();
        DB::table('analisis_pasteurizadora')->update(['numeracion_version' => 1]);
        $antes = DB::table('analisis_pasteurizadora')->orderBy('id')->get()->keyBy('id');
        $migracion = require database_path('migrations/2026_10_23_000001_use_consecutive_pasteurizadora_component_numbers.php');
        $migracion->up();
        $migracion->up();
        $despues = DB::table('analisis_pasteurizadora')->orderBy('id')->get()->keyBy('id');
        $this->assertCount(2, $despues);
        foreach ($antes as $id => $original) {
            $esperado = (array) $original;
            $esperado['componentes_revisados'] = $id === $registro->id ? '[6,10]' : '[10]';
            $esperado['numeracion_version'] = 2;
            $this->assertSame($esperado, (array) $despues[$id]);
        }
        $this->assertSame($resolutor->id, $registro->fresh()->id_registro_que_resolvio);
        $migracion->down();
        foreach ($antes as $id => $original) {
            $esperado = (array) $original;
            unset($esperado['numeracion_version']);
            $this->assertSame($esperado, (array) DB::table('analisis_pasteurizadora')->find($id));
        }
        $migracion->up();
        $this->assertSame([6, 10], $registro->fresh()->componentes_revisados);
    }

    public function test_registros_con_solo_cantidad_y_totales_historicos_usan_el_rango_correcto(): void
    {
        $linea = $this->equipo('P-04');
        $registro = AnalisisPasteurizadora::create($this->datos($linea, [
            'modulo' => 12, 'componentes_revisados' => [], 'cantidad_componentes_revisados' => 2, 'total_componentes' => 3,
        ]));
        $this->assertSame([34, 35], $registro->componentes_revisados_lista);
        $this->assertSame([36], AnalisisPasteurizadora::getComponentesPendientes($linea->id, 12, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
        $historico = HistoricoRevisados::actualizarDesdePasteurizadora($linea, 'ANILLAS');
        $this->assertEquals(36, $historico->cantidad_total);
        $this->assertEquals(2, $historico->cantidad_revisada);
        $this->assertSame([1, 2, 3], AnalisisPasteurizadora::getComponentesPendientes($linea->id, 1, 'ANILLAS', 'VAPOR', 'SUPERIOR'));
    }

    public function test_migracion_conserva_contadores_historicos_sin_analisis_de_origen(): void
    {
        $linea = $this->equipo('P-03');
        $historico = HistoricoRevisados::create([
            'area' => AnalisisPasteurizadora::AREA_MECANICA, 'linea_id' => $linea->id,
            'componente' => 'ANILLAS', 'componente_nombre' => 'Anillas',
            'cantidad_total' => 3, 'cantidad_revisada' => 3, 'porcentaje' => 100,
            'tipo_pasteurizadora' => 'sencillo', 'estado' => 'completo',
        ]);
        $fecha = $historico->updated_at;
        $migracion = require database_path('migrations/2026_10_23_000001_use_consecutive_pasteurizadora_component_numbers.php');
        $migracion->up();
        $historico->refresh();
        $this->assertEquals(3, $historico->cantidad_revisada);
        $this->assertEquals(27, $historico->cantidad_total);
        $this->assertEquals($fecha, $historico->updated_at);
        $migracion->up();
        $this->assertEquals(3, $historico->fresh()->cantidad_revisada);
    }

    public function test_migracion_invalida_revierte_todo_sin_descartar_componentes(): void
    {
        $linea = $this->equipo();
        $registro = AnalisisPasteurizadora::create($this->datos($linea, ['componentes_revisados' => [1], 'total_componentes' => 5]));
        $invalido = AnalisisPasteurizadora::create($this->datos($linea, ['componentes_revisados' => [99], 'total_componentes' => 5]));
        DB::table('analisis_pasteurizadora')->update(['numeracion_version' => 1]);
        $migracion = require database_path('migrations/2026_10_23_000001_use_consecutive_pasteurizadora_component_numbers.php');
        try {
            $migracion->up();
            $this->fail('La migración debe rechazar la posición inválida.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString((string) $invalido->id, $exception->getMessage());
        }
        $this->assertSame([1], $registro->fresh()->componentes_revisados);
        $this->assertSame([99], $invalido->fresh()->componentes_revisados);
        $this->assertSame(1, $registro->fresh()->numeracion_version);
        $this->assertDatabaseCount('analisis_pasteurizadora', 2);
    }

    public function test_captura_rapida_y_api_de_excentricos_utilizan_el_rango_global(): void
    {
        $linea = $this->equipo();
        $this->actingAs($this->usuario());
        $datos = $this->datos($linea, [
            'modulo' => 16, 'componente' => 'EXCENTRICOS', 'componentes_revisados' => json_encode([31]),
        ]);
        $this->post(route('pasteurizadora.analisis-pasteurizadora.store-quick'), $datos)->assertSessionHasNoErrors();
        $registro = AnalisisPasteurizadora::firstOrFail();
        $this->assertSame([31], $registro->componentes_revisados_lista);
        $this->postJson(route('pasteurizadora.analisis-pasteurizadora.ajax.piezas-disponibles'), [
            'linea_id' => $linea->id, 'modulo' => 16, 'componente' => 'EXCENTRICOS', 'nivel' => 'SUPERIOR', 'lado' => 'VAPOR',
        ])->assertOk()->assertJsonPath('componentes_disponibles', [31, 32]);
        $this->get(route('pasteurizadora.analisis-pasteurizadora.create-quick', [
            'linea_id' => $linea->id, 'modulo' => 16, 'componente' => 'EXCENTRICOS',
        ]))->assertOk()->assertSee('numeros_por_modulo', false)->assertSee('[31,32]', false);
        $this->get(route('pasteurizadora.analisis-pasteurizadora.create', ['linea' => $linea->id, 'linea_id' => $linea->id]))
            ->assertOk()->assertSee('[76,77,78,79,80]', false);
        $this->get(route('pasteurizadora.analisis-pasteurizadora.excentricos.index', ['linea_id' => $linea->id]))
            ->assertOk()->assertSee('#31–#32', false);
    }

    public function test_cambiar_un_componente_no_resuelve_el_indicador_de_otro_numero(): void
    {
        $linea = $this->equipo();
        $this->actingAs($this->usuario());
        $objetivo = AnalisisPasteurizadora::create($this->datos($linea, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'total_componentes' => 5,
        ]));
        $otro = AnalisisPasteurizadora::create($this->datos($linea, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'componentes_revisados' => [10], 'total_componentes' => 5,
        ]));
        $this->post(route('pasteurizadora.analisis-pasteurizadora.store'), $this->datos($linea, [
            'estado' => AnalisisPasteurizadora::ESTADO_CAMBIADO,
        ]))->assertSessionHasNoErrors();
        $this->assertTrue($objetivo->fresh()->resuelto_por_cambio);
        $this->assertFalse($otro->fresh()->resuelto_por_cambio);
        $this->assertSame([6], $objetivo->fresh()->componentes_revisados);
        $this->assertSame([10], $otro->fresh()->componentes_revisados);
    }
}
