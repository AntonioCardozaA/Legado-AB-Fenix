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

    public function test_historial_without_linea_redirects_to_first_etiquetadora_line(): void
    {
        $user = User::factory()->create();
        [$linea] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.historial'));

        $response->assertRedirect(route('analisis-etiquetadora.historial', [
            'linea_id' => $linea->id,
        ]));
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

    public function test_historial_keeps_same_component_separated_by_machine(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);

        foreach (['A', 'B'] as $maquina) {
            Componente::create([
                'codigo' => 'ETQ_L04_' . $maquina . '_COMPONENTE_IGUAL',
                'nombre' => 'Componente compartido',
                'linea' => $linea->nombre,
                'reductor' => EtiquetadoraCatalog::maquinaLabel($maquina),
                'ubicacion' => 'Modulo comun',
                'grupo' => 'Modulo comun',
                'mecanismo' => 'Mecanismo comun',
                'cantidad_total' => 4,
                'cantidad_original' => '4*maquina',
                'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
                'activo' => true,
            ]);
        }

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $linea->id,
        ]));

        $response->assertOk();

        $estadisticas = collect($response->viewData('estadisticasHistorico'));

        $this->assertCount(2, $estadisticas);
        $maquinasEncontradas = $estadisticas
            ->pluck('maquinas')
            ->map(fn ($maquinas) => $maquinas->all())
            ->sortBy(fn (array $maquinas) => $maquinas[0] ?? '')
            ->values()
            ->all();

        $this->assertSame([['A'], ['B']], $maquinasEncontradas);
    }

    public function test_historial_keeps_line_specific_components_only_on_matching_line(): void
    {
        $user = User::factory()->create();
        $lineaL04 = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);
        $lineaL13 = Linea::create([
            'nombre' => 'L-13',
            'descripcion' => 'Linea 13 Etiquetadora',
            'activo' => true,
        ]);
        $grupoLinea13 = 'Linea 13,ETIQUETA DE PLASTICO';

        $componenteIncorrectoL04 = Componente::create([
            'codigo' => 'ETQ_L04_A_GOMA_PLATO_INCORRECTA_HISTORIAL',
            'nombre' => 'goma del plato',
            'linea' => $lineaL04->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => $grupoLinea13,
            'grupo' => $grupoLinea13,
            'mecanismo' => $grupoLinea13,
            'cantidad_total' => 24,
            'cantidad_original' => '24*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $componenteL13 = Componente::create([
            'codigo' => 'ETQ_L13_A_GOMA_PLATO_HISTORIAL',
            'nombre' => 'goma del plato',
            'linea' => $lineaL13->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => $grupoLinea13,
            'grupo' => $grupoLinea13,
            'mecanismo' => $grupoLinea13,
            'cantidad_total' => 54,
            'cantidad_original' => '54*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $lineaL04->id,
            'componente_id' => $componenteIncorrectoL04->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-L04-L13-MEZCLADA',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro que no debe aparecer en L-04',
            'total_componentes' => 24,
            'cantidad_componentes_revisados' => 1,
            'componentes_revisados' => [1],
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $lineaL13->id,
            'componente_id' => $componenteL13->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-21',
            'numero_orden' => 'OT-L13-CORRECTA',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro correcto de Linea 13',
            'total_componentes' => 54,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        $responseL04 = $this->actingAs($user)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $lineaL04->id,
            'maquina' => 'A',
        ]));

        $responseL04->assertOk();
        $responseL04->assertDontSee('goma del plato');
        $responseL04->assertDontSee($grupoLinea13);
        $responseL04->assertDontSee('OT-L04-L13-MEZCLADA');
        $this->assertCount(0, collect($responseL04->viewData('estadisticasHistorico')));
        $this->assertSame(0, $responseL04->viewData('resumenHistorico')['total_general']);

        $responseL13 = $this->actingAs($user)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $lineaL13->id,
            'maquina' => 'A',
        ]));

        $responseL13->assertOk();
        $responseL13->assertSee('goma del plato');
        $responseL13->assertSeeText('ETIQUETA DE PLASTICO');
        $responseL13->assertDontSeeText($grupoLinea13);
        $this->assertSame(
            [$componenteL13->id],
            collect($responseL13->viewData('estadisticasHistorico'))
                ->pluck('detalle_componentes')
                ->flatten(1)
                ->pluck('componente_id')
                ->all()
        );
    }

    public function test_detail_modal_history_uses_analysis_history_view(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-ETQ-A-001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => "Primer registro\r\ndel historial",
            'usuario_id' => $user->id,
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        $ultimoAnalisis = AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-12',
            'numero_orden' => 'OT-ETQ-A-002',
            'estado' => AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Segundo registro del historial',
            'usuario_id' => $user->id,
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [3, 4],
        ]);

        $params = [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'maquina' => 'A',
        ];

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', array_merge($params, [
            'open_analysis_id' => $ultimoAnalisis->id,
        ])));

        $response->assertOk();
        $this->assertSame(
            route('analisis-etiquetadora.historial-analisis', $params, false),
            $response->viewData('openAnalysisData')['historial_url']
        );

        $renderedHistoryUrl = str_replace(
            ['/', '&'],
            ['\/', '\u0026'],
            route('analisis-etiquetadora.historial-analisis', $params)
        );
        $renderedReviewedUrl = str_replace(
            ['/', '&'],
            ['\/', '\u0026'],
            route('analisis-etiquetadora.historial', $params)
        );

        $this->assertStringContainsString($renderedHistoryUrl, $response->getContent());
        $this->assertStringNotContainsString($renderedReviewedUrl, $response->getContent());

        $historyResponse = $this->actingAs($user)->get(route('analisis-etiquetadora.historial-analisis', $params));
        $historyResponse->assertOk();
        $historyResponse->assertViewIs('etiquetadora.analisis-etiquetadora.historial-analisis');
        $historyResponse->assertSee('Historial del Analisis');
        $historyResponse->assertSee('PRIMER REGISTRO DEL HISTORIAL');
        $historyResponse->assertSee('OT-ETQ-A-001');
        $historyResponse->assertSee('OT-ETQ-A-002');
        $this->assertStringNotContainsString($renderedReviewedUrl, $historyResponse->getContent());
    }

    public function test_store_blocks_duplicate_pieces_inside_active_etiquetadora_cycle(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-PARCIAL',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Avance inicial',
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        $response = $this->actingAs($user)->post(route('analisis-etiquetadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-21',
            'numero_orden' => '25252525',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Intento de repetir pieza',
            'componentes_revisados' => [2, 3],
        ]);

        $response->assertSessionHasErrors('componentes_revisados');
        $this->assertDatabaseMissing('analisis_etiquetadora', [
            'numero_orden' => '25252525',
        ]);
        $this->assertSame(
            [3, 4],
            AnalisisEtiquetadora::getPiezasDisponiblesParaRegistro($linea->id, $componente->id, 'A', null, 4)
        );
    }

    public function test_completed_etiquetadora_cycle_allows_starting_a_new_cycle(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        foreach ([[1, 2], [3, 4]] as $index => $piezas) {
            AnalisisEtiquetadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $componente->id,
                'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
                'maquina' => 'A',
                'fecha_analisis' => '2026-07-' . (20 + $index),
                'numero_orden' => 'OT-CICLO-' . ($index + 1),
                'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
                'actividad' => 'Cierre de ciclo',
                'total_componentes' => 4,
                'cantidad_componentes_revisados' => count($piezas),
                'componentes_revisados' => $piezas,
            ]);
        }

        $resumenCompletado = AnalisisEtiquetadora::getResumenCicloComponente($linea->id, $componente->id, 'A', null, 4);
        $this->assertFalse($resumenCompletado['tiene_ciclo_activo']);
        $this->assertSame([1, 2, 3, 4], AnalisisEtiquetadora::getPiezasDisponiblesParaRegistro($linea->id, $componente->id, 'A', null, 4));

        $response = $this->actingAs($user)->post(route('analisis-etiquetadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-22',
            'numero_orden' => '26000001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Inicio de nuevo ciclo',
            'componentes_revisados' => [1],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $resumenNuevo = AnalisisEtiquetadora::getResumenCicloComponente($linea->id, $componente->id, 'A', null, 4);
        $this->assertTrue($resumenNuevo['tiene_ciclo_activo']);
        $this->assertSame([1], $resumenNuevo['resumen_actual']['piezas_revisadas']);
        $this->assertSame([2, 3, 4], $resumenNuevo['resumen_actual']['piezas_pendientes']);
    }

    public function test_index_action_switches_between_continue_and_new_record_by_cycle_state(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-PARCIAL',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Avance inicial',
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('Continuar');
        $response->assertDontSee('Nuevo Registro');

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-21',
            'numero_orden' => 'OT-CIERRE',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Cierre de ciclo',
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [3, 4],
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('Nuevo Registro');
        $response->assertDontSee('Continuar');
    }

    public function test_index_renders_line_specific_labeling_process_diagram(): void
    {
        $user = User::factory()->create();
        $this->crearCatalogoEtiquetadora();
        $lineaL05 = Linea::create([
            'nombre' => 'L-05',
            'descripcion' => 'Linea 05 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L05_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora L05',
            'linea' => $lineaL05->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $lineaL05->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('class="etq-process-diagram"', false);
        $response->assertSee('data-etq-process-line="L-05"', false);
        $response->assertSee('data-etq-process-code="05"', false);
        $response->assertDontSee('data-etq-process-line="L-04"', false);

        $html = $response->getContent();
        $diagramStart = strpos($html, 'data-etq-process-line="L-05"');
        $diagramEnd = strpos($html, '</section>', $diagramStart);
        $diagramHtml = substr($html, $diagramStart, $diagramEnd - $diagramStart);

        $this->assertStringContainsString('SoloEtiquetas/linea05-victoria-cuarto.png', $diagramHtml);
        $this->assertStringContainsString('Botellas/linea05-victoria-cuarto-botella-tight.png', $diagramHtml);
        $this->assertStringContainsString('Botellas/linea05-victoria-cuarto-etiquetada-tight.png', $diagramHtml);
        $this->assertStringNotContainsString('coronamega.png', $diagramHtml);
        $this->assertStringNotContainsString('etq-process-heading', $diagramHtml);
        $this->assertStringNotContainsString('etq-process-legend', $diagramHtml);
        $this->assertStringNotContainsString('Entrada sin etiqueta', $diagramHtml);
        $this->assertStringNotContainsString('Salida etiquetada', $diagramHtml);
        $this->assertStringNotContainsString('Botella sin etiqueta', $diagramHtml);
        $this->assertStringNotContainsString('Proceso de etiquetado', $diagramHtml);
    }

    public function test_etiquetadora_catalog_resolves_presentations_by_line(): void
    {
        $lineaL04 = new Linea(['nombre' => 'L-04']);
        $lineaL05 = new Linea(['nombre' => 'L-05']);
        $lineaL10 = new Linea(['nombre' => 'L-10']);
        $lineaL12 = new Linea(['nombre' => 'L-12']);
        $lineaL13 = new Linea(['nombre' => 'L-13']);

        $presentacionesL04 = collect(EtiquetadoraCatalog::presentacionesPorLinea($lineaL04))->pluck('label');
        $presentacionesL05 = collect(EtiquetadoraCatalog::presentacionesPorLinea($lineaL05))->pluck('label');
        $presentacionesL10 = collect(EtiquetadoraCatalog::presentacionesPorLinea($lineaL10));
        $presentacionesL12 = collect(EtiquetadoraCatalog::presentacionesPorLinea($lineaL12));
        $presentacionesL13 = collect(EtiquetadoraCatalog::presentacionesPorLinea($lineaL13));
        $presentacionesL06 = collect(EtiquetadoraCatalog::presentacionesPorLinea('L-06'));

        $this->assertSame('04', EtiquetadoraCatalog::normalizarCodigoLinea('Linea-04'));
        $this->assertTrue($presentacionesL04->contains('Corona Mega'));
        $this->assertFalse($presentacionesL04->contains('Pacifico Clara'));
        $this->assertTrue($presentacionesL05->contains('Victoria Cuarto'));
        $this->assertFalse($presentacionesL05->contains('Corona Mega'));
        $this->assertSame(['Barrilito'], $presentacionesL10->pluck('label')->all());
        $this->assertSame('barrilito-real', $presentacionesL10->first()['botella']['forma']);
        $this->assertSame(['Modelo Especial', 'Modelito Especial', 'Negra Modelo'], $presentacionesL12->pluck('label')->all());
        $this->assertSame('modelito-especial-210ml-real', $presentacionesL12->firstWhere('label', 'Modelito Especial')['botella']['forma']);
        $this->assertSame('dark', $presentacionesL12->firstWhere('label', 'Negra Modelo')['botella']['tono']);
        $this->assertSame(['Michelob Ultra', 'Pacifico Clara'], $presentacionesL13->pluck('label')->all());
        $this->assertSame('Botellas/linea13-botella-compartida-tight.png', $presentacionesL13->firstWhere('label', 'Michelob Ultra')['botella']['image']);
        $this->assertSame('Botellas/linea13-botella-compartida-tight.png', $presentacionesL13->firstWhere('label', 'Pacifico Clara')['botella']['image']);
        $this->assertFalse($presentacionesL13->pluck('label')->contains('Corona Mega'));
        $this->assertSame(4, $presentacionesL06->count());
        $this->assertSame(['Negra Modelo', 'Modelo Especial', 'Corona Extra', 'Bud Light'], $presentacionesL06->pluck('label')->all());
        $this->assertSame('amber', $presentacionesL06->firstWhere('label', 'Corona Extra')['botella']['tono']);
        $this->assertSame('dark', $presentacionesL06->firstWhere('label', 'Negra Modelo')['botella']['tono']);
        $this->assertSame('blue', $presentacionesL06->firstWhere('label', 'Bud Light')['botella']['tapa']);
    }

    public function test_index_renders_line_four_with_real_bottle_and_real_labels(): void
    {
        $user = User::factory()->create();
        [$linea] = $this->crearCatalogoEtiquetadora();

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('data-etq-process-code="04"', false);
        $response->assertSee('data-etq-presentations-count="2"', false);
        $response->assertSee('data-etq-flow-count="4"', false);
        $response->assertDontSee('Entrada sin etiqueta');
        $response->assertDontSee('Salida etiquetada');
        $response->assertDontSee('Botella sin etiqueta');
        $response->assertDontSee('Proceso de etiquetado');
        $response->assertSee('Botellas/linea04-mega-botella.png');
        $response->assertSee('Botellas/linea04-corona-mega-etiquetada.png');
        $response->assertSee('Botellas/linea04-victoria-mega-etiquetada.png');
        $response->assertSee('SoloEtiquetas/linea04-corona-mega.png');
        $response->assertSee('SoloEtiquetas/linea04-victoria-mega.png');
        $response->assertSee('etq-process-label-head--carousel', false);
        $response->assertSee('etq-process-label-head-stack', false);
        $response->assertSee('etq-process-label-head-item', false);
        $response->assertSee('etq-process-label-head-image', false);
        $response->assertDontSee('etq-process-label-feed', false);
        $response->assertSee('etq-process-bottle--photo', false);
        $response->assertSee('etq-process-bottle--moving', false);
        $response->assertSee('data-etq-shape="mega-real"', false);
    }

    public function test_index_renders_line_ten_with_real_barrilito_bottle(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-10',
            'descripcion' => 'Linea 10 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L10_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora L10',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('data-etq-process-code="10"', false);
        $response->assertSee('data-etq-presentations-count="1"', false);
        $response->assertSee('data-etq-flow-count="3"', false);
        $response->assertSee('data-etq-label-count="1"', false);
        $response->assertSee('etq-process-label-head--static', false);
        $response->assertSee('SoloEtiquetas/linea10-barrilito.png');
        $response->assertSee('Botellas/linea10-barrilito-botella-tight.png');
        $response->assertSee('Botellas/linea10-barrilito-etiquetada-tight.png');
        $response->assertSee('data-etq-shape="barrilito-real"', false);
        $response->assertSee('data-etq-tone="amber"', false);
        $response->assertSee('etq-process-bottle--cap-gold', false);
    }

    public function test_index_renders_line_twelve_with_three_real_modelo_bottles(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-12',
            'descripcion' => 'Linea 12 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L12_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora L12',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('data-etq-process-code="12"', false);
        $response->assertSee('data-etq-presentations-count="3"', false);
        $response->assertSee('data-etq-flow-count="3"', false);
        $response->assertSee('data-etq-label-count="3"', false);
        $response->assertSee('etq-process-label-head--carousel', false);
        $response->assertSee('SoloEtiquetas/linea12-modelo-especial-355ml.png');
        $response->assertSee('SoloEtiquetas/linea12-modelito-especial-210ml.png');
        $response->assertSee('SoloEtiquetas/linea12-negra-modelo-355ml.png');
        $response->assertSee('Botellas/linea12-modelo-especial-355ml-botella-tight.png');
        $response->assertSee('Botellas/linea12-modelito-especial-210ml-botella-tight.png');
        $response->assertSee('Botellas/linea12-negra-modelo-355ml-botella-tight.png');
        $response->assertSee('Botellas/linea12-modelo-especial-355ml-etiquetada-tight.png');
        $response->assertSee('Botellas/linea12-modelito-especial-210ml-etiquetada-tight.png');
        $response->assertSee('Botellas/linea12-negra-modelo-355ml-etiquetada-tight.png');
        $response->assertSee('data-etq-shape="modelo-especial-355ml-real"', false);
        $response->assertSee('data-etq-shape="modelito-especial-210ml-real"', false);
        $response->assertSee('data-etq-shape="negra-modelo-355ml-real"', false);
        $response->assertSee('data-etq-tone="gold-clear"', false);
        $response->assertSee('data-etq-tone="dark"', false);
    }

    public function test_index_renders_line_thirteen_with_shared_bottle_and_real_labels(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-13',
            'descripcion' => 'Linea 13 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L13_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora L13',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('data-etq-process-code="13"', false);
        $response->assertSee('data-etq-presentations-count="2"', false);
        $response->assertSee('data-etq-flow-count="4"', false);
        $response->assertSee('data-etq-label-count="2"', false);
        $response->assertSee('etq-process-label-head--carousel', false);
        $response->assertSee('SoloEtiquetas/linea13-michelob-ultra.png');
        $response->assertSee('SoloEtiquetas/linea13-pacifico-clara.png');
        $response->assertSee('Botellas/linea13-botella-compartida-tight.png');
        $response->assertSee('Botellas/linea13-michelob-ultra-etiquetada-tight.png');
        $response->assertSee('Botellas/linea13-pacifico-clara-etiquetada-tight.png');
        $response->assertSee('data-etq-shape="michelob-ultra-real"', false);
        $response->assertSee('data-etq-shape="pacifico-clara-real"', false);
        $response->assertSee('data-etq-tone="amber"', false);
        $response->assertSee('etq-process-bottle--cap-gold', false);
    }

    public function test_index_renders_all_bottles_for_multi_presentation_line(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-06',
            'descripcion' => 'Linea 06 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L06_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora L06',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('data-etq-process-code="06"', false);
        $response->assertSee('data-etq-presentations-count="4"', false);
        $response->assertSee('data-etq-flow-count="4"', false);
        $response->assertSee('data-etq-label-count="4"', false);
        $response->assertSee('--etq-process-duration: 12.00s', false);
        $response->assertSee('data-etq-sequence="1"', false);
        $response->assertSee('data-etq-sequence="2"', false);
        $response->assertSee('data-etq-sequence="3"', false);
        $response->assertSee('data-etq-sequence="4"', false);
        $response->assertSee('--etq-delay: 0.00s', false);
        $response->assertSee('--etq-delay: 3.00s', false);
        $response->assertSee('--etq-delay: 6.00s', false);
        $response->assertSee('--etq-delay: 9.00s', false);
        $response->assertSee('--etq-label-delay: 5.04s', false);
        $response->assertSee('--etq-label-delay: 8.04s', false);
        $response->assertSee('--etq-label-delay: 11.04s', false);
        $response->assertSee('--etq-label-delay: 14.04s', false);
        $response->assertDontSee('--etq-delay: -', false);
        $response->assertSee('etq-process-label-head--carousel', false);
        $response->assertSee('SoloEtiquetas/linea06-modelo-negra-grande.png');
        $response->assertSee('SoloEtiquetas/linea06-modelo-especial-grande.png');
        $response->assertSee('SoloEtiquetas/linea06-corona-extra-grande.png');
        $response->assertSee('SoloEtiquetas/linea06-bud-light-grande.png');
        $response->assertSee('Botellas/linea06-modelo-negra-grande-botella-tight.png');
        $response->assertSee('Botellas/linea06-modelo-especial-grande-botella-tight.png');
        $response->assertSee('Botellas/linea06-corona-extra-grande-botella-tight.png');
        $response->assertSee('Botellas/linea06-bud-light-grande-botella-tight.png');
        $response->assertSee('Botellas/linea06-modelo-negra-grande-etiquetada-tight.png');
        $response->assertSee('Botellas/linea06-modelo-especial-grande-etiquetada-tight.png');
        $response->assertSee('Botellas/linea06-corona-extra-grande-etiquetada-tight.png');
        $response->assertSee('Botellas/linea06-bud-light-grande-etiquetada-tight.png');
        $response->assertSee('data-etq-shape="modelo-negra-grande-real"', false);
        $response->assertSee('data-etq-shape="modelo-especial-grande-real"', false);
        $response->assertSee('data-etq-shape="corona-extra-grande-real"', false);
        $response->assertSee('data-etq-shape="bud-light-grande-real"', false);
        $response->assertSee('data-etq-tone="gold-clear"', false);
        $response->assertSee('data-etq-tone="dark"', false);
        $response->assertSee('etq-process-bottle--cap-blue', false);
    }

    public function test_index_hides_piece_quantities_and_progress_summary_for_multi_piece_components(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-MULTI',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Revision parcial sin grafica de avance',
            'total_componentes' => 4,
            'cantidad_componentes_revisados' => 2,
            'componentes_revisados' => [1, 2],
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('OT-MULTI');
        $response->assertDontSee('4*maquina');
        $response->assertDontSee('2/4');
        $response->assertDontSee('Pendientes: #3, #4');
        $response->assertDontSee('Ciclo completado');
        $response->assertDontSee('h-1.5 overflow-hidden rounded-full bg-slate-200', false);
    }

    public function test_index_hides_quantities_when_component_has_no_analysis(): void
    {
        $user = User::factory()->create();
        [$linea] = $this->crearCatalogoEtiquetadora(cantidadTotal: 4);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('Sin analisis');
        $response->assertDontSee('0 analisis');
        $response->assertDontSee('0/');
        $response->assertDontSee('4*maquina');
        $response->assertDontSee('4 por maquina');
    }

    public function test_index_ignores_components_and_records_from_another_etiquetadora_line(): void
    {
        $user = User::factory()->create();
        [$linea, $componente] = $this->crearCatalogoEtiquetadora();
        $lineaAjena = Linea::create([
            'nombre' => 'L-05',
            'descripcion' => 'Linea ajena Etiquetadora',
            'activo' => true,
        ]);
        $componenteAjeno = Componente::create([
            'codigo' => 'ETQ_L05_A_AJENO',
            'nombre' => 'Componente Ajeno',
            'linea' => $lineaAjena->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo ajeno',
            'grupo' => 'Grupo ajeno',
            'mecanismo' => 'Mecanismo ajeno',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteAjeno->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-MEZCLADA',
            'estado' => AnalisisEtiquetadora::ESTADO_DANADO,
            'actividad' => 'Registro con componente de otra linea',
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee($componente->nombre);
        $response->assertDontSee('Componente Ajeno');
        $response->assertDontSee('OT-MEZCLADA');
        $response->assertDontSee('1 analisis');

        $tablaLineas = $response->viewData('tablaLineas');
        $this->assertCount(1, $tablaLineas);
        $this->assertSame($linea->id, $tablaLineas[0]['linea']->id);
        $this->assertSame(0, $tablaLineas[0]['analisis_count']);
        $this->assertSame(
            [$componente->nombre],
            collect($tablaLineas[0]['componentes'])->pluck('nombre')->all()
        );
        $this->assertSame(0, $response->viewData('estadisticas')['total']);
        $this->assertSame([], $response->viewData('estadoModalItems')['total']);
    }

    public function test_index_keeps_line_specific_components_only_on_matching_line_and_trims_redundant_line_prefix(): void
    {
        $user = User::factory()->create();
        $lineaL04 = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);
        $lineaL13 = Linea::create([
            'nombre' => 'L-13',
            'descripcion' => 'Linea 13 Etiquetadora',
            'activo' => true,
        ]);
        $grupoLinea13 = 'Linea 13,ETIQUETA DE PLASTICO';

        Componente::create([
            'codigo' => 'ETQ_L04_A_GOMA_PLATO_INCORRECTA',
            'nombre' => 'goma del plato',
            'linea' => $lineaL04->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => $grupoLinea13,
            'grupo' => $grupoLinea13,
            'mecanismo' => $grupoLinea13,
            'cantidad_total' => 24,
            'cantidad_original' => '24*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $componenteL13 = Componente::create([
            'codigo' => 'ETQ_L13_A_GOMA_PLATO',
            'nombre' => 'goma del plato',
            'linea' => $lineaL13->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => $grupoLinea13,
            'grupo' => $grupoLinea13,
            'mecanismo' => $grupoLinea13,
            'cantidad_total' => 54,
            'cantidad_original' => '54*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $responseL04 = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $lineaL04->id,
            'maquina' => 'A',
        ]));

        $responseL04->assertOk();
        $responseL04->assertDontSee('goma del plato');
        $responseL04->assertDontSee($grupoLinea13);
        $this->assertSame([], collect($responseL04->viewData('tablaLineas'))->pluck('componentes')->flatten(1)->pluck('nombre')->all());

        $responseL13 = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $lineaL13->id,
            'maquina' => 'A',
        ]));

        $responseL13->assertOk();
        $responseL13->assertSee('goma del plato');
        $responseL13->assertSeeText('ETIQUETA DE PLASTICO');
        $responseL13->assertDontSeeText($grupoLinea13);
        $this->assertTrue(collect($responseL13->viewData('grupos'))->contains($grupoLinea13));
        $this->assertSame(
            [$componenteL13->id],
            collect($responseL13->viewData('tablaLineas'))->pluck('componentes')->flatten(1)->pluck('por_maquina')->pluck('A')->pluck('id')->all()
        );
    }

    public function test_index_hides_presentation_group_label_for_platos_giratorios(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);

        Componente::create([
            'codigo' => 'ETQ_L04_A_PLATOS_GIRATORIOS',
            'nombre' => 'Platos giratorios',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'PRESENTACION POR LINEAS:',
            'grupo' => 'PRESENTACION POR LINEAS:',
            'mecanismo' => 'PRESENTACION POR LINEAS:',
            'cantidad_total' => 24,
            'cantidad_original' => '24*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $response->assertOk();
        $response->assertSee('Platos giratorios');
        $response->assertDontSee('PRESENTACION POR LINEAS');
        $this->assertFalse(collect($response->viewData('grupos'))->contains('PRESENTACION POR LINEAS:'));
    }

    public function test_catalog_expands_tulipa_and_goma_as_independent_components(): void
    {
        $rows = collect(EtiquetadoraCatalog::expandedComponentRows())
            ->where('linea', 'L-04')
            ->where('maquina', 'A');

        $tulipa = $rows->firstWhere('nombre', 'Tulipa');
        $goma = $rows->firstWhere('nombre', 'Goma');

        $this->assertNotNull($tulipa);
        $this->assertNotNull($goma);
        $this->assertSame(24, $tulipa['cantidad_total']);
        $this->assertSame(24, $goma['cantidad_total']);
        $this->assertSame('24*maquina', $tulipa['cantidad_original']);
        $this->assertSame('24*maquina', $goma['cantidad_original']);
        $this->assertSame(
            EtiquetadoraCatalog::codigo('L-04', 'A', $tulipa['grupo'], 'Tulipa y goma'),
            $tulipa['codigo']
        );
        $this->assertNotSame($tulipa['codigo'], $goma['codigo']);
        $this->assertFalse($rows->contains(fn (array $row): bool => $row['nombre'] === 'Tulipa y goma'));
    }

    public function test_split_migration_preserves_legacy_tulipa_history_and_creates_goma_component(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);
        $legacyRow = $this->catalogRowFor('Tulipa', $linea->nombre, 'A');
        $legacyComponent = $this->createCatalogComponentFromRow($legacyRow, [
            'nombre' => 'Tulipa y goma',
            'cantidad_original' => '24*maquina',
        ]);
        $legacyAnalysis = AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $legacyComponent->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-08-01',
            'numero_orden' => '11111111',
            'estado' => AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Revision historica combinada',
            'total_componentes' => 24,
            'cantidad_componentes_revisados' => 1,
            'componentes_revisados' => [1],
        ]);

        $migration = require database_path('migrations/2026_09_04_000001_split_etiquetadora_tulipa_goma_catalog.php');
        $migration->up();

        $legacyComponent->refresh();
        $gomaRow = $this->catalogRowFor('Goma', $linea->nombre, 'A');
        $gomaComponent = Componente::where('codigo', $gomaRow['codigo'])->first();

        $this->assertSame('Tulipa', $legacyComponent->nombre);
        $this->assertSame('24*maquina', $legacyComponent->cantidad_original);
        $this->assertTrue($legacyComponent->activo);
        $this->assertSame($legacyComponent->id, $legacyAnalysis->fresh()->componente_id);
        $this->assertNotNull($gomaComponent);
        $this->assertSame('Goma', $gomaComponent->nombre);
        $this->assertTrue($gomaComponent->activo);
        $this->assertNotSame($legacyComponent->id, $gomaComponent->id);
        $this->assertFalse(AnalisisEtiquetadora::where('componente_id', $gomaComponent->id)->exists());
    }

    public function test_index_store_historial_and_filters_keep_tulipa_and_goma_independent(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea 04 Etiquetadora',
            'activo' => true,
        ]);
        [$tulipa, $goma] = $this->createTulipaAndGomaCatalog($linea, 'A');

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $tulipa->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-08-02',
            'numero_orden' => '22222222',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Revision parcial de tulipa',
            'usuario_id' => $user->id,
            'total_componentes' => 24,
            'cantidad_componentes_revisados' => 1,
            'componentes_revisados' => [1],
        ]);

        $storeResponse = $this->actingAs($user)->post(route('analisis-etiquetadora.store'), [
            'linea_id' => $linea->id,
            'componente_id' => $goma->id,
            'maquina' => 'A',
            'fecha_analisis' => '2026-08-03',
            'numero_orden' => '33333333',
            'estado' => AnalisisEtiquetadora::ESTADO_DANADO,
            'actividad' => 'Revision independiente de goma',
            'componentes_revisados' => [1],
        ]);

        $storeResponse->assertRedirect(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));
        $this->assertDatabaseHas('analisis_etiquetadora', [
            'componente_id' => $goma->id,
            'numero_orden' => '33333333',
            'maquina' => 'A',
            'estado' => AnalisisEtiquetadora::ESTADO_DANADO,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('analisis-etiquetadora.index', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Tulipa');
        $indexResponse->assertSee('Goma');
        $indexResponse->assertDontSee('Tulipa y goma');

        $componentesTabla = collect($indexResponse->viewData('tablaLineas'))
            ->pluck('componentes')
            ->flatten(1);

        $this->assertSameCanonicalizing(['Goma', 'Tulipa'], $componentesTabla->pluck('nombre')->all());
        $this->assertSame($tulipa->id, $componentesTabla->firstWhere('nombre', 'Tulipa')['por_maquina']['A']->id);
        $this->assertSame($goma->id, $componentesTabla->firstWhere('nombre', 'Goma')['por_maquina']['A']->id);

        $estadoItems = collect($indexResponse->viewData('estadoModalItems')['total'] ?? []);
        $this->assertSameCanonicalizing(['Goma', 'Tulipa'], $estadoItems->pluck('componente')->all());
        $this->assertSame(
            AnalisisEtiquetadora::ESTADO_BUENO,
            $estadoItems->firstWhere('componente', 'Tulipa')['estado']
        );
        $this->assertSame(
            AnalisisEtiquetadora::ESTADO_DANADO,
            $estadoItems->firstWhere('componente', 'Goma')['estado']
        );

        $historialResponse = $this->actingAs($user)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
        ]));

        $historialResponse->assertOk();
        $estadisticasHistorico = collect($historialResponse->viewData('estadisticasHistorico'));
        $this->assertSameCanonicalizing(['Goma', 'Tulipa'], $estadisticasHistorico->pluck('nombre')->all());
        $this->assertSame(
            [$tulipa->id],
            collect($estadisticasHistorico->firstWhere('nombre', 'Tulipa')['detalle_componentes'])->pluck('componente_id')->all()
        );
        $this->assertSame(
            [$goma->id],
            collect($estadisticasHistorico->firstWhere('nombre', 'Goma')['detalle_componentes'])->pluck('componente_id')->all()
        );

        $filteredHistorialResponse = $this->actingAs($user)->get(route('analisis-etiquetadora.historial', [
            'linea_id' => $linea->id,
            'maquina' => 'A',
            'componente_id' => $tulipa->id,
        ]));

        $filteredHistorialResponse->assertOk();
        $this->assertSame(
            ['Tulipa'],
            collect($filteredHistorialResponse->viewData('estadisticasHistorico'))->pluck('nombre')->all()
        );
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

    private function catalogRowFor(string $nombre, string $linea = 'L-04', string $maquina = 'A'): array
    {
        $row = collect(EtiquetadoraCatalog::expandedComponentRows())
            ->first(fn (array $row): bool => $row['nombre'] === $nombre
                && $row['linea'] === $linea
                && $row['maquina'] === $maquina);

        $this->assertNotNull($row, "No existe el componente {$nombre} para {$linea} maquina {$maquina} en el catalogo.");

        return $row;
    }

    /**
     * @return array{0: Componente, 1: Componente}
     */
    private function createTulipaAndGomaCatalog(Linea $linea, string $maquina = 'A'): array
    {
        return [
            $this->createCatalogComponentFromRow($this->catalogRowFor('Tulipa', $linea->nombre, $maquina)),
            $this->createCatalogComponentFromRow($this->catalogRowFor('Goma', $linea->nombre, $maquina)),
        ];
    }

    private function createCatalogComponentFromRow(array $row, array $overrides = []): Componente
    {
        return Componente::create(array_merge([
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'linea' => $row['linea'],
            'reductor' => $row['maquina_label'],
            'ubicacion' => $row['grupo'],
            'grupo' => $row['grupo'],
            'mecanismo' => $row['mecanismo'],
            'cantidad_total' => $row['cantidad_total'],
            'cantidad_original' => $row['cantidad_original'],
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ], $overrides));
    }
}
