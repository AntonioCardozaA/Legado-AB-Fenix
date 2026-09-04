<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Services\LavadoraRevisionGeneralService;
use App\Services\LavadoraRevisionPeriodicityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisLavadoraRevisionGeneralTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_index_shows_general_review_button_when_washer_is_selected(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $linea = $this->crearLinea('L-04');

        $response = $this->actingAs($user)->get(route('analisis-lavadora.index', [
            'linea_id' => $linea->id,
        ]));

        $response->assertOk();
        $response->assertSee('Revisión General de Guías y Catarinas');
        $response->assertSee(route('analisis-lavadora.revision-general.create', ['linea' => $linea->id]), false);
    }

    public function test_general_review_form_shows_separate_forms_by_component_type(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $linea = $this->crearLinea('L-04');

        $response = $this->actingAs($user)->get(route('analisis-lavadora.revision-general.create', [
            'linea' => $linea->id,
        ]));

        $response->assertOk();

        foreach (LavadoraRevisionGeneralService::COMPONENTES_REVISION_GENERAL as $codigoBase) {
            $response->assertSee('value="' . $codigoBase . '"', false);
        }

        $response->assertSee('Guardar Guia Inferior');
        $response->assertSee('Guardar Guia Intermedia');
        $response->assertSee('Guardar Guia Superior');
        $response->assertSee('Guardar Catarinas');
    }

    public function test_general_review_creates_good_state_records_only_for_the_selected_type(): void
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow('2026-08-28 08:00:00');

        $user = User::factory()->create();
        $linea = $this->crearLinea('L-04');
        $expectedCount = LavadoraRevisionPeriodicityService::CANTIDADES_POR_LADO['L-04'] * 2;

        $response = $this->actingAs($user)->post(
            route('analisis-lavadora.revision-general.store', ['linea' => $linea->id]),
            [
                'codigo_base' => 'GUI_INF_TANQUE',
                'fecha_analisis' => '2026-08-20',
                'numero_orden' => '12345678',
            ]
        );

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));
        $response->assertSessionHas('success', sprintf(
            'Revisión general de Guia Inferior completada. %d componentes registrados en BUEN ESTADO y 0 componentes omitidos por tener una revisión vigente con otro estado.',
            $expectedCount
        ));

        $registrosInferior = AnalisisLavadora::with('componente')
            ->where('linea_id', $linea->id)
            ->where('numero_orden', '12345678')
            ->get();

        $this->assertCount($expectedCount, $registrosInferior);
        $this->assertTrue($registrosInferior->every(fn (AnalisisLavadora $analisis): bool => $analisis->usuario_id === $user->id));
        $this->assertTrue($registrosInferior->every(fn (AnalisisLavadora $analisis): bool => $analisis->estado === AnalisisLavadora::ESTADO_BUENO));
        $this->assertTrue($registrosInferior->every(fn (AnalisisLavadora $analisis): bool => $analisis->fecha_analisis?->toDateString() === '2026-08-20'));
        $this->assertTrue($registrosInferior->every(fn (AnalisisLavadora $analisis): bool => $analisis->actividad === LavadoraRevisionGeneralService::ACTIVIDAD_GUIA_BUEN_ESTADO));
        $this->assertTrue($registrosInferior->every(fn (AnalisisLavadora $analisis): bool => AnalisisLavadora::codigoBaseComponente($analisis->componente->codigo) === 'GUI_INF_TANQUE'));
        $this->assertSame($expectedCount / 2, $registrosInferior->where('lado', 'VAPOR')->count());
        $this->assertSame($expectedCount / 2, $registrosInferior->where('lado', 'PASILLO')->count());

        $this->actingAs($user)->post(
            route('analisis-lavadora.revision-general.store', ['linea' => $linea->id]),
            [
                'codigo_base' => 'CATARINAS',
                'fecha_analisis' => '2026-08-21',
                'numero_orden' => '87651234',
            ]
        )->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]))
            ->assertSessionHas('success', sprintf(
                'Revisión general de Catarinas completada. %d componentes registrados en BUEN ESTADO y 0 componentes omitidos por tener una revisión vigente con otro estado.',
                $expectedCount
            ));

        $registrosCatarinas = AnalisisLavadora::with('componente')
            ->where('linea_id', $linea->id)
            ->where('numero_orden', '87651234')
            ->get();

        $this->assertCount($expectedCount, $registrosCatarinas);
        $this->assertTrue($registrosCatarinas->every(fn (AnalisisLavadora $analisis): bool => $analisis->fecha_analisis?->toDateString() === '2026-08-21'));
        $this->assertTrue($registrosCatarinas->every(fn (AnalisisLavadora $analisis): bool => $analisis->actividad === LavadoraRevisionGeneralService::ACTIVIDAD_CATARINA_BUEN_ESTADO));
        $this->assertTrue($registrosCatarinas->every(fn (AnalisisLavadora $analisis): bool => AnalisisLavadora::codigoBaseComponente($analisis->componente->codigo) === 'CATARINAS'));

        $this->actingAs($user)->post(
            route('analisis-lavadora.revision-general.store', ['linea' => $linea->id]),
            [
                'codigo_base' => 'GUI_INF_TANQUE',
                'fecha_analisis' => '2026-08-20',
                'numero_orden' => '12345678',
            ]
        )->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]))
            ->assertSessionHas('success', sprintf(
                'Revisión general de Guia Inferior completada. 0 componentes registrados en BUEN ESTADO y 0 componentes omitidos por tener una revisión vigente con otro estado. %d registros duplicados exactos no se repitieron.',
                $expectedCount
            ));

        $this->assertSame($expectedCount * 2, AnalisisLavadora::where('linea_id', $linea->id)->count());
    }

    public function test_general_review_omits_only_the_side_with_an_active_non_good_state(): void
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow('2026-08-28 08:00:00');

        $user = User::factory()->create();
        $linea = $this->crearLinea('L-04');
        $guiaSuperior = $this->crearComponente($linea, 'GUI_SUP_TANQUE', 'Reductor 1');
        $catarina = $this->crearComponente($linea, 'CATARINAS', 'Reductor 9');

        $danoVigente = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $guiaSuperior->id,
            'reductor' => 'Reductor 1',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-08-01',
            'numero_orden' => '87654321',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Dano vigente de prueba',
            'usuario_id' => $user->id,
        ]);

        $danoVencido = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $catarina->id,
            'reductor' => 'Reductor 9',
            'lado' => 'PASILLO',
            'fecha_analisis' => '2026-01-05',
            'numero_orden' => '87654322',
            'estado' => 'Desgaste severo',
            'actividad' => 'Dano vencido de prueba',
            'usuario_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(
            route('analisis-lavadora.revision-general.store', ['linea' => $linea->id]),
            [
                'codigo_base' => 'GUI_SUP_TANQUE',
                'fecha_analisis' => '2026-08-20',
                'numero_orden' => '22334455',
            ]
        );

        $expectedCreated = (LavadoraRevisionPeriodicityService::CANTIDADES_POR_LADO['L-04'] * 2) - 1;

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));
        $response->assertSessionHas('success', sprintf(
            'Revisión general de Guia Superior completada. %d componentes registrados en BUEN ESTADO y 1 componentes omitidos por tener una revisión vigente con otro estado.',
            $expectedCreated
        ));

        $this->assertSame($expectedCreated, AnalisisLavadora::where('numero_orden', '22334455')->count());

        $this->assertDatabaseMissing('analisis_componentes', [
            'linea_id' => $linea->id,
            'componente_id' => $guiaSuperior->id,
            'reductor' => 'Reductor 1',
            'lado' => 'VAPOR',
            'numero_orden' => '22334455',
        ]);

        $this->assertDatabaseHas('analisis_componentes', [
            'linea_id' => $linea->id,
            'componente_id' => $guiaSuperior->id,
            'reductor' => 'Reductor 1',
            'lado' => 'PASILLO',
            'numero_orden' => '22334455',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
        ]);

        $this->assertDatabaseMissing('analisis_componentes', [
            'linea_id' => $linea->id,
            'componente_id' => $catarina->id,
            'reductor' => 'Reductor 9',
            'lado' => 'PASILLO',
            'numero_orden' => '22334455',
        ]);

        $ultimos = AnalisisLavadora::ultimosPorComponente()
            ->with('componente')
            ->where('linea_id', $linea->id)
            ->whereIn('reductor', ['Reductor 1', 'Reductor 9'])
            ->get();

        $ultimoGuiaVapor = $ultimos->first(
            fn (AnalisisLavadora $analisis): bool => $analisis->lado === 'VAPOR'
                && $analisis->reductor === 'Reductor 1'
                && AnalisisLavadora::codigoBaseComponente($analisis->componente->codigo) === 'GUI_SUP_TANQUE'
        );
        $ultimoCatarinaPasillo = $ultimos->first(
            fn (AnalisisLavadora $analisis): bool => $analisis->lado === 'PASILLO'
                && $analisis->reductor === 'Reductor 9'
                && AnalisisLavadora::codigoBaseComponente($analisis->componente->codigo) === 'CATARINAS'
        );

        $this->assertTrue($danoVigente->is($ultimoGuiaVapor));
        $this->assertNotNull($ultimoCatarinaPasillo);
        $this->assertTrue($danoVencido->is($ultimoCatarinaPasillo));
    }

    private function crearLinea(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
    }

    private function crearComponente(Linea $linea, string $codigoBase, string $reductor): Componente
    {
        return Componente::create([
            'codigo' => str_replace('-', '', $linea->nombre) . '_' . strtolower(str_replace(' ', '_', $reductor)) . '_' . $codigoBase,
            'nombre' => $codigoBase,
            'linea' => $linea->nombre,
            'reductor' => $reductor,
            'ubicacion' => $reductor,
            'cantidad_total' => 1,
            'tipo_equipo' => AnalisisLavadora::TIPO_EQUIPO,
            'activo' => true,
        ]);
    }
}
