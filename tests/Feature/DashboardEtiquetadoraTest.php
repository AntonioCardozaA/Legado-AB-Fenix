<?php

namespace Tests\Feature;

use App\Models\AnalisisEtiquetadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEtiquetadoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_marks_catalog_machines_without_analysis_as_sin_datos(): void
    {
        $user = User::factory()->create();

        Linea::whereIn('nombre', EtiquetadoraCatalog::lineas())->update(['activo' => false]);
        Componente::where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)->update(['activo' => false]);

        $linea = Linea::updateOrCreate(
            ['nombre' => 'L-04'],
            [
                'descripcion' => 'Linea de prueba Etiquetadora',
                'activo' => true,
            ]
        );
        $componenteA = $this->crearComponenteEtiquetadora($linea, 'A');
        $this->crearComponenteEtiquetadora($linea, 'B');

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteA->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => 'OT-ETQ-001',
            'estado' => AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Revision de prueba',
            'total_componentes' => 2,
            'cantidad_componentes_revisados' => 1,
            'componentes_revisados' => [1],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.global.etiquetadoras'));

        $response->assertOk();

        $estado = collect($response->viewData('estadoEtiquetadoras'))->keyBy('id');
        $resumen = $response->viewData('resumenEtiquetadora');

        $this->assertSame('operativo', $estado->get($linea->id . '-A')['estado']['nivel']);
        $this->assertSame('sin_datos', $estado->get($linea->id . '-B')['estado']['nivel']);
        $this->assertSame('Sin analisis registrados para esta etiquetadora.', $estado->get($linea->id . '-B')['estado']['mensaje']);
        $this->assertSame(2, $resumen['total_etiquetadoras']);
        $this->assertSame(1, $resumen['equipos_con_analisis']);
        $this->assertSame(1, $resumen['equipos_sin_analisis']);
    }

    public function test_dashboard_ignores_line_specific_components_from_other_etiquetadora_lines(): void
    {
        $user = User::factory()->create();

        Linea::whereIn('nombre', EtiquetadoraCatalog::lineas())->update(['activo' => false]);
        Componente::where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)->update(['activo' => false]);

        $lineaL04 = Linea::updateOrCreate(
            ['nombre' => 'L-04'],
            ['descripcion' => 'Linea 04 Etiquetadora', 'activo' => true]
        );
        $grupoLinea13 = 'Linea 13,ETIQUETA DE PLASTICO';

        Componente::create([
            'codigo' => 'ETQ_L04_A_GOMA_PLATO_DASHBOARD_INVALIDA',
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

        $response = $this->actingAs($user)->get(route('dashboard.global.etiquetadoras'));

        $response->assertOk();
        $this->assertSame([], collect($response->viewData('estadoEtiquetadoras'))->all());
        $this->assertSame(0, $response->viewData('resumenEtiquetadora')['componentes_catalogo']);
    }

    private function crearComponenteEtiquetadora(Linea $linea, string $maquina): Componente
    {
        return Componente::create([
            'codigo' => 'ETQ_' . str_replace('-', '', $linea->nombre) . '_' . $maquina . '_PRUEBA',
            'nombre' => 'Componente Etiquetadora ' . $maquina,
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel($maquina),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 2,
            'cantidad_original' => '2*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);
    }
}
