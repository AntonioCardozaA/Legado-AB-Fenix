<?php

namespace Tests\Feature;

use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisEtiquetadoraSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_etiquetadora_analysis_is_stored_in_its_own_table(): void
    {
        [$linea, $componente] = $this->crearCatalogoEtiquetadora();

        $analisis = AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => 'OT-ETQ-001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro de prueba Etiquetadora',
        ]);

        $this->assertDatabaseHas('analisis_etiquetadora', [
            'id' => $analisis->id,
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'numero_orden' => 'OT-ETQ-001',
            'maquina' => 'A',
        ]);

        $this->assertDatabaseMissing('analisis_componentes', [
            'numero_orden' => 'OT-ETQ-001',
        ]);

        $this->assertFalse(
            AnalisisLavadora::withoutGlobalScopes()
                ->where('numero_orden', 'OT-ETQ-001')
                ->exists()
        );

        $this->assertTrue($linea->analisisEtiquetadora()->whereKey($analisis->id)->exists());
        $this->assertTrue($componente->analisisEtiquetadora()->whereKey($analisis->id)->exists());
    }

    public function test_latest_etiquetadora_query_does_not_read_lavadora_rows(): void
    {
        [$linea, $componente] = $this->crearCatalogoEtiquetadora();

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-LAV-FUTURE',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro de lavadora que no debe mezclarse',
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('B'),
            'maquina' => 'B',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => 'OT-ETQ-LATEST',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro vigente Etiquetadora',
        ]);

        $this->assertSame(
            ['OT-ETQ-LATEST'],
            AnalisisEtiquetadora::ultimosPorComponente()
                ->pluck('numero_orden')
                ->all()
        );
    }

    public function test_store_saves_selected_piece_checklist_for_multi_piece_component(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        $response = $this->actingAs($user)->post(route('analisis-etiquetadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => '123456',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Revision con checklist de piezas',
            'componentes_revisados' => [1, 3, 4],
        ]);

        $response->assertRedirect(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $analisis = AnalisisEtiquetadora::where('numero_orden', '123456')->firstOrFail();

        $this->assertSame(4, $analisis->total_componentes);
        $this->assertSame(3, $analisis->cantidad_componentes_revisados);
        $this->assertSame([1, 3, 4], $analisis->componentes_revisados_lista);
        $this->assertSame($user->id, $analisis->usuario_id);
    }

    public function test_create_view_renders_piece_checklist_container(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.create', [
            'linea' => $linea->id,
            'maquina' => 'A',
            'componente_id' => $componente->id,
        ]));

        $response->assertOk();
        $response->assertSee('Piezas revisadas');
        $response->assertSee('componentes-checklist-wrapper', false);
    }

    public function test_historial_counts_unique_reviewed_pieces_without_mixing_machines(): void
    {
        $primerUsuario = User::factory()->create(['name' => 'Inspector A']);
        $ultimoUsuario = User::factory()->create(['name' => 'Inspector B']);
        [$linea, $componenteA] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        $componenteB = Componente::create([
            'codigo' => 'ETQ_L04_B_PRUEBA',
            'nombre' => $componenteA->nombre,
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('B'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 4,
            'cantidad_original' => '4*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteA->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-ETQ-A-001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Primer avance de piezas',
            'usuario_id' => $primerUsuario->id,
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteA->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-12',
            'numero_orden' => 'OT-ETQ-A-002',
            'estado' => AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Reanalisis con pieza repetida',
            'usuario_id' => $ultimoUsuario->id,
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [2, 3],
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteB->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('B'),
            'maquina' => 'B',
            'fecha_analisis' => '2026-07-13',
            'numero_orden' => 'OT-ETQ-B-001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Revision completa de maquina B',
            'usuario_id' => $primerUsuario->id,
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 4,
            'componentes_revisados' => [1, 2, 3, 4],
        ]);

        $response = $this->actingAs($primerUsuario)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();

        $resumen = $response->viewData('resumenHistorico');
        $estadisticas = collect($response->viewData('estadisticasHistorico'));
        $detalle = $estadisticas->first();

        $this->assertSame(4, $resumen['total_general']);
        $this->assertSame(3, $resumen['revisado_general']);
        $this->assertSame(1, $resumen['pendiente_general']);
        $this->assertEquals(75.0, $resumen['porcentaje_general']);
        $this->assertSame(1, $resumen['componentes_revisados']);
        $this->assertSame(0, $resumen['componentes_completos']);
        $this->assertSame(1, $resumen['componentes_pendientes']);

        $this->assertSame(4, $detalle['cantidad_total']);
        $this->assertSame(3, $detalle['cantidad_revisada']);
        $this->assertSame(1, $detalle['cantidad_pendiente']);
        $this->assertEquals(75.0, $detalle['porcentaje']);
        $this->assertSame(['A'], $detalle['maquinas']->all());
        $this->assertSame('Inspector B', $detalle['usuario_ultima_revision']);
        $this->assertSame(AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION, $detalle['estado_actual']);
        $this->assertSame([1, 2, 3], $detalle['detalle_componentes'][0]['piezas_revisadas']);
        $this->assertSame([4], $detalle['detalle_componentes'][0]['piezas_pendientes']);
    }

    /**
     * @return array{0: Linea, 1: Componente}
     */
    private function crearCatalogoEtiquetadora(int $cantidadTotal = 1): array
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba Etiquetadora',
            'activo' => true,
        ]);

        $componente = Componente::create([
            'codigo' => 'ETQ_L04_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => $cantidadTotal,
            'cantidad_original' => $cantidadTotal . '*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        return [$linea, $componente];
    }
}
