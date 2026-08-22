<?php

namespace Tests\Feature;

use App\Models\AnalisisCentralHidraulica;
use App\Models\CentralHidraulicaComponente;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentralHidraulicaPasteurizadoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_catalog_uses_floor_structure_from_excel_without_modules(): void
    {
        $this->assertFalse(Schema::hasColumn('analisis_central_hidraulica', 'modulo'));
        $this->assertSame(19, CentralHidraulicaComponente::count());
        $aceite = CentralHidraulicaComponente::where('codigo', 'ACEITE')->firstOrFail();
        $this->assertSame('Revision de aceite', $aceite->nombre);
        $this->assertFalse((bool) $aceite->contabilizable);

        $p03Superior = CentralHidraulicaConfiguracion::with('componente')
            ->where('pasteurizador', 'P-03')
            ->where('piso', CentralHidraulicaConfiguracion::PISO_SUPERIOR)
            ->get()
            ->keyBy(fn (CentralHidraulicaConfiguracion $config) => $config->componente->codigo);

        $this->assertTrue($p03Superior->has('BOMBAS_HIDRAULICAS_INUNDADAS'));
        $this->assertTrue($p03Superior->has('TUBERIA_1_PULGADA'));
        $this->assertTrue($p03Superior->has('CODOS_CONEXIONES'));
        $this->assertTrue($p03Superior->has('ACEITE'));
        $this->assertNull($p03Superior['TUBERIA_1_PULGADA']->cantidad);
        $this->assertNull($p03Superior['CODOS_CONEXIONES']->cantidad);
        $this->assertSame(300, $p03Superior['ACEITE']->cantidad);
        $this->assertSame('lts', $p03Superior['ACEITE']->unidad);
        $this->assertFalse($p03Superior['ACEITE']->es_contabilizable);

        $p06Superior = CentralHidraulicaConfiguracion::with('componente')
            ->where('pasteurizador', 'P-06')
            ->where('piso', CentralHidraulicaConfiguracion::PISO_SUPERIOR)
            ->get()
            ->keyBy(fn (CentralHidraulicaConfiguracion $config) => $config->componente->codigo);

        $this->assertTrue($p06Superior->has('BOMBAS_HIDRAULICAS_EXTERNAS'));
        $this->assertFalse($p06Superior->has('BOMBAS_HIDRAULICAS_INUNDADAS'));
        $this->assertTrue((bool) $p06Superior['BOMBAS_HIDRAULICAS_EXTERNAS']->lado_requerido);
    }

    public function test_central_index_displays_component_name_only_once(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);

        CentralHidraulicaComponente::where('codigo', 'FILTRO_ACEITE')
            ->firstOrFail()
            ->update(['nombre' => 'Filtro de aceite - Filtro de aceite']);

        $this->actingAs($user)
            ->get(route('pasteurizadora.central-hidraulica.index', ['linea_id' => $linea->id]))
            ->assertOk()
            ->assertSee('Filtro de aceite', false)
            ->assertDontSee('Filtro de aceite - Filtro de aceite', false);
    }

    public function test_central_analysis_can_be_stored_by_floor_side_and_component(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $config = $this->config('P-03', CentralHidraulicaConfiguracion::PISO_SUPERIOR, 'ELECTROVALVULAS');

        $response = $this->actingAs($user)->post(route('pasteurizadora.central-hidraulica.store'), [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'lado' => AnalisisCentralHidraulica::LADO_1,
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => '1001',
            'estado' => AnalisisCentralHidraulica::ESTADO_BUENO,
            'actividad' => 'Revision de electrovalvulas en central hidraulica',
            'componentes_revisados' => [1, 2],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.central-hidraulica.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_central_hidraulica', [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'componente_id' => $config->componente_id,
            'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
            'lado' => AnalisisCentralHidraulica::LADO_1,
            'cantidad_componentes_revisados' => 2,
            'total_componentes' => 3,
        ]);

        $analisis = AnalisisCentralHidraulica::firstOrFail();

        $this->actingAs($user)
            ->get(route('pasteurizadora.central-hidraulica.show', $analisis->id))
            ->assertOk()
            ->assertSee('Piso Superior')
            ->assertSee('Lado 1');
    }

    public function test_components_with_pending_quantity_can_be_registered_without_schema_changes(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $config = $this->config('P-03', CentralHidraulicaConfiguracion::PISO_INFERIOR, 'TUBERIA_1_PULGADA');

        $response = $this->actingAs($user)->post(route('pasteurizadora.central-hidraulica.store'), [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'fecha_analisis' => now()->toDateString(),
            'estado' => AnalisisCentralHidraulica::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Revision inicial de tuberia sin cantidad base definida',
            'cantidad_componentes_revisados' => 7,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pasteurizadora.central-hidraulica.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_central_hidraulica', [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'piso' => CentralHidraulicaConfiguracion::PISO_INFERIOR,
            'lado' => null,
            'cantidad_componentes_revisados' => 7,
            'total_componentes' => null,
        ]);
    }

    public function test_oil_revision_defaults_to_three_hundred_liters_and_does_not_count_as_component_progress(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = Linea::create([
            'nombre' => 'P-03',
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
        $config = $this->config('P-03', CentralHidraulicaConfiguracion::PISO_SUPERIOR, 'ACEITE');

        $response = $this->actingAs($user)->post(route('pasteurizadora.central-hidraulica.store'), [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => '30010001',
            'estado' => AnalisisCentralHidraulica::ESTADO_BUENO,
            'actividad' => 'Revision de aceite de central hidraulica',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('analisis_central_hidraulica', [
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'cantidad_componentes_revisados' => 300,
            'total_componentes' => 300,
        ]);

        $analisis = AnalisisCentralHidraulica::with(['configuracion', 'componente'])->firstOrFail();
        $this->assertSame('300 lts', $analisis->cantidad_display);

        $this->actingAs($user)
            ->getJson(route('pasteurizadora.central-hidraulica.ajax.estadisticas', ['linea_id' => $linea->id]))
            ->assertOk()
            ->assertJsonPath('estadisticas.totales.componentes', 72)
            ->assertJsonPath('estadisticas.totales.revisados', 0);
    }

    private function config(string $pasteurizador, string $piso, string $codigo): CentralHidraulicaConfiguracion
    {
        $componente = CentralHidraulicaComponente::where('codigo', $codigo)->firstOrFail();

        return CentralHidraulicaConfiguracion::where('pasteurizador', $pasteurizador)
            ->where('piso', $piso)
            ->where('componente_id', $componente->id)
            ->firstOrFail();
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
}
