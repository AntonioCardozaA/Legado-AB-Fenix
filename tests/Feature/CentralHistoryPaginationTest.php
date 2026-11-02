<?php

namespace Tests\Feature;

use App\Models\AnalisisCentralHidraulica;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CentralHistoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $this->actingAs($user);
        $line = Linea::firstOrCreate(['nombre' => 'P-03'], ['activo' => true]);
        $configuration = CentralHidraulicaConfiguracion::where('pasteurizador', 'P-03')
            ->where('piso', 'SUPERIOR')->whereHas('componente', fn ($query) => $query->where('codigo', 'ELECTROVALVULAS'))->firstOrFail();
        return [
            'linea_id' => $line->id, 'configuracion_id' => $configuration->id,
            'componente_id' => $configuration->componente_id, 'usuario_id' => $user->id,
            'piso' => 'SUPERIOR', 'lado' => 'LADO_1', 'fecha_analisis' => '2026-07-10',
            'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
            'estado' => AnalisisCentralHidraulica::ESTADO_BUENO, 'actividad' => 'Pagination fixture',
            'cantidad_componentes_revisados' => 1, 'total_componentes' => 3,
            'evidencia_fotos' => '["fixture.jpg"]', 'componentes_revisados' => '[1]', 'tipo_registro' => 'quick',
        ];
    }

    private function insert(array $attributes, int $count): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::table('analisis_central_hidraulica')->insertGetId($attributes);
        }
        return $ids;
    }

    public function test_all_filtered_history_is_reachable_with_global_total_and_stable_ties(): void
    {
        $row = $this->fixture();
        $expected = array_reverse($this->insert($row, 58));
        $this->insert(array_replace($row, ['lado' => 'LADO_2']), 5);
        $this->insert(array_replace($row, ['estado' => AnalisisCentralHidraulica::ESTADO_DANADO]), 2);
        $this->insert(array_replace($row, ['fecha_analisis' => '2026-08-01']), 2);
        $this->insert(array_replace($row, ['deleted_at' => '2026-07-11 10:00:00']), 1);
        $params = [
            'linea_id' => $row['linea_id'], 'piso' => 'SUPERIOR', 'lado' => 'LADO_1',
            'componente_id' => $row['componente_id'], 'estado' => $row['estado'], 'fecha' => '2026-07',
        ];
        $seen = [];
        for ($page = 1; $page <= 3; $page++) {
            $response = $this->get(route('pasteurizadora.central-hidraulica.historial', $params + ['page' => $page]))
                ->assertOk()->assertSee('58 registros encontrados');
            $paginator = $response->viewData('analisis');
            $this->assertSame(58, $paginator->total());
            $this->assertCount($page === 3 ? 8 : 25, $paginator);
            $seen = array_merge($seen, $paginator->pluck('id')->all());
            parse_str(parse_url($paginator->url(2), PHP_URL_QUERY), $query);
            foreach ($params as $key => $value) $this->assertSame((string) $value, $query[$key]);
        }
        $this->assertSame($expected, $seen);
        $this->assertDatabaseCount('analisis_central_hidraulica', 68);
        $this->assertSame('["fixture.jpg"]', DB::table('analisis_central_hidraulica')->value('evidencia_fotos'));
    }

    public function test_detail_pages_preserve_context_side_record_and_evidence(): void
    {
        $row = $this->fixture();
        $ids = $this->insert($row, 58);
        $this->insert(array_replace($row, ['lado' => 'LADO_2']), 7);
        $seen = [];
        for ($page = 1; $page <= 3; $page++) {
            $response = $this->get(route('pasteurizadora.central-hidraulica.show', [
                $ids[0], 'historial_page' => $page,
            ]))->assertOk()->assertSee('fixture.jpg');
            $this->assertSame($ids[0], $response->viewData('analisis')->id);
            $paginator = $response->viewData('historial');
            $this->assertSame(58, $paginator->total());
            $seen = array_merge($seen, $paginator->pluck('id')->all());
            $this->assertStringContainsString('historial_page=2', $paginator->url(2));
        }
        $this->assertSame(array_reverse($ids), $seen);
        $this->assertDatabaseCount('analisis_central_hidraulica', 65);
    }

    public function test_empty_and_out_of_range_pages_are_read_only_and_access_requires_login(): void
    {
        $row = $this->fixture();
        $this->get(route('pasteurizadora.central-hidraulica.historial'))->assertOk()
            ->assertSee('0 registros encontrados')->assertSee('No hay registros');
        $this->insert($row, 1);
        $response = $this->get(route('pasteurizadora.central-hidraulica.historial', ['page' => 99]))->assertOk();
        $this->assertSame(1, $response->viewData('analisis')->total());
        $this->assertCount(0, $response->viewData('analisis'));
        $this->assertDatabaseCount('analisis_central_hidraulica', 1);
        auth()->guard('web')->logout();
        $this->get(route('pasteurizadora.central-hidraulica.historial'))->assertRedirect(route('login'));
    }

    public function test_catalog_queries_are_bounded_and_legacy_quantity_rules_are_preserved(): void
    {
        $row = $this->fixture();
        // The original unloaded-configuration accessor selects codigo only and
        // defaults non-oil components to countable, even with a false catalog flag.
        \App\Models\CentralHidraulicaComponente::findOrFail($row['componente_id'])->update(['contabilizable' => false]);
        $this->insert($row, 25);
        $oil = CentralHidraulicaConfiguracion::where('pasteurizador', 'P-03')->where('piso', 'SUPERIOR')
            ->whereHas('componente', fn ($query) => $query->where('codigo', 'ACEITE'))->firstOrFail();
        $this->insert(array_replace($row, [
            'configuracion_id' => $oil->id, 'componente_id' => $oil->componente_id,
            'cantidad_componentes_revisados' => 25, 'total_componentes' => 300,
        ]), 1);
        $this->insert(array_replace($row, ['configuracion_id' => null]), 1);

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $response = $this->get(route('pasteurizadora.central-hidraulica.historial'))->assertOk()
                ->assertSee('25 lts')->assertSee('1/3');
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }
        $catalogQueries = array_filter($queries, fn ($query) => str_contains($query['query'], '"central_hidraulica_componentes"'));
        $this->assertLessThanOrEqual(5, count($catalogQueries), 'Catalog queries must not grow by row.');
        $items = $response->viewData('analisis');
        $this->assertSame(27, $items->total());
        foreach ($items as $item) {
            $this->assertSame($item->componente_id !== $oil->componente_id, $item->es_contabilizable);
        }
    }
}
